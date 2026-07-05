<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'reference'     => $this->reference,
            'status'        => $this->status,
            'payment_type'  => $this->payment_type,
            'total_amount'  => (float) $this->total_amount,
            'paid_amount'   => (float) $this->paid_amount,
            'change_amount' => (float) $this->change_amount,
            'customer_id'   => $this->customer_id,
            'note'          => $this->note,
            'items'         => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity'     => (float) $item->quantity,
                    'unit_price'   => (float) $item->unit_price,
                    'subtotal'     => (float) $item->subtotal,
                ])
            ),
            'updated_at'    => $this->updated_at?->toISOString(),
        ];
    }
}
