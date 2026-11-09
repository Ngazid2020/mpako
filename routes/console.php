<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Alertes quotidiennes : stock bas, crédits en retard, dettes fournisseurs
Schedule::command('mpako:send-alerts')->dailyAt('08:00');
