<?php

namespace App\Filament\Commerce\Resources\CreditResource\Pages;

use App\Filament\Commerce\Resources\CreditResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCredit extends EditRecord
{
    protected static string $resource = CreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
