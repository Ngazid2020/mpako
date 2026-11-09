<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Api\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SaleResource;
use App\Services\SaleRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    use InteractsWithShop;

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAny($request, 'view_any_sale');

        $sales = $this->shop($request)->sales()->with('items')->latest()->limit(100)->get();

        return response()->json(SaleResource::collection($sales));
    }

    public function store(Request $request, SaleRecorder $recorder): JsonResponse
    {
        $this->authorizeAny($request, 'create_sale');

        $shop = $this->shop($request);

        $data = $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => ['required', 'integer', $this->inShop('products', $shop)],
            'items.*.product_name'   => 'nullable|string',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.discount_type'  => 'nullable|in:percent,fixed',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'paid_amount'            => 'nullable|numeric|min:0',
            'discount_amount'        => 'nullable|numeric|min:0',
            'payment_type'           => 'nullable|in:cash,credit',
            'customer_id'            => ['nullable', 'required_if:payment_type,credit', 'integer', $this->inShop('customers', $shop)],
            'note'                   => 'nullable|string|max:500',
            'credit'                 => 'nullable|array',
            'credit.due_date'        => 'nullable|date',
            'credit.note'            => 'nullable|string',
        ]);

        if (($data['payment_type'] ?? 'cash') === 'credit') {
            $this->authorizeAny($request, 'create_credit');
        }

        $sale = $recorder->record($shop, $request->user(), $data, strictStock: true);

        return response()->json(new SaleResource($sale->load('items')), 201);
    }
}
