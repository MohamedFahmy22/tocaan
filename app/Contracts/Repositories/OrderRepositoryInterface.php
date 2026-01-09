<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\DTOs\OrderDTO;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contract for Order repository implementations.
 *
 * This interface follows the Repository Pattern to abstract data access
 * logic from business logic, enabling easier testing and flexibility
 * in data source implementation.
 */
interface OrderRepositoryInterface
{
    /**
     * Get all orders with optional filtering and pagination.
     *
     * @param array<string, mixed> $filters Filter criteria
     * @param int $perPage Items per page
     * @return LengthAwarePaginator<Order>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get orders by user ID with pagination.
     *
     * @param int $userId User ID
     * @param int $perPage Items per page
     * @return LengthAwarePaginator<Order>
     */
    public function getByUserId(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find an order by ID.
     *
     * @param int $id Order ID
     * @return Order|null
     */
    public function find(int $id): ?Order;

    /**
     * Find an order by ID or throw exception.
     *
     * @param int $id Order ID
     * @return Order
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Order;

    /**
     * Find an order by order number.
     *
     * @param string $orderNumber Unique order number
     * @return Order|null
     */
    public function findByOrderNumber(string $orderNumber): ?Order;

    /**
     * Create a new order from DTO.
     *
     * @param OrderDTO $dto Order data
     * @return Order Created order with items
     */
    public function create(OrderDTO $dto): Order;

    /**
     * Update an existing order from DTO.
     *
     * @param Order $order Order to update
     * @param OrderDTO $dto Updated order data
     * @return Order Updated order
     */
    public function update(Order $order, OrderDTO $dto): Order;

    /**
     * Delete an order.
     *
     * @param Order $order Order to delete
     * @return bool Success status
     */
    public function delete(Order $order): bool;

    /**
     * Update order status.
     *
     * @param Order $order Order to update
     * @param OrderStatus $status New status
     * @return Order Updated order
     */
    public function updateStatus(Order $order, OrderStatus $status): Order;

    /**
     * Get orders by status with pagination.
     *
     * @param OrderStatus $status Status to filter by
     * @param int $perPage Items per page
     * @return LengthAwarePaginator<Order>
     */
    public function getByStatus(OrderStatus $status, int $perPage = 15): LengthAwarePaginator;

    /**
     * Recalculate and update order total.
     *
     * @param Order $order Order to recalculate
     * @return Order Updated order
     */
    public function calculateTotal(Order $order): Order;
}
