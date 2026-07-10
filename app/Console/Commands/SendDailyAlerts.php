<?php

namespace App\Console\Commands;

use App\Models\Credit;
use App\Models\Shop;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class SendDailyAlerts extends Command
{
    protected $signature   = 'mpako:send-alerts {--shop= : ID du shop spécifique (optionnel)}';
    protected $description = 'Envoie les alertes quotidiennes : stock bas, crédits en retard, dettes fournisseurs';

    public function handle(): int
    {
        $query = Shop::where('is_active', true);

        if ($shopId = $this->option('shop')) {
            $query->where('id', $shopId);
        }

        $shops = $query->with('members')->get();

        foreach ($shops as $shop) {
            $recipients = $shop->members;
            if ($recipients->isEmpty()) continue;

            $this->checkLowStock($shop, $recipients);
            $this->checkOverdueCredits($shop, $recipients);
            $this->checkSupplierDebts($shop, $recipients);

            $this->line("Shop [{$shop->name}] — alertes traitées.");
        }

        $this->info('Alertes envoyées avec succès.');
        return self::SUCCESS;
    }

    // ── Stock bas / rupture ───────────────────────────────────────────────

    private function checkLowStock(Shop $shop, $recipients): void
    {
        $outOfStock = $shop->products()
            ->where('is_active', true)
            ->where('stock_qty', '<=', 0)
            ->pluck('name')
            ->toArray();

        $lowStock = $shop->products()
            ->where('is_active', true)
            ->where('stock_qty', '>', 0)
            ->whereColumn('stock_qty', '<=', 'stock_alert')
            ->get(['name', 'stock_qty']);

        if (!empty($outOfStock)) {
            Notification::make()
                ->title('🔴 Rupture de stock')
                ->body(count($outOfStock) . ' produit(s) épuisé(s) : ' . implode(', ', array_slice($outOfStock, 0, 5)) . (count($outOfStock) > 5 ? '…' : ''))
                ->danger()
                ->actions([
                    Action::make('voir')
                        ->label('Voir les produits')
                        ->button()
                        ->url(route('filament.commerce.resources.products.index', ['tenant' => $shop->slug])),
                ])
                ->sendToDatabase($recipients);
        }

        if ($lowStock->isNotEmpty()) {
            $details = $lowStock->map(fn ($p) => "{$p->name} ({$p->stock_qty} restant)")->join(', ');

            Notification::make()
                ->title('🟠 Stock bas — ' . $shop->name)
                ->body($lowStock->count() . ' produit(s) sous le seuil d\'alerte : ' . mb_substr($details, 0, 120))
                ->warning()
                ->actions([
                    Action::make('voir')
                        ->label('Gérer les stocks')
                        ->button()
                        ->url(route('filament.commerce.resources.products.index', ['tenant' => $shop->slug])),
                ])
                ->sendToDatabase($recipients);
        }
    }

    // ── Crédits en retard ────────────────────────────────────────────────

    private function checkOverdueCredits(Shop $shop, $recipients): void
    {
        $overdue = $shop->credits()->overdue()->with('customer')->get();
        if ($overdue->isEmpty()) return;

        $totalAmount = $overdue->sum('remaining_amount');
        $names       = $overdue->take(3)->map(fn ($c) => $c->customer?->name ?? 'Client')->join(', ');
        $suffix      = $overdue->count() > 3 ? ' et ' . ($overdue->count() - 3) . ' autre(s)' : '';

        Notification::make()
            ->title('⚠️ Crédits en retard — ' . $shop->name)
            ->body(
                $overdue->count() . ' crédit(s) en retard · ' .
                number_format($totalAmount, 0, ',', ' ') . ' KMF à récupérer' .
                "\nClients : {$names}{$suffix}"
            )
            ->danger()
            ->actions([
                Action::make('voir')
                    ->label('Voir les crédits')
                    ->button()
                    ->url(route('filament.commerce.resources.credits.index', ['tenant' => $shop->slug])),
            ])
            ->sendToDatabase($recipients);
    }

    // ── Dettes fournisseurs importantes ──────────────────────────────────

    private function checkSupplierDebts(Shop $shop, $recipients): void
    {
        $threshold = 50_000;

        $suppliers = $shop->suppliers()
            ->where('balance', '>', $threshold)
            ->orderBy('balance', 'desc')
            ->get(['name', 'balance']);

        if ($suppliers->isEmpty()) return;

        $totalDebt = $suppliers->sum('balance');
        $details   = $suppliers->take(3)->map(fn ($s) => "{$s->name} (" . number_format($s->balance, 0, ',', ' ') . ' KMF)')->join(', ');

        Notification::make()
            ->title('💰 Dettes fournisseurs élevées — ' . $shop->name)
            ->body(
                $suppliers->count() . ' fournisseur(s) avec dette > ' . number_format($threshold, 0, ',', ' ') . ' KMF' .
                "\nTotal : " . number_format($totalDebt, 0, ',', ' ') . " KMF · {$details}"
            )
            ->warning()
            ->actions([
                Action::make('voir')
                    ->label('Voir les fournisseurs')
                    ->button()
                    ->url(route('filament.commerce.resources.suppliers.index', ['tenant' => $shop->slug])),
            ])
            ->sendToDatabase($recipients);
    }
}
