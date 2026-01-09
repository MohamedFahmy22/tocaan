<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base exception for payment-related errors.
 */
class PaymentException extends Exception
{
    /**
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param array<string, mixed> $context Additional context data
     */
    public function __construct(
        string $message,
        int $code = Response::HTTP_BAD_REQUEST,
        protected array $context = [],
    ) {
        parent::__construct($message, $code);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => $this->context,
        ], $this->getCode());
    }

    /**
     * Create exception for payment not found.
     */
    public static function notFound(int $paymentId): self
    {
        return new self(
            message: "Payment with ID {$paymentId} not found",
            code: Response::HTTP_NOT_FOUND,
        );
    }

    /**
     * Create exception for order not confirmed.
     */
    public static function orderNotConfirmed(): self
    {
        return new self(
            message: 'Payments can only be processed for confirmed orders',
            code: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    /**
     * Create exception for payment processing failure.
     */
    public static function processingFailed(string $reason): self
    {
        return new self(
            message: "Payment processing failed: {$reason}",
            code: Response::HTTP_PAYMENT_REQUIRED,
            context: ['reason' => $reason],
        );
    }

    /**
     * Create exception for already paid order.
     */
    public static function alreadyPaid(): self
    {
        return new self(
            message: 'This order has already been paid',
            code: Response::HTTP_CONFLICT,
        );
    }
}
