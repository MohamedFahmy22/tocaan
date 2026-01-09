<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a main test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'), // password
        ]);

        // Create some pending orders
        Order::factory()
            ->count(3)
            ->pending()
            ->withItems(3)
            ->create([
                'user_id' => $user->id,
            ]);

        // Create some confirmed orders
        $confirmedOrders = Order::factory()
            ->count(2)
            ->confirmed()
            ->withItems(2)
            ->create([
                'user_id' => $user->id,
            ]);

        // Create payments for confirmed orders
        foreach ($confirmedOrders as $order) {
            // Successful payment
            Payment::factory()
                ->successful()
                ->creditCard()
                ->create([
                    'order_id' => $order->id,
                    'amount' => $order->total_amount,
                ]);
        }

        // Create an order with failed payment
        $failedOrder = Order::factory()
            ->confirmed()
            ->withItems(1)
            ->create([
                'user_id' => $user->id,
            ]);

        Payment::factory()
            ->failed()
            ->create([
                'order_id' => $failedOrder->id,
                'amount' => $failedOrder->total_amount,
            ]);

        $this->command->info('Database seeded with test user (test@example.com / password) and sample orders.');
    }
}
