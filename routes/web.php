<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])
    ->get('/commerce/{shop:slug}/print-labels', [\App\Http\Controllers\Commerce\LabelController::class, 'print'])
    ->name('labels.print');
