<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PdfDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);

Route::get('/downloads/{identifier}', PdfDownloadController::class)
    ->whereUuid('identifier')
    ->middleware('signed')
    ->name('pdf.download');
