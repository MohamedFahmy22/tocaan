<?php

declare(strict_types=1);

namespace App\Contracts\PaymentGateways;

use App\DTOs\PaymentDTO;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;

/**
 * Contract for payment gateway implementations.
 *
 * This interface defines the Strategy Pattern contract for payment gateways.
 * All payment gateway implementations must adhere to this interface, enabling
 * seamless addition of new gateways with minimal code changes.
 *
 * @see \App\PaymentGateways\AbstractPaymentGateway for base implementation
 */
interface PaymentGatewayInterface
{
    /**
     * Get the unique identifier for this gateway.
     *
     * This should match the key used in configuration and database.
     *
     * @return string Gateway identifier (e.g., 'credit_card', 'paypal')
     */
    public function getName(): string;

    /**
     * Get the human-readable display name for this gateway.
     *
     * Used in API responses and documentation.
     *
     * @return string Display name (e.g., 'Credit Card', 'PayPal')
     */
    public function getDisplayName(): string;

    /**
     * Check if this gateway is currently enabled.
     *
     * Gateways can be enabled/disabled via configuration.
     *
     * @return bool True if gateway is enabled and available for use
     */
    public function isEnabled(): bool;

    /**
     * Process a payment through this gateway.
     *
     * This is the main method that handles payment processing.
     * Implementations should handle communication with external payment
     * providers and return appropriate results.
     *
     * @param PaymentDTO $payment Payment data to process
     * @return PaymentResult Result of the payment processing
     * @throws \App\Exceptions\GatewayException When gateway communication fails
     */
    public function processPayment(PaymentDTO $payment): PaymentResult;

    /**
     * Process a refund for a previous transaction.
     *
     * @param string $transactionId Original transaction ID to refund
     * @param float $amount Amount to refund
     * @return RefundResult Result of the refund processing
     * @throws \App\Exceptions\GatewayException When gateway communication fails
     */
    public function refund(string $transactionId, float $amount): RefundResult;

    /**
     * Validate that the gateway configuration is complete and valid.
     *
     * This should check all required configuration values (API keys,
     * secrets, etc.) are present and properly formatted.
     *
     * @return bool True if configuration is valid
     */
    public function validateConfiguration(): bool;

    /**
     * Get gateway-specific configuration.
     *
     * Returns the configuration array for this gateway, useful for
     * debugging and logging purposes.
     *
     * @return array<string, mixed> Gateway configuration
     */
    public function getConfiguration(): array;

    /**
     * Check if the gateway supports refunds.
     *
     * Not all payment gateways support refund operations.
     *
     * @return bool True if refunds are supported
     */
    public function supportsRefunds(): bool;
}
