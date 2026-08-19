<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PdfDownloadTest extends TestCase
{
    #[Test]
    public function valid_signed_identifier_downloads_generated_pdf(): void
    {
        $identifier = '550e8400-e29b-41d4-a716-446655440000';
        $path = $this->temporaryPath($identifier);
        file_put_contents($path, '%PDF-generated-content');
        $url = URL::temporarySignedRoute('pdf.download', now()->addMinutes(5), [
            'identifier' => $identifier,
            'name' => 'My Thesis.pdf',
        ]);

        $response = $this->get($url);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('compressed-my-thesis.pdf');
    }

    #[Test]
    public function missing_generated_file_returns_expired_message(): void
    {
        $url = URL::temporarySignedRoute('pdf.download', now()->addMinutes(5), [
            'identifier' => '550e8400-e29b-41d4-a716-446655440001',
            'name' => 'document.pdf',
        ]);

        $this->get($url)
            ->assertNotFound()
            ->assertSee('This compressed file is no longer available. Please compress the PDF again.');
    }

    #[Test]
    public function expired_signed_url_is_rejected(): void
    {
        $url = URL::temporarySignedRoute('pdf.download', now()->subMinute(), [
            'identifier' => '550e8400-e29b-41d4-a716-446655440002',
            'name' => 'document.pdf',
        ]);

        $this->get($url)->assertForbidden();
    }

    #[Test]
    public function arbitrary_path_and_non_uuid_identifier_cannot_be_downloaded(): void
    {
        $this->get('/downloads/..%2F..%2F.env')->assertNotFound();
        $this->get('/downloads/not-a-uuid')->assertNotFound();
    }

    #[Test]
    public function filename_is_sanitized_and_path_is_not_client_controlled(): void
    {
        $identifier = '550e8400-e29b-41d4-a716-446655440003';
        file_put_contents($this->temporaryPath($identifier), '%PDF-generated-content');
        $url = URL::temporarySignedRoute('pdf.download', now()->addMinutes(5), [
            'identifier' => $identifier,
            'name' => '../../secret report.pdf',
        ]);

        $this->get($url)->assertDownload('compressed-secret-report.pdf');
    }

    protected function temporaryPath(string $identifier): string
    {
        $directory = storage_path('app/temporary/compressed');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory.DIRECTORY_SEPARATOR.$identifier.'.pdf';
    }
}
