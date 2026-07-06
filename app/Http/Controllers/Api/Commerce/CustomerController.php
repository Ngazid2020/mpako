<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CustomerResource;
use App\Http\Resources\Api\CreditResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shop      = $request->attributes->get('shop');
        $customers = $shop->customers()->where('is_active', true)->get();

        return response()->json(CustomerResource::collection($customers));
    }

    public function store(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $customer = $shop->customers()->create([
            'name'      => $validated['name'],
            'phone'     => $validated['phone'] ?? null,
            'address'   => $validated['address'] ?? null,
            'balance'   => 0,
            'is_active' => true,
        ]);

        return response()->json(new CustomerResource($customer), 201);
    }

    public function credits(Request $request, string $shopSlug, int $customerId): JsonResponse
    {
        $shop     = $request->attributes->get('shop');
        $customer = $shop->customers()->findOrFail($customerId);
        $credits  = $customer->credits()->latest()->get();

        return response()->json(CreditResource::collection($credits));
    }
}
