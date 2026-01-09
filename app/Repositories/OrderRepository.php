<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\DTOs\OrderDTO;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent implementation of the Order Repository.
 *
 * This repository handles all database operations for orders,
 * abstracting the data access logic from the service layer.
 */
class OrderRepository implements OrderRepositoryInterface
{
    /**
     * @param Order $model The Order model instance
     */
    public function __construct(
        private readonly Order $model
    ) {}

    /**
     * {@inheritDoc}
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['items', 'user']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getByUserId(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->newQuery()
            ->with(['items'])
            ->forUser($userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id): ?Order
    {
        return $this->model
            ->newQuery()
            ->with(['items', 'payments', 'user'])
            ->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Order
    {
        return $this->model
            ->newQuery()
            ->with(['items', 'payments', 'user'])
            ->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->model
            ->newQuery()
            ->with(['items', 'payments', 'user'])
            ->where('order_number', $orderNumber)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function create(OrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            // Create order
            $order = $this->model->newQuery()->create([
                'user_id' => $dto->userId,
                'status' => $dto->status->value,
                'notes' => $dto->notes,
                'total_amount' => 0, // Will be calculated after items
            ]);

            // Create order items
            foreach ($dto->items as $itemDTO) {
                $order->items()->create($itemDTO->toArray());
            }

            // Calculate total
            $order->calculateTotal();

            return $order->fresh(['items', 'user']);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function update(Order $order, OrderDTO $dto): Order
    {
        return DB::transaction(function () use ($order, $dto) {
            // Update order
            $order->update([
                'notes' => $dto->notes,
            ]);

            \Illuminate\Support\Facades\Log::info('Update order items count: ' . count($dto->items));

            // Update items if provided
            if (count($dto->items) > 0) {
                // Remove existing items
                $order->items()->delete();

                // Create new items
                foreach ($dto->items as $itemDTO) {
                    $order->items()->create($itemDTO->toArray());
                }

                // Recalculate total
                $order->calculateTotal();
            }

            return $order->fresh(['items', 'payments', 'user']);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Order $order): bool
    {
        return (bool) $order->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        $order->update(['status' => $status->value]);

        return $order->fresh(['items', 'payments']);
    }

    /**
     * {@inheritDoc}
     */
    public function getByStatus(OrderStatus $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->newQuery()
            ->with(['items', 'user'])
            ->status($status)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function calculateTotal(Order $order): Order
    {
        $order->calculateTotal();

        return $order->fresh();
    }
}
