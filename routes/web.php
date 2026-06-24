<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sitemap.xml', function () {
    return response(view('sitemap'), 200)
        ->header('Content-Type', 'application/xml');
})->name('sitemap');

// Impression d'étiquettes désactivée temporairement
// Route::middleware(['web', 'auth'])
//     ->get('/commerce/{shop:slug}/print-labels', [\App\Http\Controllers\Commerce\LabelController::class, 'print'])
//     ->name('labels.print');

Route::get('/commerce/pending', fn () => view('auth.pending'))
    ->name('register.pending');

Route::middleware(['web', 'auth'])
    ->get('/commerce/{shop}/rapports/pdf/{type}', [\App\Http\Controllers\ReportPdfController::class, 'generate'])
    ->name('commerce.reports.pdf');
