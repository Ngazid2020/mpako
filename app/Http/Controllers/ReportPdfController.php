<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class ReportPdfController extends Controller
{
    public function generate(Request $request, string $shop, string $type): Response
    {
        $shop = Shop::where('slug', $shop)->firstOrFail();

        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user || (!$user->is_admin && !$user->shops()->where('shops.id', $shop->id)->exists())) {
            abort(403);
        }

        [$view, $data, $filename] = match ($type) {
            'stock'   => $this->stockReport($shop),
            'ventes'  => $this->salesReport($shop, $request),
            'credits' => $this->creditsReport($shop),
            default   => abort(404),
        };

        $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    // ═══════════════════════════════════════════════
    // RAPPORT STOCK
    // ═══════════════════════════════════════════════

    private function stockReport(Shop $shop): array
    {
        $products = $shop->products()->with(['category', 'unit'])->orderBy('name')->get();

        $totalValue    = $products->sum(fn ($p) => $p->stock_qty * $p->buy_price);
        $alertCount    = $products->filter(fn ($p) => $p->stock_qty <= ($p->stock_alert ?? 0))->count();
        $totalProducts = $products->count();

        $topItems = $products->map(fn ($p) => [
            'label' => $p->name,
            'value' => round($p->stock_qty * $p->buy_price),
        ])->sortByDesc('value')->take(10)->values();

        $chartImg = $this->quickChart([
            'type' => 'bar',
            'data' => [
                'labels'   => $topItems->pluck('label')->toArray(),
                'datasets' => [[
                    'label'           => 'Valeur de stock (KMF)',
                    'data'            => $topItems->pluck('value')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.85)',
                    'borderColor'     => 'rgb(37, 99, 235)',
                    'borderWidth'     => 1,
                    'borderRadius'    => 4,
                ]],
            ],
            'options' => $this->barOptions('Valeur (KMF)', 'Produit'),
        ]);

        return [
            'pdf.reports.stock',
            compact('shop', 'products', 'totalValue', 'alertCount', 'totalProducts', 'chartImg'),
            "stock-{$shop->slug}-" . now()->format('Y-m-d') . '.pdf',
        ];
    }

    // ═══════════════════════════════════════════════
    // RAPPORT VENTES
    // ═══════════════════════════════════════════════

    private function salesReport(Shop $shop, Request $request): array
    {
        $period = $request->get('period', 'month');
        $month  = $request->get('month', now()->format('Y-m'));
        $year   = $request->get('year', now()->format('Y'));

        [$start, $end, $label] = $this->resolvePeriod($period, $month, $year);

        $sales = $shop->sales()
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->withCount('items')
            ->orderBy('created_at', 'desc')
            ->get();

        $completed = $sales->where('status', 'completed');
        $total     = (float) $completed->sum('total_amount');
        $count     = $completed->count();
        $avg       = $count > 0 ? round($total / $count) : 0;

        $byDay = $shop->sales()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->selectRaw('DATE(created_at) as day, SUM(total_amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $chartLabels = $byDay->pluck('day')
            ->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'))
            ->toArray();
        $chartValues = $byDay->pluck('total')
            ->map(fn ($v) => round((float) $v))
            ->toArray();

        $chartImg = $this->quickChart([
            'type' => 'line',
            'data' => [
                'labels'   => $chartLabels,
                'datasets' => [[
                    'label'           => 'Ventes completees (KMF)',
                    'data'            => $chartValues,
                    'borderColor'     => 'rgb(15, 118, 110)',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.15)',
                    'borderWidth'     => 2,
                    'fill'            => true,
                    'tension'         => 0.35,
                    'pointRadius'     => 4,
                    'pointBackgroundColor' => 'rgb(15, 118, 110)',
                ]],
            ],
            'options' => $this->lineOptions('Montant (KMF)', 'Date'),
        ]);

        return [
            'pdf.reports.ventes',
            compact('shop', 'sales', 'total', 'count', 'avg', 'label', 'chartImg'),
            "ventes-{$shop->slug}-" . now()->format('Y-m-d') . '.pdf',
        ];
    }

    // ═══════════════════════════════════════════════
    // RAPPORT CRÉDITS
    // ═══════════════════════════════════════════════

    private function creditsReport(Shop $shop): array
    {
        $credits = $shop->credits()
            ->whereIn('status', ['pending', 'partial'])
            ->with('customer')
            ->orderByDesc('remaining_amount')
            ->get();

        $totalDue     = (float) $credits->sum('remaining_amount');
        $debtors      = $credits->unique('customer_id')->count();
        $overdueCount = $credits->filter(fn ($c) => $c->isOverdue())->count();

        $topDebtors = $credits->groupBy('customer_id')
            ->map(fn ($rows) => [
                'label' => $rows->first()->customer?->name ?? 'Inconnu',
                'value' => round((float) $rows->sum('remaining_amount')),
            ])
            ->sortByDesc('value')
            ->take(10)
            ->values();

        $chartImg = $this->quickChart([
            'type' => 'bar',
            'data' => [
                'labels'   => $topDebtors->pluck('label')->toArray(),
                'datasets' => [[
                    'label'           => 'Restant du (KMF)',
                    'data'            => $topDebtors->pluck('value')->toArray(),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.85)',
                    'borderColor'     => 'rgb(185, 28, 28)',
                    'borderWidth'     => 1,
                    'borderRadius'    => 4,
                ]],
            ],
            'options' => $this->barOptions('Montant du (KMF)', 'Client'),
        ]);

        return [
            'pdf.reports.credits',
            compact('shop', 'credits', 'totalDue', 'debtors', 'overdueCount', 'chartImg'),
            "credits-{$shop->slug}-" . now()->format('Y-m-d') . '.pdf',
        ];
    }

    // ═══════════════════════════════════════════════
    // QUICKCHART
    // ═══════════════════════════════════════════════

    private function quickChart(array $config, int $w = 800, int $h = 350): string
    {
        try {
            $response = Http::timeout(20)->post('https://quickchart.io/chart', [
                'chart'           => $config,
                'width'           => $w,
                'height'          => $h,
                'format'          => 'png',
                'backgroundColor' => '#f9fafb',
                'devicePixelRatio' => 2,
            ]);

            if ($response->failed() || !str_starts_with($response->header('Content-Type') ?? '', 'image/')) {
                return '';
            }

            return 'data:image/png;base64,' . base64_encode($response->body());
        } catch (\Throwable) {
            return '';
        }
    }

    // ═══════════════════════════════════════════════
    // OPTIONS CHART.JS COMMUNES
    // ═══════════════════════════════════════════════

    private function barOptions(string $yLabel, string $xLabel): array
    {
        return [
            'responsive' => false,
            'plugins' => [
                'legend' => [
                    'display'  => true,
                    'position' => 'top',
                    'labels'   => ['color' => '#374151', 'font' => ['size' => 13]],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title'  => ['display' => true, 'text' => $yLabel, 'color' => '#6b7280', 'font' => ['size' => 12]],
                    'ticks'  => ['color' => '#6b7280', 'font' => ['size' => 11]],
                    'grid'   => ['color' => '#e5e7eb'],
                ],
                'x' => [
                    'title'  => ['display' => true, 'text' => $xLabel, 'color' => '#6b7280', 'font' => ['size' => 12]],
                    'ticks'  => ['color' => '#374151', 'font' => ['size' => 11], 'maxRotation' => 35],
                    'grid'   => ['display' => false],
                ],
            ],
        ];
    }

    private function lineOptions(string $yLabel, string $xLabel): array
    {
        return [
            'responsive' => false,
            'plugins' => [
                'legend' => [
                    'display'  => true,
                    'position' => 'top',
                    'labels'   => ['color' => '#374151', 'font' => ['size' => 13]],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title'  => ['display' => true, 'text' => $yLabel, 'color' => '#6b7280', 'font' => ['size' => 12]],
                    'ticks'  => ['color' => '#6b7280', 'font' => ['size' => 11]],
                    'grid'   => ['color' => '#e5e7eb'],
                ],
                'x' => [
                    'title'  => ['display' => true, 'text' => $xLabel, 'color' => '#6b7280', 'font' => ['size' => 12]],
                    'ticks'  => ['color' => '#374151', 'font' => ['size' => 11], 'maxRotation' => 35],
                    'grid'   => ['display' => false],
                ],
            ],
        ];
    }

    // ═══════════════════════════════════════════════
    // HELPER PÉRIODE
    // ═══════════════════════════════════════════════

    private function resolvePeriod(string $period, string $month, string $year): array
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'today' => [$now->startOfDay(),  $now->endOfDay(),  "Aujourd'hui — " . $now->format('d/m/Y')],
            'week'  => [$now->startOfWeek(), $now->endOfWeek(), 'Cette semaine'],
            'year'  => [
                CarbonImmutable::parse($year)->startOfYear(),
                CarbonImmutable::parse($year)->endOfYear(),
                "Annee {$year}",
            ],
            default => [
                CarbonImmutable::parse($month)->startOfMonth(),
                CarbonImmutable::parse($month)->endOfMonth(),
                CarbonImmutable::parse($month)->translatedFormat('F Y'),
            ],
        };
    }
}
