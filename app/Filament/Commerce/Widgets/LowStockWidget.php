<?php

namespace App\Filament\Commerce\Widgets;

use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class LowStockWidget extends Widget
{
    protected static ?int    $sort            = 4;
    protected static ?string $pollingInterval = '60s';

    // Ce widget prend toute la largeur
    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.commerce.widgets.low-stock-widget';


    public static function canView(): bool
    {
        // Cacher du panel admin
        if (\Filament\Facades\Filament::getCurrentPanel()?->getId() === 'admin') {
            return false;
        }

        // Vérifier la permission Shield dans les autres panels
        $className  = class_basename(static::class);
        return auth()->user()?->can("widget_{$className}") ?? false;
    }

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
