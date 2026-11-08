<?php

namespace App\Filament\Commerce\Resources\PurchaseResource\Pages;

use App\Filament\Commerce\Resources\PurchaseResource;
use App\Models\Purchase;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    /** L'utilisateur a demandé "Valider et mettre à jour le stock" à la création */
    protected bool $completeAfterCreate = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $shop = Filament::getTenant();

        $data['shop_id']   = $shop->id;
        $data['user_id']   = auth()->id();
        $data['reference'] = Purchase::generateReference($shop->id);

        // Filament enregistre le repeater `items` APRÈS l'achat : si on créait directement
        // en 'completed', le PurchaseObserver ne verrait aucune ligne (stock jamais mis à jour).
        // On crée donc en 'pending' puis on valide dans afterCreate().
        $this->completeAfterCreate = ($data['status'] ?? 'pending') === 'completed';
        $data['status'] = 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Purchase $purchase */
        $purchase = $this->record->refresh();

        // Totaux recalculés depuis les lignes réellement enregistrées
        $purchase->syncTotalsFromItems();

        if ($this->completeAfterCreate) {
            // → PurchaseObserver::updated : stock, buy_price, supplier.balance
            $purchase->update(['status' => 'completed']);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
