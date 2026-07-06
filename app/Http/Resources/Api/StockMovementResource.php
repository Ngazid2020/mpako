<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'product_id'   => $this->product_id,
            'product_name' => $this->when(
                $this->relationLoaded('product'),
                fn () => $this->product?->name
            ),
            'type'         => $this->type,
            'quantity'     => (float) $this->quantity,
            'stock_before' => (float) $this->stock_before,
            'stock_after'  => (float) $this->stock_after,
            'reason'       => $this->reason,
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
