<?php

declare(strict_types=1);

namespace App\PaymentGateways;

use App\Contracts\PaymentGateways\PaymentGatewayInterface;
use App\DTOs\PaymentDTO;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base class for payment gateway implementations.
 *
 * This class provides common functionality for all payment gateways,
 * implementing the Template Method pattern for shared behaviors.
 * Concrete gateway implementations should extend this class.
 *
 * @see PaymentGatewayInterface
 */
abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    /**
     * Gateway configuration from config/payment_gateways.php
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Initialize the gateway with its configuration.
     */
    public function __construct()
    {
        $this->config = config("payment_gateways.gateways.{$this->getName()}", []);
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration(): array
    {
        // Return safe configuration (exclude sensitive data)
        return array_filter($this->config, function ($key) {
            return !in_array($key, ['api_key', 'secret', 'client_secret', 'webhook_secret']);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsRefunds(): bool
    {
        return true; // Most gateways support refunds
    }

    /**
     * Log a gateway event.
     *
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     */
    protected function log(string $message, array $context = []): void
    {
        Log::channel('single')->info(
            "[PaymentGateway:{$this->getName()}] {$message}",
            array_merge(['gateway' => $this->getName()], $context)
        );
    }

    /**
     * Log a gateway error.
     *
     * @param string $message Error message
     * @param array<string, mixed> $context Additional context
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::channel('single')->error(
            "[PaymentGateway:{$this->getName()}] {$message}",
            array_merge(['gateway' => $this->getName()], $context)
        );
    }

    /**
     * Simulate payment processing for demonstration purposes.
     *
     * In a production environment, this would be replaced with
     * actual API calls to the payment provider.
     *
     * @param PaymentDTO $payment Payment data
     * @return bool Success status
     */
    protected function simulatePayment(PaymentDTO $payment): bool
    {
        // Simulate a 90% success rate for testing
        // In production, remove this and implement actual gateway logic
        $success = random_int(1, 10) <= 9;

        if ($this->isSandbox()) {
            $this->log('Sandbox mode payment simulation', [
                'amount' => $payment->amount,
                'success' => $success,
            ]);
        }

        return $success;
    }

    /**
     * Check if gateway is in sandbox/test mode.
     */
    protected function isSandbox(): bool
    {
        return (bool) ($this->config['sandbox'] ?? true);
    }

    /**
     * Get a configuration value.
     *
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
