<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\PaymentStatus;

/**
 * Value Object representing a payment processing result.
 *
 * Immutable object encapsulating the result of a payment gateway operation.
 */
readonly class PaymentResult
{
    /**
     * @param bool $success Whether the payment was successful
     * @param string|null $transactionId Gateway transaction identifier
     * @param string $message Human-readable result message
     * @param array<string, mixed> $metadata Additional result data
     */
    public function __construct(
        public bool $success,
        public ?string $transactionId,
        public string $message,
        public array $metadata = [],
    ) {}

    /**
     * Create a successful payment result.
     */
    public static function success(string $transactionId, string $message = 'Payment processed successfully'): self
    {
        return new self(
            success: true,
            transactionId: $transactionId,
            message: $message,
        );
    }

    /**
     * Create a failed payment result.
     */
    public static function failure(string $message, array $metadata = []): self
    {
        return new self(
            success: false,
            transactionId: null,
            message: $message,
            metadata: $metadata,
        );
    }

    /**
     * Get the corresponding payment status.
     */
    public function getStatus(): PaymentStatus
    {
        return $this->success ? PaymentStatus::SUCCESSFUL : PaymentStatus::FAILED;
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
            'transaction_id' => $this->transactionId,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];
    }
}
