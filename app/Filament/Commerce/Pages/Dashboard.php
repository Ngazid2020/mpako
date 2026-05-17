<?php

namespace App\Filament\Commerce\Pages;

use App\Filament\Commerce\Widgets\ExpensesWidget;
use App\Filament\Commerce\Widgets\LowStockWidget;
use App\Filament\Commerce\Widgets\SalesChartWidget;
use App\Filament\Commerce\Widgets\StatsOverviewWidget;
use App\Filament\Commerce\Widgets\TopProductsWidget;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Tableau de bord';
    protected static ?string $title           = 'Tableau de bord';
    protected static ?int    $navigationSort  = 0;

    // Définir le nombre de colonnes de la grille
    public function getColumns(): int | array
    {
        return [
            'sm'  => 1,
            'md'  => 2,
            'xl'  => 2,
        ];
    }

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class, // Stats KPI — pleine largeur
            SalesChartWidget::class,
            TopProductsWidget::class,   // Top produits — colonne droite
            ExpensesWidget::class,    // Graphique — colonne gauche
            LowStockWidget::class,      // Stock bas — pleine largeur
        ];
    }
}