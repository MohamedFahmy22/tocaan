<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for OrderItem model.
 *
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * Product name pool for realistic testing.
     */
    private const PRODUCTS = [
        'Laptop Pro 15"',
        'Wireless Mouse',
        'Mechanical Keyboard',
        'USB-C Hub',
        'Monitor 27"',
        'Webcam HD',
        'Headphones Bluetooth',
        'External SSD 1TB',
        'Graphics Tablet',
        'Smartphone Case',
        'Screen Protector',
        'Charging Cable',
        'Power Bank 20000mAh',
        'Smart Watch',
        'Tablet Stand',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_name' => $this->faker->randomElement(self::PRODUCTS),
            'quantity' => $this->faker->numberBetween(1, 5),
            'unit_price' => $this->faker->randomFloat(2, 9.99, 499.99),
        ];
    }

    /**
     * Create item with specific product.
     */
    public function forProduct(string $name, float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'product_name' => $name,
            'unit_price' => $price,
        ]);
    }
}
