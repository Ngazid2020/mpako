<?php

namespace App\Traits;

use Filament\Facades\Filament;

trait HasShieldPermissionPages
{
    public static function canAccess(): bool
    {
        // Cacher dans tout panel autre que Commerce
        if (Filament::getCurrentPanel()?->getId() !== 'commerce') {
            return false;
        }

        // Cacher si pas de tenant (sécurité)
        if (!Filament::getTenant()) {
            return false;
        }

        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Vérifier la permission Shield
        $className  = class_basename(static::class);
        $permission = "page_{$className}";

        return $user->can($permission);
    }
}