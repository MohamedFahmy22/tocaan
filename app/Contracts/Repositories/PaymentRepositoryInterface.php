<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\DTOs\PaymentDTO;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\ValueObjects\PaymentResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for Payment repository implementations.
 *
 * This interface follows the Repository Pattern to abstract data access
 * logic from business logic, enabling easier testing and flexibility
 * in data source implementation.
 */
interface PaymentRepositoryInterface
{
    /**
     * Get all payments with optional filtering and pagination.
     *
     * @param array<string, mixed> $filters Filter criteria
     * @param int $perPage Items per page
     * @return LengthAwarePaginator<Payment>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get payments for a specific order.
     *
     * @param int $orderId Order ID
     * @return Collection<int, Payment>
     */
    public function getByOrderId(int $orderId): Collection;

    /**
     * Find a payment by ID.
     *
     * @param int $id Payment ID
     * @return Payment|null
     */
    public function find(int $id): ?Payment;

    /**
     * Find a payment by ID or throw exception.
     *
     * @param int $id Payment ID
     * @return Payment
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Payment;

    /**
     * Find a payment by payment number.
     *
     * @param string $paymentNumber Unique payment number
     * @return Payment|null
     */
    public function findByPaymentNumber(string $paymentNumber): ?Payment;

    /**
     * Create a new payment from DTO.
     *
     * @param PaymentDTO $dto Payment data
     * @return Payment Created payment
     */
    public function create(PaymentDTO $dto): Payment;

    /**
     * Update payment with gateway result.
     *
     * @param Payment $payment Payment to update
     * @param PaymentResult $result Gateway result
     * @return Payment Updated payment
     */
    public function updateWithResult(Payment $payment, PaymentResult $result): Payment;

    /**
     * Mark payment as failed.
     *
     * @param Payment $payment Payment to mark
     * @param string $reason Failure reason
     * @return Payment Updated payment
     */
    public function markAsFailed(Payment $payment, string $reason): Payment;

    /**
     * Check if an order has any payments.
     *
     * @param int $orderId Order ID
     * @return bool True if order has payments
     */
    public function orderHasPayments(int $orderId): bool;

    /**
     * Check if an order has any successful payments.
     *
     * @param int $orderId Order ID
     * @return bool True if order has successful payments
     */
    public function orderHasSuccessfulPayment(int $orderId): bool;

    /**
     * Get payments by status.
     *
     * @param PaymentStatus $status Status to filter by
     * @param int $perPage Items per page
     * @return LengthAwarePaginator<Payment>
     */
    public function getByStatus(PaymentStatus $status, int $perPage = 15): LengthAwarePaginator;
}
