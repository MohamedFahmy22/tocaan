<?php

declare(strict_types=1);

namespace Tests\Unit\PaymentGateways;

use App\DTOs\PaymentDTO;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\PaymentGateways\CreditCardGateway;
use App\ValueObjects\PaymentResult;
use Tests\TestCase;

/**
 * Unit tests for CreditCardGateway.
 */
class CreditCardGatewayTest extends TestCase
{
    private CreditCardGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new CreditCardGateway();
    }

    /** @test */
    public function it_has_correct_name(): void
    {
        $this->assertEquals('credit_card', $this->gateway->getName());
    }

    /** @test */
    public function it_has_correct_display_name(): void
    {
        $this->assertEquals('Credit Card', $this->gateway->getDisplayName());
    }

    /** @test */
    public function it_is_enabled_by_default_in_sandbox(): void
    {
        // In sandbox mode, gateway should be enabled
        $this->assertTrue($this->gateway->isEnabled());
    }

    /** @test */
    public function it_validates_configuration_in_sandbox_mode(): void
    {
        // In sandbox mode, configuration should be valid
        $this->assertTrue($this->gateway->validateConfiguration());
    }

    /** @test */
    public function it_supports_refunds(): void
    {
        $this->assertTrue($this->gateway->supportsRefunds());
    }

    /** @test */
    public function it_processes_payment_and_returns_payment_result(): void
    {
        $paymentDTO = new PaymentDTO(
            id: null,
            orderId: 1,
            paymentNumber: 'PAY-TEST123',
            gateway: PaymentMethod::CREDIT_CARD,
            amount: 99.99,
            status: PaymentStatus::PENDING,
        );

        $result = $this->gateway->processPayment($paymentDTO);

        $this->assertInstanceOf(PaymentResult::class, $result);
        $this->assertIsString($result->message);

        if ($result->success) {
            $this->assertNotNull($result->transactionId);
            $this->assertStringStartsWith('CC-', $result->transactionId);
        }
    }

    /** @test */
    public function it_returns_safe_configuration(): void
    {
        $config = $this->gateway->getConfiguration();

        // Should not contain sensitive keys
        $this->assertArrayNotHasKey('api_key', $config);
        $this->assertArrayNotHasKey('secret', $config);
    }

    /** @test */
    public function it_can_process_refund(): void
    {
        $result = $this->gateway->refund('TXN-123456', 50.00);

        $this->assertIsObject($result);
        $this->assertObjectHasProperty('success', $result);
        $this->assertObjectHasProperty('message', $result);
    }
}
