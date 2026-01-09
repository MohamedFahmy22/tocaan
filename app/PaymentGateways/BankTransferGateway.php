<?php

declare(strict_types=1);

namespace App\PaymentGateways;

use App\DTOs\PaymentDTO;
use App\Exceptions\GatewayException;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Str;

/**
 * Bank Transfer payment gateway implementation.
 *
 * This gateway handles bank transfer payments. In production, this would
 * integrate with banking APIs or payment processors that support bank transfers.
 *
 * @see AbstractPaymentGateway
 */
class BankTransferGateway extends AbstractPaymentGateway
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'bank_transfer';
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return 'Bank Transfer';
    }

    /**
     * {@inheritDoc}
     */
    public function processPayment(PaymentDTO $payment): PaymentResult
    {
        $this->log('Processing bank transfer payment', [
            'amount' => $payment->amount,
            'order_id' => $payment->orderId,
        ]);

        try {
            // Validate configuration before processing
            if (!$this->validateConfiguration()) {
                throw GatewayException::invalidConfiguration($this->getName());
            }

            // Bank transfers typically work differently:
            // 1. Generate a reference number
            // 2. Provide bank details to the customer
            // 3. Wait for confirmation (async process)

            // For simulation, we create a pending transfer
            $referenceNumber = $this->generateReferenceNumber();

            $this->log('Bank transfer initiated', [
                'reference_number' => $referenceNumber,
                'amount' => $payment->amount,
            ]);

            // Bank transfers are always "pending" initially
            return PaymentResult::success(
                transactionId: $referenceNumber,
                message: 'Bank transfer initiated. Please complete the transfer using the reference number.'
            );

        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError('Bank transfer error', ['error' => $e->getMessage()]);
            throw GatewayException::communicationError($this->getName(), $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function refund(string $transactionId, float $amount): RefundResult
    {
        $this->log('Processing bank transfer refund', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);

        try {
            // Bank transfer refunds require manual processing
            $refundId = 'REF-BT-' . strtoupper(Str::random(8));

            $this->log('Bank transfer refund initiated', [
                'refund_id' => $refundId,
                'amount' => $amount,
            ]);

            return RefundResult::success(
                refundId: $refundId,
                amount: $amount,
                message: 'Bank transfer refund initiated. Please allow 3-5 business days for processing.'
            );

        } catch (\Exception $e) {
            $this->logError('Bank transfer refund error', ['error' => $e->getMessage()]);
            return RefundResult::failure('Bank transfer refund failed: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateConfiguration(): bool
    {
        // Bank transfers may require bank account details
        if ($this->isSandbox()) {
            return true;
        }

        return !empty($this->config['bank_name'])
            && !empty($this->config['account_number']);
    }

    /**
     * Generate a unique reference number for the bank transfer.
     */
    private function generateReferenceNumber(): string
    {
        return 'BT-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }
}
