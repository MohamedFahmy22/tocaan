<?php

declare(strict_types=1);

namespace App\PaymentGateways;

use App\DTOs\PaymentDTO;
use App\Exceptions\GatewayException;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Str;

/**
 * Credit Card payment gateway implementation.
 *
 * This gateway handles credit card payments. In production, this would
 * integrate with a payment processor like Stripe, Braintree, or a bank's
 * payment gateway API.
 *
 * @see AbstractPaymentGateway
 */
class CreditCardGateway extends AbstractPaymentGateway
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'credit_card';
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return 'Credit Card';
    }

    /**
     * {@inheritDoc}
     */
    public function processPayment(PaymentDTO $payment): PaymentResult
    {
        $this->log('Processing credit card payment', [
            'amount' => $payment->amount,
            'order_id' => $payment->orderId,
        ]);

        try {
            // Validate configuration before processing
            if (!$this->validateConfiguration()) {
                throw GatewayException::invalidConfiguration($this->getName());
            }

            // In production, this would call the actual payment API
            // For now, we simulate the payment
            $success = $this->simulatePayment($payment);

            if ($success) {
                $transactionId = $this->generateTransactionId();

                $this->log('Credit card payment successful', [
                    'transaction_id' => $transactionId,
                    'amount' => $payment->amount,
                ]);

                return PaymentResult::success(
                    transactionId: $transactionId,
                    message: 'Credit card payment processed successfully'
                );
            }

            $this->log('Credit card payment declined');

            return PaymentResult::failure(
                message: 'Credit card payment was declined',
                metadata: ['reason' => 'Card declined by issuer']
            );

        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError('Credit card payment error', ['error' => $e->getMessage()]);
            throw GatewayException::communicationError($this->getName(), $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function refund(string $transactionId, float $amount): RefundResult
    {
        $this->log('Processing credit card refund', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);

        try {
            // Simulate refund processing
            $success = random_int(1, 10) <= 9;

            if ($success) {
                $refundId = 'REF-CC-' . strtoupper(Str::random(8));

                $this->log('Credit card refund successful', [
                    'refund_id' => $refundId,
                    'amount' => $amount,
                ]);

                return RefundResult::success(
                    refundId: $refundId,
                    amount: $amount,
                    message: 'Refund processed successfully'
                );
            }

            return RefundResult::failure('Refund could not be processed');

        } catch (\Exception $e) {
            $this->logError('Credit card refund error', ['error' => $e->getMessage()]);
            return RefundResult::failure('Refund failed: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateConfiguration(): bool
    {
        // In production, verify actual credentials
        // For sandbox mode, we're more lenient
        if ($this->isSandbox()) {
            return true;
        }

        return !empty($this->config['merchant_id'])
            && !empty($this->config['api_key']);
    }

    /**
     * Generate a unique transaction ID.
     */
    private function generateTransactionId(): string
    {
        return 'CC-' . strtoupper(Str::random(16));
    }
}
