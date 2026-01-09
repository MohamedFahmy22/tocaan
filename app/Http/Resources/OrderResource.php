<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for Order.
 *
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'total_amount' => (float) $this->total_amount,
            'total_amount_formatted' => '$' . number_format((float) $this->total_amount, 2),
            'notes' => $this->notes,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->when(!$this->relationLoaded('items'), function () {
                return $this->items()->count();
            }),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'payments_count' => $this->when(!$this->relationLoaded('payments'), function () {
                return $this->payments()->count();
            }),
            'has_successful_payment' => $this->hasSuccessfulPayment(),
            'user' => new UserResource($this->whenLoaded('user')),
            'user_id' => $this->user_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Include actions/permissions based on status
            'can' => [
                'update' => $this->isPending(),
                'delete' => $this->isPending() && !$this->hasPayments(),
                'confirm' => $this->isPending(),
                'cancel' => !$this->isCancelled() && !$this->hasSuccessfulPayment(),
                'pay' => $this->isConfirmed() && !$this->hasSuccessfulPayment(),
            ],
        ];
    }
}
