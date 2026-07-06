<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'reference'      => $this->reference,
            'supplier_id'    => $this->supplier_id,
            'supplier'       => $this->when(
                $this->relationLoaded('supplier') && $this->supplier !== null,
                fn() => [
                    'id'   => $this->supplier->id,
                    'name' => $this->supplier->name,
                ]
            ),
            'status'         => $this->status,
            'payment_status' => $this->payment_status,
            'total_amount'   => (float) $this->total_amount,
            'paid_amount'    => (float) $this->paid_amount,
            'debt_amount'    => (float) $this->debt_amount,
            'items'          => PurchaseItemResource::collection($this->whenLoaded('items')),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
