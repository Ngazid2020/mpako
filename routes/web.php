<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Impression d'étiquettes désactivée temporairement
// Route::middleware(['web', 'auth'])
//     ->get('/commerce/{shop:slug}/print-labels', [\App\Http\Controllers\Commerce\LabelController::class, 'print'])
//     ->name('labels.print');

Route::get('/commerce/pending', fn () => view('auth.pending'))
    ->name('register.pending');
