<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\DTOs\PaymentDTO;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Eloquent implementation of the Payment Repository.
 *
 * This repository handles all database operations for payments,
 * abstracting the data access logic from the service layer.
 */
class PaymentRepository implements PaymentRepositoryInterface
{
    /**
     * @param Payment $model The Payment model instance
     */
    public function __construct(
        private readonly Payment $model
    ) {}

    /**
     * {@inheritDoc}
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['order', 'order.user']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        if (!empty($filters['gateway'])) {
            $query->gateway($filters['gateway']);
        }

        if (!empty($filters['order_id'])) {
            $query->forOrder($filters['order_id']);
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
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('gateway_transaction_id', 'like', "%{$search}%");
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
    public function getByOrderId(int $orderId): Collection
    {
        return $this->model
            ->newQuery()
            ->forOrder($orderId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id): ?Payment
    {
        return $this->model
            ->newQuery()
            ->with(['order', 'order.user'])
            ->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Payment
    {
        return $this->model
            ->newQuery()
            ->with(['order', 'order.user'])
            ->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByPaymentNumber(string $paymentNumber): ?Payment
    {
        return $this->model
            ->newQuery()
            ->with(['order'])
            ->where('payment_number', $paymentNumber)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function create(PaymentDTO $dto): Payment
    {
        return $this->model->newQuery()->create([
            'order_id' => $dto->orderId,
            'gateway' => $dto->gateway->value,
            'amount' => $dto->amount,
            'status' => PaymentStatus::PENDING->value,
            'metadata' => $dto->metadata,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateWithResult(Payment $payment, PaymentResult $result): Payment
    {
        $payment->update([
            'status' => $result->getStatus()->value,
            'gateway_transaction_id' => $result->transactionId,
            'processed_at' => Carbon::now(),
            'metadata' => array_merge(
                $payment->metadata ?? [],
                ['gateway_response' => $result->toArray()]
            ),
        ]);

        return $payment->fresh(['order']);
    }

    /**
     * {@inheritDoc}
     */
    public function markAsFailed(Payment $payment, string $reason): Payment
    {
        $payment->update([
            'status' => PaymentStatus::FAILED->value,
            'processed_at' => Carbon::now(),
            'metadata' => array_merge(
                $payment->metadata ?? [],
                ['failure_reason' => $reason]
            ),
        ]);

        return $payment->fresh(['order']);
    }

    /**
     * {@inheritDoc}
     */
    public function orderHasPayments(int $orderId): bool
    {
        return $this->model
            ->newQuery()
            ->forOrder($orderId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function orderHasSuccessfulPayment(int $orderId): bool
    {
        return $this->model
            ->newQuery()
            ->forOrder($orderId)
            ->status(PaymentStatus::SUCCESSFUL)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getByStatus(PaymentStatus $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->newQuery()
            ->with(['order', 'order.user'])
            ->status($status)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
