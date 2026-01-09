<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Value Object representing a refund result.
 *
 * Immutable object encapsulating the result of a refund operation.
 */
readonly class RefundResult
{
    /**
     * @param bool $success Whether the refund was successful
     * @param string|null $refundId Refund identifier
     * @param string $message Human-readable result message
     * @param float $amountRefunded Amount that was refunded
     */
    public function __construct(
        public bool $success,
        public ?string $refundId,
        public string $message,
        public float $amountRefunded = 0.0,
    ) {}

    /**
     * Create a successful refund result.
     */
    public static function success(string $refundId, float $amount, string $message = 'Refund processed successfully'): self
    {
        return new self(
            success: true,
            refundId: $refundId,
            message: $message,
            amountRefunded: $amount,
        );
    }

    /**
     * Create a failed refund result.
     */
    public static function failure(string $message): self
    {
        return new self(
            success: false,
            refundId: null,
            message: $message,
        );
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'refund_id' => $this->refundId,
            'message' => $this->message,
            'amount_refunded' => $this->amountRefunded,
        ];
    }
}
