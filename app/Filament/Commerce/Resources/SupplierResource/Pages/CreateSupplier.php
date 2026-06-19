<?php

namespace App\Filament\Commerce\Resources\SupplierResource\Pages;

use App\Filament\Commerce\Resources\SupplierResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['shop_id'] = Filament::getTenant()->id;
        return $data;
    }
}
