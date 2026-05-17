<?php

namespace App\Filament\Commerce\Resources\StockMovementResource\Pages;

use App\Filament\Commerce\Resources\StockMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
