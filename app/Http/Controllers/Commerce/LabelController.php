<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function print(Shop $shop, Request $request)
    {
        abort_unless(
            auth()->user()->shops()->where('shops.id', $shop->id)->exists(),
            403
        );

        $rawIds = $request->query('ids', '');
        $ids = array_values(array_filter(array_map('intval', explode(',', $rawIds))));

        abort_if(empty($ids), 404);

        $products = $shop->products()
            ->whereIn('id', $ids)
            ->with('unit')
            ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
            ->get();

        abort_if($products->isEmpty(), 404);

        return view('commerce.label-print', compact('shop', 'products'));
    }
}
