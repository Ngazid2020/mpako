<?php

namespace App\Filament\Commerce\Widgets;

use App\Models\Product;
use App\Traits\HasShieldPermission;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\Concerns\Has;

class LowStockWidget extends Widget
{
    use HasShieldPermission;
    protected static ?int    $sort            = 4;
    protected static ?string $pollingInterval = '60s';

    // Ce widget prend toute la largeur
    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.commerce.widgets.low-stock-widget';



    public function getLowStockProducts(): Collection
    {
        $shop = Filament::getTenant();

        return $shop->products()
            ->where('is_active', true)
            ->whereColumn('stock_qty', '<=', 'stock_alert')
            ->with(['category', 'unit'])
            ->orderBy('stock_qty', 'asc') // Les plus critiques en premier
            ->get();
    }
}
