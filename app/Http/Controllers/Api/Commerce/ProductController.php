<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shop     = $request->attributes->get('shop');
        $products = $shop->products()->with('unit')->where('is_active', true)->get();

        return response()->json(ProductResource::collection($products));
    }

    public function store(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');
        $user = $request->user();

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'sell_price'  => 'required|numeric|min:0',
            'buy_price'   => 'nullable|numeric|min:0',
            'barcode'     => 'nullable|string|max:255',
            'stock_qty'   => 'nullable|numeric|min:0',
            'stock_alert' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'unit_id'     => 'nullable|integer|exists:units,id',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $product = $shop->products()->create([
            'name'        => $validated['name'],
            'sell_price'  => $validated['sell_price'],
            'buy_price'   => $validated['buy_price'] ?? 0,
            'barcode'     => $validated['barcode'] ?? null,
            'stock_qty'   => $validated['stock_qty'] ?? 0,
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

        return response()->json(new ProductResource($product->load('unit')), 201);
    }
}
