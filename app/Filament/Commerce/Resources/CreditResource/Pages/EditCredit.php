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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['remaining_amount'] = max(0, (float) $data['total_amount'] - (float) $this->record->paid_amount);
        return $data;
    }
}
