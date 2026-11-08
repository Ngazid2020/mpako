<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Exists;

/**
 * Helpers communs aux contrôleurs API "commerce".
 *
 * - shop()       : récupère le Shop résolu par le middleware ResolveShop
 * - authorizeAny(): vérifie les permissions Shield/Spatie (mêmes noms que les Policies Filament)
 * - inShop()     : règle de validation `exists` restreinte au shop courant (anti fuite cross-tenant)
 */
trait InteractsWithShop
{
    protected function shop(Request $request): Shop
    {
        return $request->attributes->get('shop');
    }

    /**
     * Refuse (403) si l'utilisateur ne possède aucune des permissions données.
     * Les super-admins (is_admin) passent toujours.
     */
    protected function authorizeAny(Request $request, string ...$permissions): void
    {
        abort_unless(static::userCanAny($request->user(), $permissions), 403, 'Action non autorisée pour votre rôle.');
    }

    protected static function userCanAny(?User $user, array $permissions): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->is_admin) {
            return true;
        }

        return $user->canAny($permissions);
    }

    /**
     * Règle `exists:{table},id` limitée aux lignes du shop courant.
     */
    protected function inShop(string $table, Shop $shop): Exists
    {
        return (new Exists($table, 'id'))->where('shop_id', $shop->id);
    }
}
