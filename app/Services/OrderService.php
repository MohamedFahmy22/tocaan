<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\DTOs\OrderDTO;
use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service class for order-related business logic.
 *
 * This service encapsulates all order business logic, following the
 * Single Responsibility Principle. It coordinates between repositories
 * and enforces business rules.
 */
class OrderService
{
    /**
     * @param OrderRepositoryInterface $orderRepository Order repository
     * @param PaymentRepositoryInterface $paymentRepository Payment repository
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {}

    /**
     * Get paginated list of orders with filtering.
     *
     * @param array<string, mixed> $filters Filter criteria
     * @param int $perPage Items per page
     * @return LengthAwarePaginator<Order>
     */
    public function getOrders(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->paginate($filters, $perPage);
    }

    /**
     * Get orders for a specific user.
     *
     * @param int $userId User ID
     * @param int $perPage Items per page
     * @return LengthAwarePaginator<Order>
     */
    public function getUserOrders(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getByUserId($userId, $perPage);
    }

    /**
     * Get a single order by ID.
     *
     * @param int $orderId Order ID
     * @return Order
     * @throws OrderException When order not found
     */
    public function getOrder(int $orderId): Order
    {
        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            throw OrderException::notFound($orderId);
        }

        return $order;
    }

    /**
     * Create a new order.
     *
     * @param OrderDTO $dto Order data
     * @return Order Created order
     */
    public function createOrder(OrderDTO $dto): Order
    {
        return $this->orderRepository->create($dto);
    }

    /**
     * Update an existing order.
     *
     * @param int $orderId Order ID
     * @param OrderDTO $dto Updated order data
     * @return Order Updated order
     * @throws OrderException When order not found or cannot be updated
     */
    public function updateOrder(int $orderId, OrderDTO $dto): Order
    {
        $order = $this->getOrder($orderId);

        // Only pending orders can be updated
        if (!$order->isPending()) {
            throw new OrderException(
                'Only pending orders can be updated',
                422
            );
        }

        return $this->orderRepository->update($order, $dto);
    }

    /**
     * Delete an order.
     *
     * Business rule: Orders with payments cannot be deleted.
     *
     * @param int $orderId Order ID
     * @return bool Success status
     * @throws OrderException When order has payments or not found
     */
    public function deleteOrder(int $orderId): bool
    {
        $order = $this->getOrder($orderId);

        // Check for associated payments
        if ($this->paymentRepository->orderHasPayments($orderId)) {
            throw OrderException::hasPayments();
        }

        return $this->orderRepository->delete($order);
    }

    /**
     * Confirm an order.
     *
     * @param int $orderId Order ID
     * @return Order Confirmed order
     * @throws OrderException When order cannot be confirmed
     */
    public function confirmOrder(int $orderId): Order
    {
        $order = $this->getOrder($orderId);

        if (!$order->status->canTransitionTo(OrderStatus::CONFIRMED)) {
            throw OrderException::invalidStatusTransition(
                $order->status->value,
                OrderStatus::CONFIRMED->value
            );
        }

        return $this->orderRepository->updateStatus($order, OrderStatus::CONFIRMED);
    }

    /**
     * Cancel an order.
     *
     * @param int $orderId Order ID
     * @return Order Cancelled order
     * @throws OrderException When order cannot be cancelled
     */
    public function cancelOrder(int $orderId): Order
    {
        $order = $this->getOrder($orderId);

        // Check if order has successful payments
        if ($this->paymentRepository->orderHasSuccessfulPayment($orderId)) {
            throw new OrderException(
                'Cannot cancel order with successful payments',
                422
            );
        }

        if (!$order->status->canTransitionTo(OrderStatus::CANCELLED)) {
            throw OrderException::invalidStatusTransition(
                $order->status->value,
                OrderStatus::CANCELLED->value
            );
        }

        return $this->orderRepository->updateStatus($order, OrderStatus::CANCELLED);
    }

    /**
     * Get orders by status.
     *
     * @param OrderStatus $status Status to filter by
     * @param int $perPage Items per page
     * @return LengthAwarePaginator<Order>
     */
    public function getOrdersByStatus(OrderStatus $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getByStatus($status, $perPage);
    }

    /**
     * Check if user owns the order.
     *
     * @param int $orderId Order ID
     * @param int $userId User ID
     * @return bool True if user owns the order
     */
    public function userOwnsOrder(int $orderId, int $userId): bool
    {
        $order = $this->orderRepository->find($orderId);

        return $order && $order->user_id === $userId;
    }
}
