<?php

namespace Tests\Feature;

use App\Enums\CompressionLevel;
use App\Livewire\PdfCompressor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $this->assertCount(1, Storage::disk('local')->allFiles('temporary/uploads'));
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

    protected function storedPath(): string
    {
        $files = Storage::disk('local')->allFiles('temporary/uploads');

        return $files[0] ?? '';
    }
}
