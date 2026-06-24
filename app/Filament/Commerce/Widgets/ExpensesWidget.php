<?php

namespace App\Filament\Commerce\Widgets;

use App\Traits\HasShieldPermission;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class ExpensesWidget extends Widget
{
    use HasShieldPermission;
    protected static ?int    $sort            = 5;
    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 1;

    protected static string $view = 'filament.commerce.widgets.expenses-widget';

    public string $period = 'month';


    
    public function getExpensesByCategory(): Collection
    {
        $shop = Filament::getTenant();

        $query = $shop->expenses()
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw('expenses.expense_category_id, expense_categories.name as cat_name, expense_categories.color as cat_color, SUM(expenses.amount) as total, COUNT(*) as cnt')
            ->groupBy('expenses.expense_category_id', 'expense_categories.name', 'expense_categories.color');

        $query = match ($this->period) {
            'today' => $query->whereDate('expenses.spent_at', today()),
            'week'  => $query->whereBetween('expenses.spent_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]),
            default => $query
                ->whereMonth('expenses.spent_at', now()->month)
                ->whereYear('expenses.spent_at', now()->year),
        };

        return $query->get()
            ->map(fn ($row) => [
                'name'  => $row->cat_name  ?? 'Sans catégorie',
                'color' => $row->cat_color ?? '#6366f1',
                'total' => (float) $row->total,
                'count' => (int) $row->cnt,
            ])
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
