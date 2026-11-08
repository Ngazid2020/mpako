<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Mode hors ligne de la plateforme web.
 *
 * La page est une application autonome (Alpine.js + IndexedDB) mise en cache par le
 * service worker : elle reste utilisable sans connexion. Toutes les écritures passent
 * par la file d'opérations de la sync (`POST /api/shop/{slug}/push`), exactement comme
 * l'application mobile, puis l'état est rafraîchi par `GET /pull?light=1`.
 */
class OfflineController extends Controller
{
    /** Durée de validité du token d'un poste hors ligne */
    private const TOKEN_TTL_DAYS = 30;

    public function show(Request $request, string $shop): View
    {
        [$shopModel, $user] = $this->authorizeShop($request, $shop);

        return view('offline.app', [
            'shop'        => $shopModel,
            'user'        => $user,
            'permissions' => $this->permissionsFor($user),
            'apiBase'     => url("/api/shop/{$shopModel->slug}"),
            'panelUrl'    => url("/commerce/{$shopModel->slug}"),
            'tokenUrl'    => route('commerce.offline.token', ['shop' => $shopModel->slug]),
        ]);
    }

    /**
     * Délivre un token Sanctum pour ce poste (navigateur). Un seul token actif
     * par utilisateur, commerce et poste : l'ancien est révoqué.
     */
    public function token(Request $request, string $shop): JsonResponse
    {
        [$shopModel, $user] = $this->authorizeShop($request, $shop);

        $data = $request->validate([
            'device_id' => 'required|string|max:64',
        ]);

        $name = 'web-offline:' . $shopModel->id . ':' . Str::slug($data['device_id']);

        $user->tokens()->where('name', $name)->delete();

        $expiresAt = now()->addDays(self::TOKEN_TTL_DAYS);
        $token     = $user->createToken($name, ['*'], $expiresAt);

        return response()->json([
            'token'       => $token->plainTextToken,
            'expires_at'  => $expiresAt->toISOString(),
            'user'        => ['id' => $user->id, 'name' => $user->name],
            'permissions' => $this->permissionsFor($user),
        ]);
    }

    /**
     * @return array{0: Shop, 1: User}
     */
    private function authorizeShop(Request $request, string $slug): array
    {
        /** @var User $user */
        $user = $request->user();
        $shop = Shop::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $allowed = $user->is_admin
            || ($user->is_approved && $user->shops()->where('shops.id', $shop->id)->exists());

        abort_unless($allowed, 403);

        return [$shop, $user];
    }

    /**
     * Permissions transmises à l'interface (affichage seulement : le serveur revérifie tout).
     */
    private function permissionsFor(User $user): array
    {
        return $user->is_admin ? ['*'] : $user->getAllPermissions()->pluck('name')->values()->all();
    }
}
