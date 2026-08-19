<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/downloads/{identifier}', function (string $identifier) {
    $path = storage_path('app/temporary/compressed/'.$identifier.'.pdf');

    if (! is_file($path)) {
        return response('This compressed file is no longer available. Please compress the PDF again.', 404);
    }

    $originalName = pathinfo((string) request()->query('name', 'document.pdf'), PATHINFO_FILENAME);
    $safeName = Str::slug($originalName) ?: 'document';

    return response()->download($path, 'compressed-'.$safeName.'.pdf', [
        'Content-Type' => 'application/pdf',
    ]);
})->whereUuid('identifier')->middleware('signed')->name('pdf.download');
