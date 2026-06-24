<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ─────────────────────────────────────────────
    // POST /api/auth/login
    // ─────────────────────────────────────────────

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone'    => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('phone', $request->phone)->first();

        // Timing-safe : toujours vérifier le hash même si l'user n'existe pas
        $passwordValid = $user && Hash::check($request->password, $user->password);

        if (! $passwordValid) {
            throw ValidationException::withMessages([
                'phone' => ['Identifiants incorrects.'],
            ]);
        }

        if (! $user->is_approved) {
            throw ValidationException::withMessages([
                'phone' => ['Compte en attente de validation.'],
            ]);
        }

        $user->load('shops');

        $token = $user->createToken(
            name: 'mobile-app',
            expiresAt: now()->addDays(30)
        )->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'phone' => $user->phone,
            ],
            'shops' => $user->shops->filter(fn ($shop) => $shop->is_active)->values()->map(fn ($shop) => [
                'id'       => $shop->id,
                'name'     => $shop->name,
                'slug'     => $shop->slug,
                'island'   => $shop->island,
                'city'     => $shop->city,
                'currency' => $shop->currency,
            ]),
        ]);
    }

    // ─────────────────────────────────────────────
    // POST /api/auth/logout
    // ─────────────────────────────────────────────

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',
        ]);
    }

    // ─────────────────────────────────────────────
    // GET /api/auth/me
    // ─────────────────────────────────────────────

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('shops');

        return response()->json([
            'success' => true,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'phone' => $user->phone,
            ],
            'shops' => $user->shops->filter(fn ($shop) => $shop->is_active)->values()->map(fn ($shop) => [
                'id'       => $shop->id,
                'name'     => $shop->name,
                'slug'     => $shop->slug,
                'island'   => $shop->island,
                'city'     => $shop->city,
                'currency' => $shop->currency,
            ]),
        ]);
    }
}
