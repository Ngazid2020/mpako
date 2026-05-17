<?php

namespace App\Filament\Commerce\Resources\ProductResource\Pages;

use App\Filament\Commerce\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
