<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
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
}
