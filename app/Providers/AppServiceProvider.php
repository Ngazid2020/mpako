<?php

namespace App\Providers;

use App\Models\Purchase;
use App\Models\StockMovement;
use App\Observers\PurchaseObserver;
use App\Observers\StockMovementObserver;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        StockMovement::observe(StockMovementObserver::class);
        Purchase::observe(PurchaseObserver::class);
        // Dates en français
        Carbon::setLocale('fr');
    }
}
