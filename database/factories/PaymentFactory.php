<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for Payment model.
 *
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_number' => 'PAY-' . strtoupper(Str::random(10)),
            'gateway' => $this->faker->randomElement(PaymentMethod::values()),
            'gateway_transaction_id' => null,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'status' => PaymentStatus::PENDING,
            'metadata' => null,
            'processed_at' => null,
        ];
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PENDING,
            'gateway_transaction_id' => null,
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the payment is successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::SUCCESSFUL,
            'gateway_transaction_id' => 'TXN-' . strtoupper(Str::random(16)),
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::FAILED,
            'gateway_transaction_id' => null,
            'processed_at' => now(),
            'metadata' => ['failure_reason' => 'Payment declined'],
        ]);
    }

    /**
     * Use credit card gateway.
     */
    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => PaymentMethod::CREDIT_CARD->value,
        ]);
    }

    /**
     * Use PayPal gateway.
     */
    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => PaymentMethod::PAYPAL->value,
        ]);
    }
}
