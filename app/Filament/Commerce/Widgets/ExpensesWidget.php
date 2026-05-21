<?php

namespace App\Filament\Commerce\Widgets;

use App\Traits\HasShieldPermission;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\Concerns\Has;

class ExpensesWidget extends Widget
{
    // use HasShieldPermission;
    protected static ?int    $sort            = 5;
    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 1;

    protected static string $view = 'filament.commerce.widgets.expenses-widget';

    public string $period = 'month';


    
    public function getExpensesByCategory(): Collection
    {
        $shop = Filament::getTenant();

        $query = $shop->expenses()
            ->with('category');

        // Filtre période
        $query = match ($this->period) {
            'today' => $query->whereDate('spent_at', today()),
            'week'  => $query->whereBetween('spent_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]),
            default => $query
                ->whereMonth('spent_at', now()->month)
                ->whereYear('spent_at', now()->year),
        };

        return $query->get()
            ->groupBy('expense_category_id')
            ->map(function ($expenses) {
                return [
                    'name'   => $expenses->first()->category?->name ?? 'Sans catégorie',
                    'color'  => $expenses->first()->category?->color ?? '#6366f1',
                    'total'  => $expenses->sum('amount'),
                    'count'  => $expenses->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    public function getTotalExpenses(): float
    {
        $shop  = Filament::getTenant();
        $query = $shop->expenses();

        $query = match ($this->period) {
            'today' => $query->whereDate('spent_at', today()),
            'week'  => $query->whereBetween('spent_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]),
            default => $query
                ->whereMonth('spent_at', now()->month)
                ->whereYear('spent_at', now()->year),
        };

        return (float) $query->sum('amount');
    }
}
