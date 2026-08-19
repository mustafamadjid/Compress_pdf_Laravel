<?php

namespace App\Livewire;

use App\Enums\CompressionLevel;
use App\Exceptions\PdfCompressionException;
use App\Services\PdfCompressionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class PdfCompressor extends Component
{
    use WithFileUploads;

    public $file;

    public string $compressionLevel = CompressionLevel::Medium->value;

    public ?string $originalFileName = null;

    public ?int $originalFileSize = null;

    public ?string $storedFilePath = null;

    public bool $isProcessing = false;

    public ?array $result = null;

    public ?string $compressionError = null;

    public function updatedCompressionLevel(string $value): void
    {
        $this->compressionLevel = CompressionLevel::fromInput($value)->value;
    }

    public function updatedFile(): void
    {
        $this->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'mimetypes:application/pdf',
                'max:'.(config('pdf-compressor.max_upload_mb') * 1024),
            ],
        ]);

        $this->originalFileName = $this->file->getClientOriginalName();
        $this->originalFileSize = $this->file->getSize();
        $this->storedFilePath = $this->file->storeAs(
            'temporary/uploads',
            Str::uuid().'.pdf',
            'local'
        );
    }

    public function resetFile(): void
    {
        if ($this->storedFilePath) {
            Storage::disk('local')->delete($this->storedFilePath);
        }

        $this->file = null;
        $this->originalFileName = null;
        $this->originalFileSize = null;
        $this->storedFilePath = null;
        $this->isProcessing = false;
        $this->result = null;
        $this->compressionError = null;
        $this->resetErrorBag('file');
    }

    public function resetCompression(): void
    {
        if (($this->result['file_identifier'] ?? null) !== null) {
            Storage::disk('local')->delete('temporary/compressed/'.$this->result['file_identifier'].'.pdf');
        }

        $this->resetFile();
        $this->compressionLevel = CompressionLevel::Medium->value;
        $this->resetValidation();
    }

    public function compress(PdfCompressionService $compressionService): void
    {
        $this->validate([
            'file' => ['required'],
            'storedFilePath' => ['required', 'string'],
            'compressionLevel' => ['required', 'in:low,medium,high'],
        ]);

        $this->isProcessing = true;
        $this->compressionError = null;

        try {
            $identifier = (string) Str::uuid();
            $metadata = $compressionService->compress(
                Storage::disk('local')->path($this->storedFilePath),
                storage_path('app/temporary/compressed/'.$identifier.'.pdf'),
                CompressionLevel::fromInput($this->compressionLevel),
            );
            $originalSize = $metadata['original_size'];
            $compressedSize = $metadata['compressed_size'];

            $this->result = [
                'original_filename' => $this->originalFileName,
                'original_size' => $originalSize,
                'compressed_size' => $compressedSize,
                'reduction_percentage' => $originalSize > 0
                    ? round((($originalSize - $compressedSize) / $originalSize) * 100, 2)
                    : 0,
                'compression_level' => $this->compressionLevel,
                'file_identifier' => $identifier,
                'download_url' => URL::temporarySignedRoute(
                    'pdf.download',
                    now()->addMinutes(config('pdf-compressor.retention_minutes')),
                    ['identifier' => $identifier, 'name' => $this->originalFileName],
                ),
            ];
        } catch (PdfCompressionException $exception) {
            Log::error('PDF compression failed.', [
                'exception' => $exception->getMessage(),
                'upload_path' => $this->storedFilePath,
            ]);

            $this->compressionError = 'The PDF could not be compressed. Please try another file.';
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render()
    {
        return view('livewire.pdf-compressor');
    }

    public function formatBytes(?int $bytes): string
    {
        if (! $bytes) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 2).' '.$units[$power];
    }
}
