<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'expense_category_id' => $this->expense_category_id,
            'category_name'       => $this->when(
                $this->relationLoaded('category'),
                fn () => $this->category?->name
            ),
            'description' => $this->description,
            'amount'      => (float) $this->amount,
            'spent_at'    => $this->spent_at?->toDateString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
