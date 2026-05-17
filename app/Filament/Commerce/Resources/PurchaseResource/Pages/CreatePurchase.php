<?php

namespace App\Filament\Commerce\Resources\PurchaseResource\Pages;

use App\Filament\Commerce\Resources\PurchaseResource;
use App\Models\Purchase;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $shop = Filament::getTenant();

        $data['shop_id']   = $shop->id;
        $data['user_id']   = auth()->id();
        $data['reference'] = Purchase::generateReference($shop->id);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}