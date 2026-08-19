<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CleanupTemporaryPdfFilesTest extends TestCase
{
    #[Test]
    public function expired_pdfs_are_deleted_and_non_pdf_files_are_preserved(): void
    {
        Storage::fake('local');
        config()->set('pdf-compressor.retention_minutes', -1);
        Storage::disk('local')->put('temporary/uploads/old.pdf', 'old');
        Storage::disk('local')->put('temporary/compressed/fresh.pdf', 'fresh');
        Storage::disk('local')->put('temporary/uploads/keep.txt', 'keep');

        $this->artisan('pdf:cleanup')->assertSuccessful();

        Storage::disk('local')->assertMissing('temporary/uploads/old.pdf');
        Storage::disk('local')->assertMissing('temporary/compressed/fresh.pdf');
        Storage::disk('local')->assertExists('temporary/uploads/keep.txt');
    }

    #[Test]
    public function fresh_files_are_preserved(): void
    {
        Storage::fake('local');
        config()->set('pdf-compressor.retention_minutes', 99999);
        Storage::disk('local')->put('temporary/uploads/fresh.pdf', 'fresh');
        Storage::disk('local')->put('temporary/compressed/fresh.pdf', 'fresh');
        Storage::disk('local')->put('unrelated.pdf', 'do not delete');

        $this->artisan('pdf:cleanup')->assertSuccessful();

        Storage::disk('local')->assertExists('temporary/uploads/fresh.pdf');
        Storage::disk('local')->assertExists('temporary/compressed/fresh.pdf');
        Storage::disk('local')->assertExists('unrelated.pdf');
    }
}
