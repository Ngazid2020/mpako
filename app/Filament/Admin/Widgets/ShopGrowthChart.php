<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Shop;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class ShopGrowthChart extends ChartWidget
{
    protected static ?string $heading = 'Croissance des commerces (30 derniers jours)';

    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Récupérer les 30 derniers jours
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);

            $count = Shop::query()
                ->whereDate('created_at', $date)
                ->count();

            $labels[] = $date->format('d M');
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Nouveaux commerces',
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }
}