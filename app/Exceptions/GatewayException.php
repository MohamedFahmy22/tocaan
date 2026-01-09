<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exception for payment gateway-related errors.
 */
class GatewayException extends Exception
{
    /**
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param string|null $gateway Gateway name
     * @param array<string, mixed> $context Additional context data
     */
    public function __construct(
        string $message,
        int $code = Response::HTTP_BAD_GATEWAY,
        protected ?string $gateway = null,
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
            'gateway' => $this->gateway,
            'errors' => $this->context,
        ], $this->getCode());
    }

    /**
     * Get the gateway name.
     */
    public function getGatewayName(): ?string
    {
        return $this->gateway;
    }

    /**
     * Create exception for gateway not found.
     */
    public static function notFound(string $gatewayName): self
    {
        return new self(
            message: "Payment gateway '{$gatewayName}' not found",
            code: Response::HTTP_NOT_FOUND,
            gateway: $gatewayName,
        );
    }

    /**
     * Create exception for gateway not enabled.
     */
    public static function notEnabled(string $gatewayName): self
    {
        return new self(
            message: "Payment gateway '{$gatewayName}' is not enabled",
            code: Response::HTTP_SERVICE_UNAVAILABLE,
            gateway: $gatewayName,
        );
    }

    /**
     * Create exception for invalid gateway configuration.
     */
    public static function invalidConfiguration(string $gatewayName): self
    {
        return new self(
            message: "Payment gateway '{$gatewayName}' is not properly configured",
            code: Response::HTTP_INTERNAL_SERVER_ERROR,
            gateway: $gatewayName,
        );
    }

    /**
     * Create exception for gateway communication error.
     */
    public static function communicationError(string $gatewayName, string $reason): self
    {
        return new self(
            message: "Communication error with gateway '{$gatewayName}': {$reason}",
            code: Response::HTTP_BAD_GATEWAY,
            gateway: $gatewayName,
            context: ['reason' => $reason],
        );
    }
}
