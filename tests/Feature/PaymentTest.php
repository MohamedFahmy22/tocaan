<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Payment endpoints.
 */
class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Order $confirmedOrder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        // Create a confirmed order with items
        $this->confirmedOrder = Order::factory()->confirmed()->create([
            'user_id' => $this->user->id,
            'total_amount' => 199.99,
        ]);
        OrderItem::factory()->create([
            'order_id' => $this->confirmedOrder->id,
            'quantity' => 1,
            'unit_price' => 199.99,
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_payments(): void
    {
        $response = $this->getJson('/api/payments');

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_list_payments(): void
    {
        Payment::factory()->count(3)->create([
            'order_id' => $this->confirmedOrder->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'payment_number', 'gateway', 'amount', 'status'],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    /** @test */
    public function authenticated_user_can_view_payment(): void
    {
        $payment = Payment::factory()->successful()->create([
            'order_id' => $this->confirmedOrder->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'payment_number',
                    'gateway',
                    'amount',
                    'status',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $payment->id,
                ],
            ]);
    }

    /** @test */
    public function authenticated_user_can_view_order_payments(): void
    {
        Payment::factory()->count(2)->create([
            'order_id' => $this->confirmedOrder->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/orders/{$this->confirmedOrder->id}/payments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'payment_number', 'gateway', 'amount', 'status'],
                ],
            ]);
    }

    /** @test */
    public function authenticated_user_can_process_payment_for_confirmed_order(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/orders/{$this->confirmedOrder->id}/payments", [
                'payment_method' => 'credit_card',
            ]);

        // Payment might succeed or fail based on simulation
        // Accept both 201 (success) and 402 (payment required/failed)
        $this->assertContains($response->status(), [201, 402]);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'payment_number',
                'gateway',
                'amount',
                'status',
            ],
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->confirmedOrder->id,
            'gateway' => 'credit_card',
        ]);
    }

    /** @test */
    public function authenticated_user_cannot_process_payment_for_pending_order(): void
    {
        $pendingOrder = Order::factory()->pending()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/orders/{$pendingOrder->id}/payments", [
                'payment_method' => 'credit_card',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Payments can only be processed for confirmed orders',
            ]);
    }

    /** @test */
    public function authenticated_user_cannot_pay_for_already_paid_order(): void
    {
        Payment::factory()->successful()->create([
            'order_id' => $this->confirmedOrder->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/orders/{$this->confirmedOrder->id}/payments", [
                'payment_method' => 'credit_card',
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'This order has already been paid',
            ]);
    }

    /** @test */
    public function authenticated_user_can_process_payment_with_paypal(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/orders/{$this->confirmedOrder->id}/payments", [
                'payment_method' => 'paypal',
            ]);

        // Payment might succeed or fail based on simulation
        $this->assertContains($response->status(), [201, 402]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->confirmedOrder->id,
            'gateway' => 'paypal',
        ]);
    }

    /** @test */
    public function payment_validation_requires_payment_method(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/orders/{$this->confirmedOrder->id}/payments", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    /** @test */
    public function payment_validation_rejects_invalid_payment_method(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/orders/{$this->confirmedOrder->id}/payments", [
                'payment_method' => 'invalid_method',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    /** @test */
    public function authenticated_user_can_get_available_payment_methods(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/payment-methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['name', 'display_name', 'enabled'],
                ],
            ]);
    }

    /** @test */
    public function authenticated_user_can_filter_payments_by_status(): void
    {
        Payment::factory()->successful()->create([
            'order_id' => $this->confirmedOrder->id,
        ]);
        Payment::factory()->failed()->create([
            'order_id' => $this->confirmedOrder->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/payments?status=successful');

        $response->assertStatus(200);

        $payments = $response->json('data');
        foreach ($payments as $payment) {
            $this->assertEquals('successful', $payment['status']);
        }
    }

    /** @test */
    public function authenticated_user_can_filter_payments_by_gateway(): void
    {
        Payment::factory()->creditCard()->create([
            'order_id' => $this->confirmedOrder->id,
        ]);
        Payment::factory()->paypal()->create([
            'order_id' => $this->confirmedOrder->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/payments?gateway=credit_card');

        $response->assertStatus(200);

        $payments = $response->json('data');
        foreach ($payments as $payment) {
            $this->assertEquals('credit_card', $payment['gateway']);
        }
    }

    /** @test */
    public function payment_amount_matches_order_total(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/orders/{$this->confirmedOrder->id}/payments", [
                'payment_method' => 'credit_card',
            ]);

        // Payment might succeed or fail based on simulation
        $this->assertContains($response->status(), [201, 402]);

        // Payment amount should match order total regardless of success/failure
        $this->assertEquals(
            (float) $this->confirmedOrder->total_amount,
            (float) $response->json('data.amount')
        );
    }
}
