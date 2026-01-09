<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Order endpoints.
 */
class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_orders(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_list_orders(): void
    {
        Order::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_number', 'status', 'total_amount'],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    /** @test */
    public function authenticated_user_can_filter_orders_by_status(): void
    {
        Order::factory()->pending()->create(['user_id' => $this->user->id]);
        Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        Order::factory()->cancelled()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/orders?status=confirmed');

        $response->assertStatus(200);

        $orders = $response->json('data');
        foreach ($orders as $order) {
            $this->assertEquals('confirmed', $order['status']);
        }
    }

    /** @test */
    public function authenticated_user_can_create_order(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/orders', [
                'items' => [
                    [
                        'product_name' => 'Test Product',
                        'quantity' => 2,
                        'unit_price' => 49.99,
                    ],
                ],
                'notes' => 'Test order notes',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'total_amount',
                    'items',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function order_total_is_calculated_correctly(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/orders', [
                'items' => [
                    ['product_name' => 'Product A', 'quantity' => 2, 'unit_price' => 100.00],
                    ['product_name' => 'Product B', 'quantity' => 3, 'unit_price' => 50.00],
                ],
            ]);

        $response->assertStatus(201);

        // Total should be (2 * 100) + (3 * 50) = 350
        $this->assertEquals(350.00, $response->json('data.total_amount'));
    }

    /** @test */
    public function authenticated_user_can_view_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'total_amount',
                    'items',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $order->id,
                ],
            ]);
    }

    /** @test */
    public function authenticated_user_can_update_pending_order(): void
    {
        $order = Order::factory()->pending()->create(['user_id' => $this->user->id]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/orders/{$order->id}", [
                'notes' => 'Updated notes',
                'items' => [
                    ['product_name' => 'New Product', 'quantity' => 1, 'unit_price' => 200.00],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order updated successfully',
            ]);
    }

    /** @test */
    public function authenticated_user_cannot_update_confirmed_order(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/orders/{$order->id}", [
                'notes' => 'Try to update',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function authenticated_user_can_delete_order_without_payments(): void
    {
        $order = Order::factory()->pending()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order deleted successfully',
            ]);

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    /** @test */
    public function authenticated_user_cannot_delete_order_with_payments(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete order with associated payments',
            ]);
    }

    /** @test */
    public function authenticated_user_can_confirm_pending_order(): void
    {
        $order = Order::factory()->pending()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'api')
            ->patchJson("/api/orders/{$order->id}/confirm");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order confirmed successfully',
                'data' => [
                    'status' => 'confirmed',
                ],
            ]);
    }

    /** @test */
    public function authenticated_user_cannot_confirm_already_confirmed_order(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'api')
            ->patchJson("/api/orders/{$order->id}/confirm");

        $response->assertStatus(422);
    }

    /** @test */
    public function authenticated_user_can_cancel_pending_order(): void
    {
        $order = Order::factory()->pending()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'api')
            ->patchJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);
    }

    /** @test */
    public function authenticated_user_cannot_cancel_order_with_successful_payment(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        Payment::factory()->successful()->create(['order_id' => $order->id]);

        $response = $this->actingAs($this->user, 'api')
            ->patchJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422);
    }

    /** @test */
    public function authenticated_user_can_get_their_orders(): void
    {
        // Create orders for this user
        Order::factory()->count(2)->create(['user_id' => $this->user->id]);

        // Create orders for another user
        $otherUser = User::factory()->create();
        Order::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/orders/my-orders');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function order_validation_requires_at_least_one_item(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/orders', [
                'items' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    /** @test */
    public function order_validation_requires_valid_item_data(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/orders', [
                'items' => [
                    [
                        'product_name' => '',
                        'quantity' => 0,
                        'unit_price' => -10,
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'items.0.product_name',
                'items.0.quantity',
                'items.0.unit_price',
            ]);
    }
}
