<?php

namespace App\Filament\Commerce\Resources\SaleResource\Pages;

use App\Exports\SalesExport;
use App\Filament\Commerce\Resources\SaleResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Exporter Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn() => Excel::download(
                    new SalesExport(Filament::getTenant()),
                    'ventes-' . now()->format('Y-m-d') . '.xlsx'
                )),
            Actions\CreateAction::make(),
        ];
    }
}
