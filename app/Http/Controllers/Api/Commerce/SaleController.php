<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SaleResource;
use App\Models\Credit;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shop  = $request->attributes->get('shop');
        $sales = $shop->sales()->with('items')->latest()->limit(100)->get();

        return response()->json(SaleResource::collection($sales));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|integer',
            'items.*.product_name'   => 'required|string',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.subtotal'       => 'required|numeric|min:0',
            'total_amount'           => 'required|numeric|min:0',
            'paid_amount'            => 'required|numeric|min:0',
            'change_amount'          => 'nullable|numeric|min:0',
            'payment_type'           => 'nullable|in:cash,credit',
            'customer_id'            => 'nullable|integer',
            'note'                   => 'nullable|string|max:500',
            'credit'                 => 'nullable|array',
            'credit.due_date'        => 'nullable|date',
            'credit.note'            => 'nullable|string',
        ]);

        $shop = $request->attributes->get('shop');
        $user = $request->user();

        $sale = DB::transaction(function () use ($request, $shop, $user) {
            // Vérifier stocks
            foreach ($request->items as $item) {
                $product = $shop->products()->lockForUpdate()->findOrFail($item['product_id']);
                if ($product->stock_qty < $item['quantity']) {
                    throw new \RuntimeException("Stock insuffisant pour « {$product->name} ».");
                }
            }

            $sale = Sale::create([
                'shop_id'       => $shop->id,
                'user_id'       => $user->id,
                'customer_id'   => $request->customer_id,
                'payment_type'  => $request->payment_type ?? 'cash',
                'reference'     => Sale::generateReference($shop->id),
                'status'        => 'completed',
                'total_amount'  => $request->total_amount,
                'paid_amount'   => $request->paid_amount,
                'change_amount' => $request->change_amount ?? 0,
                'note'          => $request->note,
            ]);

            foreach ($request->items as $item) {
                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'subtotal'     => $item['subtotal'],
                ]);

                StockMovement::create([
                    'shop_id'    => $shop->id,
                    'product_id' => $item['product_id'],
                    'user_id'    => $user->id,
                    'type'       => 'out',
                    'quantity'   => $item['quantity'],
                    'reason'     => "Vente {$sale->reference}",
                ]);
            }

            // Vente à crédit
            if ($request->payment_type === 'credit' && $request->customer_id) {
                Credit::create([
                    'shop_id'          => $shop->id,
                    'sale_id'          => $sale->id,
                    'customer_id'      => $request->customer_id,
                    'user_id'          => $user->id,
                    'reference'        => Credit::generateReference($shop->id),
                    'status'           => 'pending',
                    'total_amount'     => $request->total_amount,
                    'paid_amount'      => 0,
                    'remaining_amount' => $request->total_amount,
                    'due_date'         => $request->input('credit.due_date'),
                    'description'      => "Vente à crédit {$sale->reference}",
                    'note'             => $request->input('credit.note'),
                ]);
            }

            return $sale;
        });

        return response()->json(new SaleResource($sale->load('items')), 201);
    }
}
