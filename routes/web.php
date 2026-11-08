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

// Mode hors ligne (application autonome mise en cache par le service worker)
Route::middleware(['web', 'auth'])
    ->prefix('/commerce/{shop}/hors-ligne')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Commerce\OfflineController::class, 'show'])
            ->name('commerce.offline');
        Route::post('/token', [\App\Http\Controllers\Commerce\OfflineController::class, 'token'])
            ->middleware('throttle:10,1')
            ->name('commerce.offline.token');
    });
