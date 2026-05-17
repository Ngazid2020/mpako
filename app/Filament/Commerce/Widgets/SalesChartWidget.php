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
        $days = match($this->filter) {
            '30days' => 30,
            '90days' => 90,
            default  => 7,
        };

        // Construire les données jour par jour
        $labels  = [];
        $amounts = [];
        $counts  = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);

            // Label de la date
            $labels[] = $days <= 7
                ? $date->translatedFormat('D d/m')  // Ex: "Lun 13/05"
                : $date->format('d/m');              // Format court pour 30j+

            // CA de ce jour
            $amounts[] = (float) $shop->sales()
                ->whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('total_amount');

            // Nombre de ventes de ce jour
            $counts[] = $shop->sales()
                ->whereDate('created_at', $date)
                ->where('status', 'completed')
                ->count();
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
                    'pointHoverRadius'=> 6,
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