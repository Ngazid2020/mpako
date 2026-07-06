<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\StockMovementResource;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function movements(Request $request): JsonResponse
    {
        $shop      = $request->attributes->get('shop');
        $movements = $shop->stockMovements()->with('product')->latest()->limit(100)->get();

        return response()->json(StockMovementResource::collection($movements));
    }

    public function adjust(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity'   => 'required|numeric|min:0.01',
            'type'       => 'required|in:in,out,adjustment',
            'reason'     => 'nullable|string|max:255',
        ]);

        $shop = $request->attributes->get('shop');
        $user = $request->user();

        $product = $shop->products()->findOrFail($request->product_id);

        StockMovement::create([
            'shop_id'    => $shop->id,
            'product_id' => $request->product_id,
            'user_id'    => $user->id,
            'type'       => $request->type,
            'quantity'   => $request->quantity,
            'reason'     => $request->reason ?? 'Ajustement mobile',
        ]);

        // StockMovementObserver handles stock_before, stock_after, and product.stock_qty update.
        $product->refresh();

        return response()->json([
            'message'   => 'Mouvement de stock enregistré.',
            'product'   => [
                'id'        => $product->id,
                'name'      => $product->name,
                'stock_qty' => (float) $product->stock_qty,
            ],
        ], 201);
    }
}
