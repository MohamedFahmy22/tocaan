<?php

declare(strict_types=1);

namespace Tests\Unit\PaymentGateways;

use App\Exceptions\GatewayException;
use App\PaymentGateways\CreditCardGateway;
use App\PaymentGateways\PaymentGatewayFactory;
use App\PaymentGateways\PayPalGateway;
use App\PaymentGateways\StripeGateway;
use Tests\TestCase;

/**
 * Unit tests for PaymentGatewayFactory.
 */
class PaymentGatewayFactoryTest extends TestCase
{
    private PaymentGatewayFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new PaymentGatewayFactory();

        // Register test gateways
        $this->factory->register('credit_card', CreditCardGateway::class);
        $this->factory->register('paypal', PayPalGateway::class);
        $this->factory->register('stripe', StripeGateway::class);
    }

    /** @test */
    public function it_can_register_gateway(): void
    {
        $this->assertTrue($this->factory->has('credit_card'));
        $this->assertTrue($this->factory->has('paypal'));
        $this->assertTrue($this->factory->has('stripe'));
    }

    /** @test */
    public function it_returns_false_for_unregistered_gateway(): void
    {
        $this->assertFalse($this->factory->has('nonexistent'));
    }

    /** @test */
    public function it_can_create_gateway_instance(): void
    {
        $gateway = $this->factory->make('credit_card');

        $this->assertInstanceOf(CreditCardGateway::class, $gateway);
    }

    /** @test */
    public function it_throws_exception_for_unknown_gateway(): void
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage("Payment gateway 'unknown' not found");

        $this->factory->make('unknown');
    }

    /** @test */
    public function it_returns_registered_gateway_names(): void
    {
        $gateways = $this->factory->getRegisteredGateways();

        $this->assertContains('credit_card', $gateways);
        $this->assertContains('paypal', $gateways);
        $this->assertContains('stripe', $gateways);
    }

    /** @test */
    public function it_can_resolve_gateway_without_checking_enabled(): void
    {
        $gateway = $this->factory->resolve('credit_card');

        $this->assertInstanceOf(CreditCardGateway::class, $gateway);
    }

    /** @test */
    public function it_returns_gateway_info(): void
    {
        $info = $this->factory->getGatewayInfo();

        $this->assertIsArray($info);
        $this->assertNotEmpty($info);

        $firstGateway = $info[0];
        $this->assertArrayHasKey('name', $firstGateway);
        $this->assertArrayHasKey('display_name', $firstGateway);
        $this->assertArrayHasKey('enabled', $firstGateway);
    }

    /** @test */
    public function it_returns_available_gateways(): void
    {
        $gateways = $this->factory->getAvailableGateways();

        $this->assertIsArray($gateways);

        foreach ($gateways as $gateway) {
            $this->assertTrue($gateway->isEnabled());
        }
    }

    /** @test */
    public function it_caches_gateway_instances(): void
    {
        $gateway1 = $this->factory->make('credit_card');
        $gateway2 = $this->factory->make('credit_card');

        // Should be the same cached instance
        $this->assertSame($gateway1, $gateway2);
    }
}
