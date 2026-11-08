<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Ressources du panel Admin masquées (shouldRegisterNavigation = false) qui n'existent
 * que pour que Filament-Shield génère les permissions des modèles du panel Commerce.
 *
 * Leurs routes restent accessibles par URL : on interdit toute écriture, car une
 * création/suppression ici contournerait les observers (stock, dettes, balances).
 */
trait ShieldOnlyResource
{
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }
}
