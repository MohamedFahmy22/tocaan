<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\PaymentGateways\PaymentGatewayInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\PaymentGateways\BankTransferGateway;
use App\PaymentGateways\CreditCardGateway;
use App\PaymentGateways\PaymentGatewayFactory;
use App\PaymentGateways\PayPalGateway;
use App\PaymentGateways\StripeGateway;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for Payment Gateway bindings.
 *
 * This provider is responsible for:
 * - Binding repository interfaces to implementations
 * - Registering payment gateways with the factory
 * - Configuring the payment gateway factory as a singleton
 *
 * To add a new payment gateway:
 * 1. Create a new gateway class extending AbstractPaymentGateway
 * 2. Add configuration to config/payment_gateways.php
 * 3. Register the gateway in this provider's registerGateways() method
 */
class PaymentGatewayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository interfaces to implementations
        $this->bindRepositories();

        // Register payment gateway factory as singleton
        $this->app->singleton(PaymentGatewayFactory::class, function ($app) {
            $factory = new PaymentGatewayFactory();
            $this->registerGateways($factory);
            return $factory;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/payment_gateways.php',
            'payment_gateways'
        );
    }

    /**
     * Bind repository interfaces to their implementations.
     */
    private function bindRepositories(): void
    {
        $this->app->bind(
            OrderRepositoryInterface::class,
            OrderRepository::class
        );

        $this->app->bind(
            PaymentRepositoryInterface::class,
            PaymentRepository::class
        );
    }

    /**
     * Register all payment gateways with the factory.
     *
     * Add new gateway registrations here when extending the system.
     *
     * @param PaymentGatewayFactory $factory
     */
    private function registerGateways(PaymentGatewayFactory $factory): void
    {
        // Core gateways
        $factory->register('credit_card', CreditCardGateway::class);
        $factory->register('paypal', PayPalGateway::class);
        $factory->register('stripe', StripeGateway::class);
        $factory->register('bank_transfer', BankTransferGateway::class);

        // Add custom gateways here:
        // $factory->register('new_gateway', NewGateway::class);
    }
}
