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

        if (! $user->shops()->where('shops.id', $shop->id)->exists() && ! $user->is_admin) {
            return response()->json(['message' => 'Accès non autorisé à ce commerce.'], 403);
        }

        $request->attributes->set('shop', $shop);

        return $next($request);
    }
}
