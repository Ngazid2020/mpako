<?php

namespace App\Filament\Commerce\Widgets;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    // Rafraîchissement automatique toutes les 30 secondes
    protected static ?string $pollingInterval = '30s';
    protected int | string | array $columnSpan = 'full';
    // Ce widget est en haut du dashboard
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $shop = Filament::getTenant();

        // ── Ventes du jour ──
        $todaySales = $shop->sales()
            ->whereDate('created_at', today())
            ->where('status', 'completed');

        $todayCount  = $todaySales->count();
        $todayAmount = $todaySales->sum('total_amount');

        // ── Ventes d'hier (pour la comparaison) ──
        $yesterdaySales = $shop->sales()
            ->whereDate('created_at', Carbon::yesterday())
            ->where('status', 'completed');

        $yesterdayCount  = $yesterdaySales->count();
        $yesterdayAmount = $yesterdaySales->sum('total_amount');

        // ── Ventes du mois ──
        $monthAmount = $shop->sales()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'completed')
            ->sum('total_amount');

        // ── Produits en stock bas ──
        $lowStockCount = $shop->products()
            ->where('is_active', true)
            ->whereColumn('stock_qty', '<=', 'stock_alert')
            ->count();

        // ── Calcul des tendances ──
        $salesTrend  = $this->getTrend($todayCount, $yesterdayCount);
        $amountTrend = $this->getTrend($todayAmount, $yesterdayAmount);

        // ── Graphe sparkline des 7 derniers jours ──
        $last7Days = collect(range(6, 0))->map(function ($daysAgo) use ($shop) {
            return $shop->sales()
                ->whereDate('created_at', now()->subDays($daysAgo))
                ->where('status', 'completed')
                ->sum('total_amount');
        })->toArray();
        $pendingCreditsAmount = $shop->credits()
            ->whereIn('status', ['pending', 'partial'])
            ->sum('remaining_amount');

        $pendingCreditsCount = $shop->credits()
            ->whereIn('status', ['pending', 'partial'])
            ->count();

        // ── Dettes fournisseurs ──
        $supplierDebt = $shop->suppliers()
            ->where('balance', '>', 0)
            ->sum('balance');

        $supplierDebtCount = $shop->suppliers()
            ->where('balance', '>', 0)
            ->count();

        return [
            // ── Stat 1 : CA du jour ──
            Stat::make('💰 CA du jour', number_format($todayAmount, 0, ',', ' ') . ' KMF')
                ->description($amountTrend['label'])
                ->descriptionIcon($amountTrend['icon'])
                ->color($amountTrend['color'])
                ->chart($last7Days),

            // ── Stat 2 : Nombre de ventes ──
            Stat::make('🧾 Ventes aujourd\'hui', $todayCount . ' vente(s)')
                ->description($salesTrend['label'])
                ->descriptionIcon($salesTrend['icon'])
                ->color($salesTrend['color'])
                ->chart($last7Days),

            // ── Stat 3 : CA du mois ──
            Stat::make('📅 CA du mois', number_format($monthAmount, 0, ',', ' ') . ' KMF')
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            // ── Stat 4 : Alertes stock ──
            Stat::make('⚠️ Stock bas', $lowStockCount . ' produit(s)')
                ->description(
                    $lowStockCount === 0
                        ? 'Tout est en ordre'
                        : 'À réapprovisionner'
                )
                ->descriptionIcon(
                    $lowStockCount === 0
                        ? 'heroicon-m-check-circle'
                        : 'heroicon-m-exclamation-triangle'
                )
                ->color($lowStockCount === 0 ? 'success' : 'danger'),

            Stat::make('📒 Crédits en cours', $pendingCreditsCount . ' crédit(s)')
                ->description(number_format($pendingCreditsAmount, 0, ',', ' ') . ' KMF à récupérer')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($pendingCreditsCount > 0 ? 'warning' : 'success'),

            Stat::make('🚚 Dettes fournisseurs', $supplierDebtCount . ' fournisseur(s)')
                ->description(number_format($supplierDebt, 0, ',', ' ') . ' KMF à payer')
                ->descriptionIcon('heroicon-m-truck')
                ->color($supplierDebtCount > 0 ? 'warning' : 'success'),
        ];
    }

    // ─────────────────────────────────────────────
    // HELPER : Calcul de tendance
    // ─────────────────────────────────────────────

    private function getTrend(float $current, float $previous): array
    {
        if ($previous == 0) {
            return [
                'label' => 'Pas de données hier',
                'icon'  => 'heroicon-m-minus',
                'color' => 'gray',
            ];
        }

        $diff       = $current - $previous;
        $percentage = round(abs($diff / $previous) * 100);

        if ($diff > 0) {
            return [
                'label' => "+{$percentage}% par rapport à hier",
                'icon'  => 'heroicon-m-arrow-trending-up',
                'color' => 'success',
            ];
        }

        if ($diff < 0) {
            return [
                'label' => "-{$percentage}% par rapport à hier",
                'icon'  => 'heroicon-m-arrow-trending-down',
                'color' => 'danger',
            ];
        }

        return [
            'label' => 'Identique à hier',
            'icon'  => 'heroicon-m-minus',
            'color' => 'gray',
        ];
    }
}
