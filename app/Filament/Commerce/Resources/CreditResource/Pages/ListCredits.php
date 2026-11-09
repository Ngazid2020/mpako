<?php

namespace App\Filament\Commerce\Resources\CreditResource\Pages;

use App\Exports\CreditsExport;
use App\Filament\Commerce\Resources\CreditResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListCredits extends ListRecords
{
    protected static string $resource = CreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Exporter Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn() => Excel::download(
                    new CreditsExport(Filament::getTenant()),
                    'credits-' . now()->format('Y-m-d') . '.xlsx'
                )),
            Actions\CreateAction::make(),
        ];
    }
}
