<?php

namespace App\Http\Middleware\Api;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveShop
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('shop');

        $shop = Shop::where('slug', $slug)->where('is_active', true)->first();

        if (! $shop) {
            return response()->json(['message' => 'Commerce introuvable.'], 404);
        }

        $user = $request->user();

        if (! $user->is_admin) {
            // L'approbation est re-vérifiée à chaque requête : un compte désactivé
            // ne doit pas pouvoir continuer à utiliser un token encore valide (30 j).
            if (! $user->is_approved) {
                return response()->json(['message' => 'Compte en attente de validation ou désactivé.'], 403);
            }

            if (! $user->shops()->where('shops.id', $shop->id)->exists()) {
                return response()->json(['message' => 'Accès non autorisé à ce commerce.'], 403);
            }
        }

        $request->attributes->set('shop', $shop);

        return $next($request);
    }
}
