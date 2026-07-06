<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'purchase_id'  => $this->purchase_id,
            'product_id'   => $this->product_id,
            'product_name' => $this->product_name,
            'quantity'     => (float) $this->quantity,
            'unit_cost'    => (float) $this->unit_cost,
            'subtotal'     => (float) $this->subtotal,
        ];
    }
}
