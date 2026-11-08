<?php

namespace App\Filament\Commerce\Resources\WalletResource\Pages;

use App\Filament\Commerce\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWallet extends EditRecord
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function afterSave(): void
    {
        $this->dispatch('notify', 
            type: 'success', 
            message: 'Portefeuille mis à jour avec succès.'
        );
    }
}
