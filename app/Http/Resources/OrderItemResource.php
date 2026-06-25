<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'product_name' => $this->productVariant->product->name ?? null,
            'variant_name' => $this->productVariant->name ?? null,
            'quanitty' => $this->quantity,
            'price' => $this->price,
            'notes' => $this->notes,
        ];
    }
}
