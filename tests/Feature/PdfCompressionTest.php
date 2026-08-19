<?php

namespace Tests\Feature;

use App\Enums\CompressionLevel;
use App\Exceptions\PdfCompressionException;
use App\Livewire\PdfCompressor;
use App\Services\PdfCompressionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PdfCompressionTest extends TestCase
{
    #[Test]
    public function a_valid_pdf_can_be_selected(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        Livewire::test(PdfCompressor::class)
            ->set('file', $pdf)
            ->assertHasNoErrors()
            ->assertSet('originalFileName', 'document.pdf')
            ->assertSet('originalFileSize', 102400)
            ->assertSet('compressionLevel', 'medium')
            ->assertSet('storedFilePath', fn (string $path): bool => str_starts_with($path, 'temporary/uploads/'));

        Storage::disk('local')->assertExists($this->storedPath());
        $this->assertSame('document.pdf', $pdf->getClientOriginalName());
    }

    #[Test]
    public function invalid_extension_is_rejected(): void
    {
        Storage::fake('local');

        Livewire::test(PdfCompressor::class)
            ->set('file', UploadedFile::fake()->create('notes.txt', 10, 'text/plain'))
            ->assertHasErrors(['file' => 'mimes']);
    }

    #[Test]
    public function invalid_mime_type_is_rejected(): void
    {
        Storage::fake('local');

        Livewire::test(PdfCompressor::class)
            ->set('file', UploadedFile::fake()->create('fake.pdf', 10, 'text/plain'))
            ->assertHasErrors(['file' => 'mimetypes']);
    }

    #[Test]
    public function oversized_pdf_is_rejected(): void
    {
        Storage::fake('local');
        config()->set('livewire.temporary_file_upload.rules', 'file|max:30000');

        Livewire::test(PdfCompressor::class)
            ->set('file', UploadedFile::fake()->create('huge.pdf', (25 * 1024) + 1, 'application/pdf'))
            ->assertHasErrors(['file' => 'max']);
    }

    #[Test]
    public function medium_compression_level_is_default(): void
    {
        Livewire::test(PdfCompressor::class)
            ->assertSet('compressionLevel', CompressionLevel::Medium->value);
    }

    #[Test]
    public function compression_level_can_be_changed(): void
    {
        Livewire::test(PdfCompressor::class)
            ->set('compressionLevel', CompressionLevel::High->value)
            ->assertSet('compressionLevel', 'high');
    }

    #[Test]
    public function component_state_is_reset(): void
    {
        Storage::fake('local');

        Livewire::test(PdfCompressor::class)
            ->set('file', UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'))
            ->call('resetFile')
            ->assertSet('file', null)
            ->assertSet('originalFileName', null)
            ->assertSet('originalFileSize', null)
            ->assertSet('storedFilePath', null)
            ->assertSet('isProcessing', false);

        $this->assertEmpty(Storage::disk('local')->allFiles('temporary/uploads'));
    }

    #[Test]
    public function successful_compression_action_sets_result_metadata(): void
    {
        Storage::fake('local');
        $this->app->bind(PdfCompressionService::class, fn () => $this->fakeService(4000, 1000));

        Livewire::test(PdfCompressor::class)
            ->set('file', UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'))
            ->call('compress')
            ->assertSet('compressionError', null)
            ->assertSet('isProcessing', false)
            ->assertSet('result.original_filename', 'document.pdf')
            ->assertSet('result.original_size', 4000)
            ->assertSet('result.compressed_size', 1000)
            ->assertSet('result.reduction_percentage', 75.0)
            ->assertSet('result.compression_level', 'medium')
            ->assertSet('result.file_identifier', fn (string $value): bool => Str::isUuid($value))
            ->assertSet('result.download_url', fn (string $value): bool => str_contains($value, '/downloads/'))
            ->assertSee('Download PDF')
            ->assertSee('Compression Complete');
    }

    #[Test]
    public function compression_failure_shows_generic_error(): void
    {
        Storage::fake('local');
        $this->app->bind(PdfCompressionService::class, fn () => new class extends PdfCompressionService
        {
            public function compress(string $sourcePath, string $destinationPath, CompressionLevel $level): array
            {
                throw new PdfCompressionException('Ghostscript missing at C:/secret/path/gs');
            }
        });

        Livewire::test(PdfCompressor::class)
            ->set('file', UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'))
            ->call('compress')
            ->assertSet('result', null)
            ->assertSet('isProcessing', false)
            ->assertSet('compressionError', 'The PDF could not be compressed. Please try another file.')
            ->assertDontSee('C:/secret/path/gs');
    }

    #[Test]
    public function larger_output_still_shows_result_with_warning(): void
    {
        Storage::fake('local');
        $this->app->bind(PdfCompressionService::class, fn () => $this->fakeService(1000, 1200));

        Livewire::test(PdfCompressor::class)
            ->set('file', UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'))
            ->set('compressionLevel', 'high')
            ->call('compress')
            ->assertSet('result.reduction_percentage', -20.0)
            ->assertSee('This PDF is already well optimized and could not be reduced significantly.');
    }

    #[Test]
    public function reset_compression_clears_component_state_and_generated_file(): void
    {
        Storage::fake('local');
        $identifier = '550e8400-e29b-41d4-a716-446655440010';
        Storage::disk('local')->put('temporary/compressed/'.$identifier.'.pdf', 'compressed');

        Livewire::test(PdfCompressor::class)
            ->set('result', [
                'file_identifier' => $identifier,
                'original_size' => 1000,
                'compressed_size' => 800,
                'reduction_percentage' => 20,
                'compression_level' => 'medium',
                'download_url' => 'https://example.com',
            ])
            ->set('compressionLevel', 'high')
            ->set('compressionError', 'error')
            ->call('resetCompression')
            ->assertSet('result', null)
            ->assertSet('compressionLevel', 'medium')
            ->assertSet('compressionError', null)
            ->assertSet('file', null);

        Storage::disk('local')->assertMissing('temporary/compressed/'.$identifier.'.pdf');
    }

    protected function fakeService(int $originalSize, int $compressedSize): PdfCompressionService
    {
        return new class($originalSize, $compressedSize) extends PdfCompressionService
        {
            public function __construct(private readonly int $originalSize, private readonly int $compressedSize) {}

            public function compress(string $sourcePath, string $destinationPath, CompressionLevel $level): array
            {
                return [
                    'original_size' => $this->originalSize,
                    'compressed_size' => $this->compressedSize,
                    'compression_level' => $level,
                    'preset' => $level->ghostscriptPreset(),
                    'output_path' => $destinationPath,
                ];
            }
        };
    }

    protected function storedPath(): string
    {
        $files = Storage::disk('local')->allFiles('temporary/uploads');

        return $files[0] ?? '';
    }
}
