<?php

namespace App\Traits;

use Filament\Facades\Filament;

trait HasShieldPermission
{
    public static function canView(): bool
    {
        // Cacher dans le panel admin (ne sert qu'à Shield)
        if (Filament::getCurrentPanel()?->getId() === 'admin') {
            return false;
        }

        $user = auth()->user();

        if (!$user) {
            return false;
        }

        $className  = class_basename(static::class);
        $permission = "widget_{$className}";

        return $user->can($permission);
    }
}