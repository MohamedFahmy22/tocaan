<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\OrderStatus;

/**
 * Data Transfer Object for Order data.
 *
 * Immutable object for transferring order data between layers.
 */
readonly class OrderDTO
{
    /**
     * @param int|null $id Order ID (null for new orders)
     * @param int $userId Associated user ID
     * @param string|null $orderNumber Unique order number
     * @param OrderStatus $status Current order status
     * @param array<OrderItemDTO> $items Order items
     * @param string|null $notes Optional order notes
     */
    public function __construct(
        public ?int $id,
        public int $userId,
        public ?string $orderNumber,
        public OrderStatus $status,
        public array $items,
        public ?string $notes = null,
    ) {}

    /**
     * Create DTO from validated request data.
     *
     * @param array<string, mixed> $data Validated request data
     * @param int $userId Authenticated user ID
     */
    public static function fromRequest(array $data, int $userId): self
    {
        $items = array_map(
            fn(array $item) => OrderItemDTO::fromArray($item),
            $data['items'] ?? []
        );

        return new self(
            id: null,
            userId: $userId,
            orderNumber: null,
            status: OrderStatus::PENDING,
            items: $items,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Create DTO from existing model data for updates.
     *
     * @param array<string, mixed> $data Update data
     * @param int $orderId Existing order ID
     * @param int $userId User ID
     * @param string $orderNumber Existing order number
     * @param OrderStatus $currentStatus Current status
     */
    public static function forUpdate(
        array $data,
        int $orderId,
        int $userId,
        string $orderNumber,
        OrderStatus $currentStatus
    ): self {
        $items = isset($data['items'])
            ? array_map(fn(array $item) => OrderItemDTO::fromArray($item), $data['items'])
            : [];

        return new self(
            id: $orderId,
            userId: $userId,
            orderNumber: $orderNumber,
            status: isset($data['status']) ? OrderStatus::from($data['status']) : $currentStatus,
            items: $items,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Calculate total amount from items.
     */
    public function calculateTotal(): float
    {
        return array_reduce(
            $this->items,
            fn(float $carry, OrderItemDTO $item) => $carry + $item->getTotalPrice(),
            0.0
        );
    }

    /**
     * Convert DTO to array for model creation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'order_number' => $this->orderNumber,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'total_amount' => $this->calculateTotal(),
        ];
    }
}
