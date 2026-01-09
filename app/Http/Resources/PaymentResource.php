<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for Payment.
 *
 * @mixin \App\Models\Payment
 */
class PaymentResource extends JsonResource
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
            'payment_number' => $this->payment_number,
            'gateway' => $this->gateway,
            'gateway_display_name' => $this->getPaymentMethod()?->label() ?? ucfirst($this->gateway),
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'amount' => (float) $this->amount,
            'amount_formatted' => '$' . number_format((float) $this->amount, 2),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_successful' => $this->isSuccessful(),
            'is_pending' => $this->isPending(),
            'is_failed' => $this->isFailed(),
            'metadata' => $this->when($this->metadata, $this->metadata),
            'order' => new OrderResource($this->whenLoaded('order')),
            'order_id' => $this->order_id,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
