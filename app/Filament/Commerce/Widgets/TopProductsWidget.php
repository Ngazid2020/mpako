<?php

namespace App\Filament\Commerce\Widgets;

use App\Models\SaleItem;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class TopProductsWidget extends Widget
{
    protected static ?int    $sort        = 3;
    protected static ?string $pollingInterval = '60s';

    // Colonne : ce widget prend la moitié droite
    protected int | string | array $columnSpan = 1;

    protected static string $view = 'filament.commerce.widgets.top-products-widget';

    // Filtre de période
    public string $period = '7days';

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

    public function getTopProducts(): Collection
    {
        $shop = Filament::getTenant();

        $days = match ($this->period) {
            '30days' => 30,
            'month'  => now()->daysInMonth,
            default  => 7,
        };

        return SaleItem::query()
            ->whereHas('sale', function ($query) use ($shop, $days) {
                $query->where('shop_id', $shop->id)
                    ->where('status', 'completed')
                    ->where('created_at', '>=', now()->subDays($days));
            })
            ->selectRaw('product_id, product_name, SUM(quantity) as total_qty, SUM(subtotal) as total_amount')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();
    }
}
