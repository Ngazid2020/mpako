<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'phone'      => $this->phone,
            'address'    => $this->address,
            'balance'    => (float) $this->balance,
            'is_active'  => (bool) $this->is_active,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
