<?php

declare(strict_types=1);

namespace Tests\Unit\PaymentGateways;

use App\DTOs\PaymentDTO;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\PaymentGateways\PayPalGateway;
use App\ValueObjects\PaymentResult;
use Tests\TestCase;

/**
 * Unit tests for PayPalGateway.
 */
class PayPalGatewayTest extends TestCase
{
    private PayPalGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new PayPalGateway();
    }

    /** @test */
    public function it_has_correct_name(): void
    {
        $this->assertEquals('paypal', $this->gateway->getName());
    }

    /** @test */
    public function it_has_correct_display_name(): void
    {
        $this->assertEquals('PayPal', $this->gateway->getDisplayName());
    }

    /** @test */
    public function it_is_enabled_by_default_in_sandbox(): void
    {
        $this->assertTrue($this->gateway->isEnabled());
    }

    /** @test */
    public function it_validates_configuration_in_sandbox_mode(): void
    {
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
            gateway: PaymentMethod::PAYPAL,
            amount: 149.99,
            status: PaymentStatus::PENDING,
        );

        $result = $this->gateway->processPayment($paymentDTO);

        $this->assertInstanceOf(PaymentResult::class, $result);
        $this->assertIsString($result->message);

        if ($result->success) {
            $this->assertNotNull($result->transactionId);
            $this->assertStringStartsWith('PP-', $result->transactionId);
        }
    }

    /** @test */
    public function it_can_process_refund(): void
    {
        $result = $this->gateway->refund('PP-123456', 75.00);

        $this->assertIsObject($result);
        $this->assertObjectHasProperty('success', $result);
        $this->assertObjectHasProperty('message', $result);
    }
}
