<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base exception for order-related errors.
 */
class OrderException extends Exception
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
     * Create exception for order not found.
     */
    public static function notFound(int $orderId): self
    {
        return new self(
            message: "Order with ID {$orderId} not found",
            code: Response::HTTP_NOT_FOUND,
        );
    }

    /**
     * Create exception for deletion with payments.
     */
    public static function hasPayments(): self
    {
        return new self(
            message: 'Cannot delete order with associated payments',
            code: Response::HTTP_CONFLICT,
        );
    }

    /**
     * Create exception for invalid status transition.
     */
    public static function invalidStatusTransition(string $from, string $to): self
    {
        return new self(
            message: "Cannot transition order from '{$from}' to '{$to}'",
            code: Response::HTTP_UNPROCESSABLE_ENTITY,
            context: ['from' => $from, 'to' => $to],
        );
    }
}
