<?php

namespace App\Filament\Commerce\Resources\CreditResource\Pages;

use App\Filament\Commerce\Resources\CreditResource;
use App\Models\Credit;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateCredit extends CreateRecord
{
    protected static string $resource = CreditResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $shop = Filament::getTenant();

        $data['shop_id']          = $shop->id;
        $data['user_id']          = auth()->id();
        $data['reference']        = Credit::generateReference($shop->id);
        $data['paid_amount']      = 0;
        $data['remaining_amount'] = $data['total_amount'];
        $data['status']           = 'pending';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}