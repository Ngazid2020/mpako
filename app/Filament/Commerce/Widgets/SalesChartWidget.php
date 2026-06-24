<?php

namespace App\Filament\Commerce\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class SalesChartWidget extends ChartWidget
{
    protected static ?string $heading     = '📈 Ventes des 7 derniers jours';
    protected static ?int    $sort        = 2;
    protected static ?string $pollingInterval = '60s';
    protected int | string | array $columnSpan = 1;
    // Hauteur du graphique
    protected static ?string $maxHeight = '250px';

    // Filtre de période (optionnel — Filament gère l'UI)
    public ?string $filter = '7days';

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
    protected function getFilters(): ?array
    {
        return [
            '7days'  => '7 derniers jours',
            '30days' => '30 derniers jours',
            '90days' => '3 derniers mois',
        ];
    }

    protected function getData(): array
    {
        $shop = Filament::getTenant();

        // Déterminer le nombre de jours selon le filtre
        $days = match ($this->filter) {
            '30days' => 30,
            '90days' => 90,
            default  => 7,
        };

        // Une seule requête groupée par jour
        $from     = now()->subDays($days - 1)->startOfDay();
        $salesMap = $shop->sales()
            ->where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, SUM(total_amount) as total, COUNT(*) as cnt')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $labels  = [];
        $amounts = [];
        $counts  = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date   = now()->subDays($i);
            $dayKey = $date->format('Y-m-d');
            $row    = $salesMap->get($dayKey);

            $labels[]  = $days <= 7 ? $date->translatedFormat('D d/m') : $date->format('d/m');
            $amounts[] = $row ? (float) $row->total : 0;
            $counts[]  = $row ? (int) $row->cnt : 0;
        }

        return [
            'datasets' => [
                [
                    'label'           => 'CA (KMF)',
                    'data'            => $amounts,
                    'borderColor'     => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.4, // Courbe lissée
                    'pointRadius'     => 4,
                    'pointHoverRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Graphique en courbe
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false, // Masquer la légende
                ],
                'tooltip' => [
                    'callbacks' => [
                        // Formater le tooltip en KMF
                        // (fait côté JS, Filament le gère nativement)
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => [
                        'stepSize' => 1000,
                    ],
                ],
            ],
        ];
    }
}
