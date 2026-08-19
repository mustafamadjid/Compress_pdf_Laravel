<?php

namespace Tests\Unit;

use App\Enums\CompressionLevel;
use App\Exceptions\PdfCompressionException;
use App\Services\PdfCompressionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PdfCompressionServiceTest extends TestCase
{
    #[Test]
    public function missing_source_file_is_rejected(): void
    {
        $this->expectException(PdfCompressionException::class);

        (new PdfCompressionService)->compress(
            $this->temporaryPath('does-not-exist.pdf'),
            $this->temporaryPath('output.pdf'),
            CompressionLevel::Medium
        );
    }

    #[Test]
    public function empty_source_file_is_rejected_before_process_execution(): void
    {
        $source = $this->temporaryPath('empty-source.pdf');
        file_put_contents($source, '');

        $this->expectException(PdfCompressionException::class);
        $this->expectExceptionMessage('The source PDF file is empty.');

        (new PdfCompressionService)->compress(
            $source,
            $this->temporaryPath('output.pdf'),
            CompressionLevel::Medium
        );
    }

    #[Test]
    public function missing_output_after_process_is_rejected(): void
    {
        $this->expectException(PdfCompressionException::class);
        $this->expectExceptionMessage('The compressed PDF output was not created.');

        $this->fakeService()->compress(
            $this->createSourcePdf(),
            $this->temporaryPath('missing-output.pdf'),
            CompressionLevel::Medium
        );
    }

    #[Test]
    public function process_failure_is_rejected_and_partial_output_is_removed(): void
    {
        $destination = $this->temporaryPath('process-failure.pdf');
        $service = $this->fakeService(
            static function (string $destination): void {
                file_put_contents($destination, 'partial');
                throw new PdfCompressionException('Ghostscript failed to compress the PDF.');
            }
        );

        $this->expectException(PdfCompressionException::class);

        try {
            $service->compress($this->createSourcePdf(), $destination, CompressionLevel::Medium);
        } finally {
            $this->assertFileDoesNotExist($destination);
        }
    }

    #[Test]
    public function empty_output_is_rejected(): void
    {
        $this->expectException(PdfCompressionException::class);
        $this->expectExceptionMessage('The compressed PDF output is empty.');

        $this->fakeService(static fn (string $destination) => file_put_contents($destination, ''))->compress(
            $this->createSourcePdf(),
            $this->temporaryPath('empty-output.pdf'),
            CompressionLevel::High
        );
    }

    #[Test]
    public function successful_compression_returns_metadata(): void
    {
        $source = $this->createSourcePdf('source-pdf-content');
        $destination = $this->temporaryPath('successful-output.pdf');
        $result = $this->fakeService(
            static fn (string $destination) => file_put_contents($destination, 'compressed-pdf-content')
        )->compress($source, $destination, CompressionLevel::Low);

        $this->assertSame(18, $result['original_size']);
        $this->assertSame(22, $result['compressed_size']);
        $this->assertSame(CompressionLevel::Low, $result['compression_level']);
        $this->assertSame('/prepress', $result['preset']);
        $this->assertSame(str_replace('\\', '/', $destination), $result['output_path']);
    }

    #[Test]
    public function compression_level_maps_to_expected_preset(): void
    {
        foreach ([
            [CompressionLevel::Low, '/prepress'],
            [CompressionLevel::Medium, '/ebook'],
            [CompressionLevel::High, '/screen'],
        ] as [$level, $preset]) {
            $result = $this->fakeService(
                static fn (string $destination) => file_put_contents($destination, 'content')
            )->compress($this->createSourcePdf(), $this->temporaryPath($level->value.'.pdf'), $level);

            $this->assertSame($preset, $result['preset']);
        }
    }

    protected function fakeService(?callable $runner = null): PdfCompressionService
    {
        return new class($runner) extends PdfCompressionService
        {
            public function __construct(private readonly mixed $runner)
            {
                parent::__construct();
            }

            protected function runGhostscript(string $sourcePath, string $destinationPath, CompressionLevel $level): void
            {
                if ($this->runner !== null) {
                    ($this->runner)($destinationPath);
                }
            }
        };
    }

    protected function createSourcePdf(string $content = 'source-pdf-content'): string
    {
        $path = $this->temporaryPath('source-'.md5($content).'.pdf');
        file_put_contents($path, $content);

        return $path;
    }

    protected function temporaryPath(string $name): string
    {
        $directory = storage_path('app/temporary/tests');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory.DIRECTORY_SEPARATOR.$name;
    }
}
