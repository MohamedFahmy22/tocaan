<?php

declare(strict_types=1);

namespace App\PaymentGateways;

use App\Contracts\PaymentGateways\PaymentGatewayInterface;
use App\Exceptions\GatewayException;

/**
 * Factory for creating and managing payment gateway instances.
 *
 * This factory implements the Factory Pattern combined with the Strategy Pattern
 * to provide a clean way to instantiate and manage payment gateways.
 *
 * @example
 * // Register a new gateway
 * $factory->register('new_gateway', NewGateway::class);
 *
 * // Create a gateway instance
 * $gateway = $factory->make('credit_card');
 *
 * // Get all available gateways
 * $gateways = $factory->getAvailableGateways();
 */
class PaymentGatewayFactory
{
    /**
     * Registered gateway classes.
     *
     * @var array<string, class-string<PaymentGatewayInterface>>
     */
    private array $gateways = [];

    /**
     * Cached gateway instances.
     *
     * @var array<string, PaymentGatewayInterface>
     */
    private array $instances = [];

    /**
     * Register a payment gateway.
     *
     * @param string $name Gateway identifier
     * @param class-string<PaymentGatewayInterface> $gatewayClass Fully qualified class name
     * @return self
     */
    public function register(string $name, string $gatewayClass): self
    {
        $this->gateways[$name] = $gatewayClass;

        // Clear cached instance if exists
        unset($this->instances[$name]);

        return $this;
    }

    /**
     * Create or retrieve a gateway instance.
     *
     * Uses lazy loading to cache gateway instances.
     *
     * @param string $name Gateway identifier
     * @return PaymentGatewayInterface
     * @throws GatewayException When gateway is not found or not enabled
     */
    public function make(string $name): PaymentGatewayInterface
    {
        // Check if gateway is registered
        if (!isset($this->gateways[$name])) {
            throw GatewayException::notFound($name);
        }

        // Return cached instance if available
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        // Create new instance
        $gateway = app($this->gateways[$name]);

        // Validate gateway implements interface
        if (!$gateway instanceof PaymentGatewayInterface) {
            throw new GatewayException(
                "Gateway class must implement PaymentGatewayInterface",
                500
            );
        }

        // Check if gateway is enabled
        if (!$gateway->isEnabled()) {
            throw GatewayException::notEnabled($name);
        }

        // Cache and return
        $this->instances[$name] = $gateway;

        return $gateway;
    }

    /**
     * Get a gateway instance without checking if enabled.
     *
     * Useful for admin panels to view gateway configurations.
     *
     * @param string $name Gateway identifier
     * @return PaymentGatewayInterface
     * @throws GatewayException When gateway is not found
     */
    public function resolve(string $name): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$name])) {
            throw GatewayException::notFound($name);
        }

        return app($this->gateways[$name]);
    }

    /**
     * Check if a gateway is registered.
     *
     * @param string $name Gateway identifier
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->gateways[$name]);
    }

    /**
     * Get all registered gateway names.
     *
     * @return array<string>
     */
    public function getRegisteredGateways(): array
    {
        return array_keys($this->gateways);
    }

    /**
     * Get all enabled and available gateways.
     *
     * @return array<string, PaymentGatewayInterface>
     */
    public function getAvailableGateways(): array
    {
        $available = [];

        foreach ($this->gateways as $name => $class) {
            try {
                $gateway = $this->resolve($name);

                if ($gateway->isEnabled() && $gateway->validateConfiguration()) {
                    $available[$name] = $gateway;
                }
            } catch (\Exception) {
                // Skip gateways that fail to instantiate
                continue;
            }
        }

        return $available;
    }

    /**
     * Get gateway information for API responses.
     *
     * @return array<int, array{name: string, display_name: string, enabled: bool}>
     */
    public function getGatewayInfo(): array
    {
        $info = [];

        foreach ($this->gateways as $name => $class) {
            try {
                $gateway = $this->resolve($name);
                $info[] = [
                    'name' => $gateway->getName(),
                    'display_name' => $gateway->getDisplayName(),
                    'enabled' => $gateway->isEnabled(),
                    'supports_refunds' => $gateway->supportsRefunds(),
                ];
            } catch (\Exception) {
                continue;
            }
        }

        return $info;
    }

    /**
     * Get the default gateway.
     *
     * @return PaymentGatewayInterface
     * @throws GatewayException When default gateway is not found or not enabled
     */
    public function getDefault(): PaymentGatewayInterface
    {
        $defaultName = config('payment_gateways.default', 'credit_card');

        return $this->make($defaultName);
    }
}
