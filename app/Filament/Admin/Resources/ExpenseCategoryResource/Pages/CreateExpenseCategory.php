<?php

namespace App\Filament\Admin\Resources\ExpenseCategoryResource\Pages;

use App\Filament\Admin\Resources\ExpenseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenseCategory extends CreateRecord
{
    protected static string $resource = ExpenseCategoryResource::class;
}
