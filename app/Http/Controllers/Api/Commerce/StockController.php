<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Api\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\StockMovementResource;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    use InteractsWithShop;

    public function movements(Request $request): JsonResponse
    {
        $this->authorizeAny($request, 'view_any_stock::movement');

        $shop      = $request->attributes->get('shop');
        $movements = $shop->stockMovements()->with('product')->latest()->limit(100)->get();

        return response()->json(StockMovementResource::collection($movements));
    }

    public function adjust(Request $request): JsonResponse
    {
        $this->authorizeAny($request, 'create_stock::movement');

        $request->validate([
            'product_id' => 'required|integer',
            'type'       => 'required|in:in,out,adjustment',
            // adjustment = nouvelle quantité absolue (0 autorisé) ; in/out = quantité > 0
            'quantity'   => ['required', 'numeric', $request->input('type') === 'adjustment' ? 'min:0' : 'min:0.01'],
            'reason'     => 'nullable|string|max:255',
        ]);

        $shop = $this->shop($request);
        $user = $request->user();

        $product = DB::transaction(function () use ($request, $shop, $user) {
            $product = $shop->products()->lockForUpdate()->findOrFail($request->product_id);

            StockMovement::create([
                'shop_id'    => $shop->id,
                'product_id' => $product->id,
                'user_id'    => $user->id,
                'type'       => $request->type,
                'quantity'   => $request->quantity,
                'reason'     => $request->reason ?? 'Ajustement mobile',
            ]);

            return $product;
        });

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
