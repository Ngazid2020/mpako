<?php

namespace App\Filament\Commerce\Resources\WalletResource\Pages;

use App\Filament\Commerce\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Pas de bouton Create car le portefeuille est créé automatiquement
        ];
    }
}
