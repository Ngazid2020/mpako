<?php

namespace App\Filament\Commerce\Resources\PurchaseResource\Pages;

use App\Exports\PurchasesExport;
use App\Filament\Commerce\Resources\PurchaseResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListPurchases extends ListRecords
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Exporter Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn() => Excel::download(
                    new PurchasesExport(Filament::getTenant()),
                    'achats-' . now()->format('Y-m-d') . '.xlsx'
                )),
            Actions\CreateAction::make(),
        ];
    }
}
