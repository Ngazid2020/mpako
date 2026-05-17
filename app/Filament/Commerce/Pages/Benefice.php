<?php

namespace App\Filament\Commerce\Pages;

use Filament\Pages\Page;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Benefice extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Bénéfice';
    protected static ?string $title           = '📈 Bénéfice';
    protected static ?string $navigationGroup = null;
    protected static ?int    $navigationSort  = 2;

    protected static string $view = 'filament.commerce.pages.benefice';

    // ─────────────────────────────────────────────
    // FILTRES DE PÉRIODE
    // ─────────────────────────────────────────────

    public string $period = 'month';  // today | week | month | year | custom
    public string $month;             // Format : Y-m (ex: 2024-05)
    public string $year;              // Format : Y   (ex: 2024)

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->year  = now()->format('Y');
    }

    // ─────────────────────────────────────────────
    // DATES DE LA PÉRIODE SÉLECTIONNÉE
    // ─────────────────────────────────────────────

    public function getPeriodDates(): array
    {
        return match($this->period) {
            'today' => [
                'start' => today(),
                'end'   => today(),
                'label' => "Aujourd'hui — " . today()->format('d/m/Y'),
            ],
            'week' => [
                'start' => now()->startOfWeek(),
                'end'   => now()->endOfWeek(),
                'label' => 'Cette semaine — '
                    . now()->startOfWeek()->format('d/m')
                    . ' au '
                    . now()->endOfWeek()->format('d/m/Y'),
            ],
            'year' => [
                'start' => Carbon::parse($this->year)->startOfYear(),
                'end'   => Carbon::parse($this->year)->endOfYear(),
                'label' => 'Année ' . $this->year,
            ],
            default => [ // month
                'start' => Carbon::parse($this->month)->startOfMonth(),
                'end'   => Carbon::parse($this->month)->endOfMonth(),
                'label' => Carbon::parse($this->month)->translatedFormat('F Y'),
            ],
        };
    }

    // ─────────────────────────────────────────────
    // CALCULS PRINCIPAUX
    // ─────────────────────────────────────────────

    /**
     * Total des ventes sur la période
     */
    public function getTotalSales(): float
    {
        $shop  = Filament::getTenant();
        $dates = $this->getPeriodDates();

        return (float) $shop->sales()
            ->where('status', 'completed')
            ->whereBetween('created_at', [
                $dates['start']->startOfDay(),
                $dates['end']->endOfDay(),
            ])
            ->sum('total_amount');
    }

    /**
     * Total des achats validés sur la période
     */
    public function getTotalPurchases(): float
    {
        $shop  = Filament::getTenant();
        $dates = $this->getPeriodDates();

        return (float) $shop->purchases()
            ->where('status', 'completed')
            ->whereBetween('purchased_at', [
                $dates['start'],
                $dates['end'],
            ])
            ->sum('total_amount');
    }

    /**
     * Total des dépenses sur la période
     */
    public function getTotalExpenses(): float
    {
        $shop  = Filament::getTenant();
        $dates = $this->getPeriodDates();

        return (float) $shop->expenses()
            ->whereBetween('spent_at', [
                $dates['start'],
                $dates['end'],
            ])
            ->sum('amount');
    }

    /**
     * Bénéfice net = Ventes - Achats - Dépenses
     */
    public function getNetProfit(): float
    {
        return $this->getTotalSales()
            - $this->getTotalPurchases()
            - $this->getTotalExpenses();
    }

    /**
     * Marge bénéficiaire en %
     */
    public function getMarginPercent(): float
    {
        $sales = $this->getTotalSales();

        if ($sales <= 0) return 0;

        return round(($this->getNetProfit() / $sales) * 100, 2);
    }

    // ─────────────────────────────────────────────
    // DONNÉES DÉTAILLÉES
    // ─────────────────────────────────────────────

    /**
     * Ventes regroupées par jour sur la période
     */
    public function getSalesByDay(): Collection
    {
        $shop  = Filament::getTenant();
        $dates = $this->getPeriodDates();

        return $shop->sales()
            ->where('status', 'completed')
            ->whereBetween('created_at', [
                $dates['start']->startOfDay(),
                $dates['end']->endOfDay(),
            ])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Dépenses regroupées par catégorie
     */
    public function getExpensesByCategory(): Collection
    {
        $shop  = Filament::getTenant();
        $dates = $this->getPeriodDates();

        return $shop->expenses()
            ->with('category')
            ->whereBetween('spent_at', [
                $dates['start'],
                $dates['end'],
            ])
            ->get()
            ->groupBy('expense_category_id')
            ->map(function ($expenses) {
                return [
                    'name'  => $expenses->first()->category?->name ?? 'Sans catégorie',
                    'color' => $expenses->first()->category?->color ?? '#6366f1',
                    'total' => $expenses->sum('amount'),
                    'count' => $expenses->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Évolution du bénéfice sur les 6 derniers mois
     */
    public function getProfitEvolution(): array
    {
        $shop   = Filament::getTenant();
        $labels = [];
        $profits = [];

        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $start = $date->copy()->startOfMonth();
            $end   = $date->copy()->endOfMonth();

            $sales = (float) $shop->sales()
                ->where('status', 'completed')
                ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
                ->sum('total_amount');

            $purchases = (float) $shop->purchases()
                ->where('status', 'completed')
                ->whereBetween('purchased_at', [$start, $end])
                ->sum('total_amount');

            $expenses = (float) $shop->expenses()
                ->whereBetween('spent_at', [$start, $end])
                ->sum('amount');

            $labels[]  = $date->translatedFormat('M Y');
            $profits[] = round($sales - $purchases - $expenses);
        }

        return [
            'labels'  => $labels,
            'profits' => $profits,
        ];
    }

    /**
     * Nombre de ventes sur la période
     */
    public function getSalesCount(): int
    {
        $shop  = Filament::getTenant();
        $dates = $this->getPeriodDates();

        return $shop->sales()
            ->where('status', 'completed')
            ->whereBetween('created_at', [
                $dates['start']->startOfDay(),
                $dates['end']->endOfDay(),
            ])
            ->count();
    }
}