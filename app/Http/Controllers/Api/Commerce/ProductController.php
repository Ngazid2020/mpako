<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Api\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use InteractsWithShop;

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAny($request, 'view_any_product');

        $shop     = $request->attributes->get('shop');
        $products = $shop->products()->with('unit')->where('is_active', true)->get();

        return response()->json(ProductResource::collection($products));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAny($request, 'create_product');

        $shop = $this->shop($request);
        $user = $request->user();

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'sell_price'  => 'required|numeric|min:0',
            'buy_price'   => 'nullable|numeric|min:0',
            'barcode'     => 'nullable|string|max:255',
            'stock_qty'   => 'nullable|numeric|min:0',
            'stock_alert' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'unit_id'     => ['nullable', 'integer', $this->inShop('units', $shop)],
            'category_id' => ['nullable', 'integer', $this->inShop('categories', $shop)],
        ]);

        $product = DB::transaction(function () use ($shop, $user, $validated) {
        $product = $shop->products()->create([
            'name'        => $validated['name'],
            'sell_price'  => $validated['sell_price'],
            'buy_price'   => $validated['buy_price'] ?? 0,
            'barcode'     => $validated['barcode'] ?? null,
            // Le stock initial passe par un StockMovement (traçabilité) — l'observer met stock_qty à jour
            'stock_qty'   => 0,
            'stock_alert' => $validated['stock_alert'] ?? 0,
            'description' => $validated['description'] ?? null,
            'unit_id'     => $validated['unit_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'is_active'   => true,
        ]);

        if (($validated['stock_qty'] ?? 0) > 0) {
            StockMovement::create([
                'shop_id'    => $shop->id,
                'product_id' => $product->id,
                'user_id'    => $user->id,
                'type'       => 'in',
                'quantity'   => $validated['stock_qty'],
                'reason'     => 'Stock initial',
            ]);
        }

            return $product->fresh();
        });

        return response()->json(new ProductResource($product->load('unit')), 201);
    }
}
