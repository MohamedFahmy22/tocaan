<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

/**
 * Data Transfer Object for Payment data.
 *
 * Immutable object for transferring payment data between layers.
 */
readonly class PaymentDTO
{
    /**
     * @param int|null $id Payment ID (null for new payments)
     * @param int $orderId Associated order ID
     * @param string|null $paymentNumber Unique payment number
     * @param PaymentMethod $gateway Payment gateway/method
     * @param float $amount Payment amount
     * @param PaymentStatus $status Payment status
     * @param string|null $gatewayTransactionId External transaction ID
     * @param array<string, mixed>|null $metadata Additional payment metadata
     */
    public function __construct(
        public ?int $id,
        public int $orderId,
        public ?string $paymentNumber,
        public PaymentMethod $gateway,
        public float $amount,
        public PaymentStatus $status,
        public ?string $gatewayTransactionId = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Create DTO from validated request data.
     *
     * @param array<string, mixed> $data Validated request data
     * @param int $orderId Order ID
     * @param float $amount Payment amount
     */
    public static function fromRequest(array $data, int $orderId, float $amount): self
    {
        return new self(
            id: null,
            orderId: $orderId,
            paymentNumber: null,
            gateway: PaymentMethod::from($data['payment_method']),
            amount: $amount,
            status: PaymentStatus::PENDING,
            gatewayTransactionId: null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Create a new DTO with updated status and transaction ID.
     */
    public function withResult(PaymentStatus $status, ?string $transactionId = null): self
    {
        return new self(
            id: $this->id,
            orderId: $this->orderId,
            paymentNumber: $this->paymentNumber,
            gateway: $this->gateway,
            amount: $this->amount,
            status: $status,
            gatewayTransactionId: $transactionId ?? $this->gatewayTransactionId,
            metadata: $this->metadata,
        );
    }

    /**
     * Convert DTO to array for model creation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'payment_number' => $this->paymentNumber,
            'gateway' => $this->gateway->value,
            'amount' => $this->amount,
            'status' => $this->status->value,
            'gateway_transaction_id' => $this->gatewayTransactionId,
            'metadata' => $this->metadata,
        ];
    }
}
