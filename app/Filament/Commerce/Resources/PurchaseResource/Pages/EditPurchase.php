<?php

namespace App\Filament\Commerce\Resources\PurchaseResource\Pages;

use App\Filament\Commerce\Resources\PurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    protected bool $completeAfterSave = false;

    protected function getHeaderActions(): array
    {
        return [
            // Seuls les achats en attente/annulés sont supprimables (cf. PurchasePolicy)
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->record->status === 'completed'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Même raison que CreatePurchase : les lignes modifiées sont enregistrées après
        // l'achat ; la validation doit se faire une fois les nouvelles lignes en base.
        if ($this->record->status === 'pending' && ($data['status'] ?? null) === 'completed') {
            $this->completeAfterSave = true;
            $data['status'] = 'pending';
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $purchase = $this->record->refresh();

        if ($purchase->status === 'pending') {
            $purchase->syncTotalsFromItems();
        }

        if ($this->completeAfterSave) {
            $purchase->update(['status' => 'completed']);
        }
    }
}
