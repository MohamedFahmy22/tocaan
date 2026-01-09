<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object for Order Item data.
 *
 * Immutable object for transferring order item data between layers.
 */
readonly class OrderItemDTO
{
    /**
     * @param int|null $id Item ID (null for new items)
     * @param string $productName Name of the product
     * @param int $quantity Quantity ordered
     * @param float $unitPrice Price per unit
     */
    public function __construct(
        public ?int $id,
        public string $productName,
        public int $quantity,
        public float $unitPrice,
    ) {}

    /**
     * Create DTO from array data.
     *
     * @param array<string, mixed> $data Item data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            productName: $data['product_name'],
            quantity: (int) $data['quantity'],
            unitPrice: (float) $data['unit_price'],
        );
    }

    /**
     * Calculate total price for this item.
     */
    public function getTotalPrice(): float
    {
        return round($this->quantity * $this->unitPrice, 2);
    }

    /**
     * Convert DTO to array for model creation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_name' => $this->productName,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
        ];
    }
}
