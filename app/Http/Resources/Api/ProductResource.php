<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'barcode'     => $this->barcode,
            'sell_price'  => (float) $this->sell_price,
            'buy_price'   => (float) $this->buy_price,
            'stock_qty'   => (float) $this->stock_qty,
            'stock_alert' => (float) $this->stock_alert,
            'is_active'   => $this->is_active,
            'category_id' => $this->category_id,
            'unit_id'     => $this->unit_id,
            'unit'        => $this->whenLoaded('unit', fn() => [
                'id'           => $this->unit->id,
                'name'         => $this->unit->name,
                'abbreviation' => $this->unit->abbreviation,
            ]),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
