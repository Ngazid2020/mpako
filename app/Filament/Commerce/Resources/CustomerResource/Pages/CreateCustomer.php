<?php

namespace App\Filament\Commerce\Resources\CustomerResource\Pages;

use App\Filament\Commerce\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
