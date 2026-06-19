<?php

namespace App\Filament\Commerce\Resources\ProductResource\Pages;

use App\Filament\Commerce\Resources\ProductResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;


class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['shop_id'] = Filament::getTenant()->id;
        return $data;
    }
}
