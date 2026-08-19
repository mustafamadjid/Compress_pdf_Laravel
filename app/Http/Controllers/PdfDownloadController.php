<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PdfDownloadController extends Controller
{
    public function __invoke(string $identifier): Response
    {
        $path = storage_path('app/temporary/compressed/'.$identifier.'.pdf');

        if (! is_file($path)) {
            return response('This compressed file is no longer available. Please compress the PDF again.', 404);
        }

        $originalName = pathinfo((string) request()->query('name', 'document.pdf'), PATHINFO_FILENAME);
        $safeName = Str::slug($originalName) ?: 'document';

        return response()->download($path, 'compressed-'.$safeName.'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
