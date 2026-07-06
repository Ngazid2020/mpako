<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PurchaseResource;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shop      = $request->attributes->get('shop');
        $purchases = $shop->purchases()->with(['supplier', 'items'])->latest()->limit(100)->get();

        return response()->json(PurchaseResource::collection($purchases));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id'          => 'nullable|integer',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer',
            'items.*.product_name' => 'required|string',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.unit_cost'    => 'required|numeric|min:0',
            'items.*.subtotal'     => 'required|numeric|min:0',
            'total_amount'         => 'required|numeric|min:0',
            'note'                 => 'nullable|string|max:500',
        ]);

        $shop = $request->attributes->get('shop');
        $user = $request->user();

        $purchase = DB::transaction(function () use ($request, $shop, $user) {
            $purchase = Purchase::create([
                'shop_id'        => $shop->id,
                'user_id'        => $user->id,
                'supplier_id'    => $request->supplier_id,
                'reference'      => Purchase::generateReference($shop->id),
                'status'         => 'pending',
                'payment_status' => 'unpaid',
                'total_amount'   => $request->total_amount,
                'paid_amount'    => 0,
                'debt_amount'    => $request->total_amount,
                'note'           => $request->note,
            ]);

            foreach ($request->items as $item) {
                PurchaseItem::create([
                    'purchase_id'  => $purchase->id,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity'     => $item['quantity'],
                    'unit_cost'    => $item['unit_cost'],
                    'subtotal'     => $item['subtotal'],
                ]);
            }

            return $purchase;
        });

        return response()->json(new PurchaseResource($purchase->load(['supplier', 'items'])), 201);
    }
}
