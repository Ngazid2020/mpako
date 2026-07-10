<?php

namespace App\Filament\Commerce\Widgets;

use App\Filament\Commerce\Resources\CreditResource;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class AlertsWidget extends Widget
{
    public static function canView(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'commerce';
    }

    protected static ?int    $sort            = 0;   // Avant tout le reste
    protected static ?string $pollingInterval = '120s';
    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.commerce.widgets.alerts-widget';

    public function getOverdueCredits(): Collection
    {
        $shop = Filament::getTenant();

        return $shop->credits()
            ->overdue()
            ->with('customer')
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();
    }

    public function getExpiringSoonCredits(): Collection
    {
        $shop = Filament::getTenant();

        return $shop->credits()
            ->expiringSoon(7)
            ->with('customer')
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();
    }

    public function getCreditsUrl(): string
    {
        $shop = Filament::getTenant();
        return CreditResource::getUrl('index', tenant: $shop);
    }
}
