<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PdfDownloadController extends Controller
{
    public function __invoke(string $identifier): BinaryFileResponse
    {
        $path = storage_path('app/temporary/compressed/'.$identifier.'.pdf');

        if (! is_file($path)) {
            abort(404, 'This compressed file is no longer available. Please compress the PDF again.');
        }

        $originalName = pathinfo((string) request()->query('name', 'document.pdf'), PATHINFO_FILENAME);
        $safeName = Str::slug($originalName) ?: 'document';

        // return response()->download($path, 'compressed-'.$safeName.'.pdf', [
        //     'Content-Type' => 'application/pdf',
        // ]);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="compressed-'.$safeName.'.pdf"',
        ]);
    }
}
