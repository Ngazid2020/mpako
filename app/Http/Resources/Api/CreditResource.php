<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'reference'        => $this->reference,
            'customer_id'      => $this->customer_id,
            'sale_id'          => $this->sale_id,
            'status'           => $this->status,
            'total_amount'     => (float) $this->total_amount,
            'paid_amount'      => (float) $this->paid_amount,
            'remaining_amount' => (float) $this->remaining_amount,
            'due_date'         => $this->due_date?->toDateString(),
            'description'      => $this->description,
            'note'             => $this->note,
            'is_overdue'       => $this->isOverdue(),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
