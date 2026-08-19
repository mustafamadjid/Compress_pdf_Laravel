<?php

namespace App\Services;

use App\Enums\CompressionLevel;
use App\Exceptions\PdfCompressionException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class PdfCompressionService
{
    public function __construct(?int $processTimeoutSeconds = null, ?string $ghostscriptBinary = null)
    {
        $this->processTimeoutSeconds = $processTimeoutSeconds ?? config('pdf-compressor.process_timeout');
        $this->ghostscriptBinary = $ghostscriptBinary ?? config('pdf-compressor.ghostscript_binary');
    }

    protected int $processTimeoutSeconds;

    protected string $ghostscriptBinary;

    public function compress(string $sourcePath, string $destinationPath, CompressionLevel $level): array
    {
        $sourcePath = $this->normalizePath($sourcePath);
        $destinationPath = $this->normalizePath($destinationPath);

        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new PdfCompressionException('The source PDF file does not exist or is not readable.');
        }

        if (filesize($sourcePath) === 0) {
            throw new PdfCompressionException('The source PDF file is empty.');
        }

        $destinationDirectory = dirname($destinationPath);

        if (! is_dir($destinationDirectory) && ! @mkdir($destinationDirectory, 0755, true) && ! is_dir($destinationDirectory)) {
            throw new PdfCompressionException('The destination directory could not be created.');
        }

        try {
            $this->runGhostscript($sourcePath, $destinationPath, $level);
        } catch (PdfCompressionException $exception) {
            $this->forgetPartialOutput($destinationPath);

            throw $exception;
        }

        if (! is_file($destinationPath) || ! is_readable($destinationPath)) {
            throw new PdfCompressionException('The compressed PDF output was not created.');
        }

        if (filesize($destinationPath) === 0) {
            throw new PdfCompressionException('The compressed PDF output is empty.');
        }

        return [
            'original_size' => (int) filesize($sourcePath),
            'compressed_size' => (int) filesize($destinationPath),
            'compression_level' => $level,
            'preset' => $level->ghostscriptPreset(),
            'output_path' => $destinationPath,
        ];
    }

    protected function runGhostscript(string $sourcePath, string $destinationPath, CompressionLevel $level): void
    {
        $process = new Process([
            $this->ghostscriptBinary,
            '-q',
            '-dNOPAUSE',
            '-dBATCH',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dPDFSETTINGS='.$level->ghostscriptPreset(),
            '-sOutputFile='.$destinationPath,
            $sourcePath,
        ], null, null, null, $this->processTimeoutSeconds);

        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            Log::error('PDF compression process timed out.', [
                'timeout' => $this->processTimeoutSeconds,
                'source' => $sourcePath,
                'exception' => $exception->getMessage(),
            ]);

            $this->forgetPartialOutput($destinationPath);

            throw new PdfCompressionException('The PDF compression process timed out.');
        } catch (Throwable $throwable) {
            Log::error('PDF compression process failed to start.', [
                'source' => $sourcePath,
                'exception' => $throwable->getMessage(),
            ]);

            throw new PdfCompressionException('The PDF compression process could not be executed.');
        }

        if (! $process->isSuccessful()) {
            Log::error('PDF compression process exited with a non-zero status.', [
                'exit_code' => $process->getExitCode(),
                'source' => $sourcePath,
                'output' => substr($process->getOutput().$process->getErrorOutput(), 0, 1024),
            ]);

            $this->forgetPartialOutput($destinationPath);

            throw new PdfCompressionException('Ghostscript failed to compress the PDF.');
        }
    }

    protected function normalizePath(string $path): string
    {
        if (str_starts_with($path, '~')) {
            throw new PdfCompressionException('Tilde-expanded paths are not supported.');
        }

        return str_replace('\\', '/', $path);
    }

    protected function forgetPartialOutput(string $destinationPath): void
    {
        try {
            if (is_file($destinationPath)) {
                unlink($destinationPath);
            }
        } catch (Throwable $throwable) {
            Log::warning('Could not remove partial compressed output.', [
                'destination' => $destinationPath,
                'exception' => $throwable->getMessage(),
            ]);
        }
    }
}
