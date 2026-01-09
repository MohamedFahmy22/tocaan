<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for OrderItem.
 *
 * @mixin \App\Models\OrderItem
 */
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
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'unit_price_formatted' => '$' . number_format((float) $this->unit_price, 2),
            'total_price' => (float) $this->total_price,
            'total_price_formatted' => '$' . number_format((float) $this->total_price, 2),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
