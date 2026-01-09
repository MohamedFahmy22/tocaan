<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\DTOs\PaymentDTO;
use App\Enums\OrderStatus;
use App\Exceptions\GatewayException;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\Payment;
use App\PaymentGateways\PaymentGatewayFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service class for payment-related business logic.
 *
 * This service encapsulates all payment business logic, coordinating
 * between the payment gateways (Strategy Pattern) and repositories.
 */
class PaymentService
{
    /**
     * @param PaymentGatewayFactory $gatewayFactory Payment gateway factory
     * @param PaymentRepositoryInterface $paymentRepository Payment repository
     * @param OrderRepositoryInterface $orderRepository Order repository
     */
    public function __construct(
        private readonly PaymentGatewayFactory $gatewayFactory,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    /**
     * Get paginated list of payments with filtering.
     *
     * @param array<string, mixed> $filters Filter criteria
     * @param int $perPage Items per page
     * @return LengthAwarePaginator<Payment>
     */
    public function getPayments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->paymentRepository->paginate($filters, $perPage);
    }

    /**
     * Get payments for a specific order.
     *
     * @param int $orderId Order ID
     * @return Collection<int, Payment>
     */
    public function getOrderPayments(int $orderId): Collection
    {
        return $this->paymentRepository->getByOrderId($orderId);
    }

    /**
     * Get a single payment by ID.
     *
     * @param int $paymentId Payment ID
     * @return Payment
     * @throws PaymentException When payment not found
     */
    public function getPayment(int $paymentId): Payment
    {
        $payment = $this->paymentRepository->find($paymentId);

        if (!$payment) {
            throw PaymentException::notFound($paymentId);
        }

        return $payment;
    }

    /**
     * Process a payment for an order.
     *
     * Business rule: Payments can only be processed for confirmed orders.
     *
     * @param Order $order Order to pay for
     * @param PaymentDTO $dto Payment data
     * @return Payment Processed payment
     * @throws PaymentException When payment cannot be processed
     * @throws GatewayException When gateway fails
     */
    public function processPayment(Order $order, PaymentDTO $dto): Payment
    {
        // Business rule: Only confirmed orders can be paid
        if ($order->status !== OrderStatus::CONFIRMED) {
            throw PaymentException::orderNotConfirmed();
        }

        // Check if order already has a successful payment
        if ($this->paymentRepository->orderHasSuccessfulPayment($order->id)) {
            throw PaymentException::alreadyPaid();
        }

        return DB::transaction(function () use ($order, $dto) {
            // Create pending payment record
            $payment = $this->paymentRepository->create($dto);

            try {
                // Get the appropriate gateway (Strategy Pattern)
                $gateway = $this->gatewayFactory->make($dto->gateway->value);

                // Process payment through gateway
                $result = $gateway->processPayment($dto);

                // Update payment with result
                return $this->paymentRepository->updateWithResult($payment, $result);

            } catch (GatewayException $e) {
                // Mark payment as failed
                $this->paymentRepository->markAsFailed($payment, $e->getMessage());
                throw $e;
            } catch (\Exception $e) {
                // Mark payment as failed for unexpected errors
                $this->paymentRepository->markAsFailed($payment, $e->getMessage());
                throw PaymentException::processingFailed($e->getMessage());
            }
        });
    }

    /**
     * Get available payment methods.
     *
     * @return array<int, array{name: string, display_name: string, enabled: bool}>
     */
    public function getAvailablePaymentMethods(): array
    {
        return $this->gatewayFactory->getGatewayInfo();
    }

    /**
     * Check if a payment method/gateway is available.
     *
     * @param string $gatewayName Gateway name
     * @return bool True if available
     */
    public function isPaymentMethodAvailable(string $gatewayName): bool
    {
        try {
            $gateway = $this->gatewayFactory->make($gatewayName);
            return $gateway->isEnabled() && $gateway->validateConfiguration();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Calculate payment amount for an order.
     *
     * This could include additional logic for discounts, taxes, etc.
     *
     * @param Order $order Order to calculate for
     * @return float Payment amount
     */
    public function calculatePaymentAmount(Order $order): float
    {
        // Currently just returns total, but could be extended
        // to apply discounts, taxes, shipping, etc.
        return (float) $order->total_amount;
    }
}
