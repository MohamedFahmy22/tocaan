<?php

declare(strict_types=1);

namespace App\PaymentGateways;

use App\DTOs\PaymentDTO;
use App\Exceptions\GatewayException;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Str;

/**
 * PayPal payment gateway implementation.
 *
 * This gateway handles PayPal payments. In production, this would
 * integrate with PayPal's REST API or SDK.
 *
 * @see AbstractPaymentGateway
 */
class PayPalGateway extends AbstractPaymentGateway
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'paypal';
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return 'PayPal';
    }

    /**
     * {@inheritDoc}
     */
    public function processPayment(PaymentDTO $payment): PaymentResult
    {
        $this->log('Processing PayPal payment', [
            'amount' => $payment->amount,
            'order_id' => $payment->orderId,
        ]);

        try {
            // Validate configuration before processing
            if (!$this->validateConfiguration()) {
                throw GatewayException::invalidConfiguration($this->getName());
            }

            // In production, this would:
            // 1. Create a PayPal order
            // 2. Redirect user to PayPal for approval
            // 3. Capture the payment after approval

            // For simulation, we process directly
            $success = $this->simulatePayment($payment);

            if ($success) {
                $transactionId = $this->generateTransactionId();

                $this->log('PayPal payment successful', [
                    'transaction_id' => $transactionId,
                    'amount' => $payment->amount,
                ]);

                return PaymentResult::success(
                    transactionId: $transactionId,
                    message: 'PayPal payment completed successfully'
                );
            }

            $this->log('PayPal payment failed');

            return PaymentResult::failure(
                message: 'PayPal payment could not be completed',
                metadata: ['reason' => 'Payment not approved']
            );

        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError('PayPal payment error', ['error' => $e->getMessage()]);
            throw GatewayException::communicationError($this->getName(), $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function refund(string $transactionId, float $amount): RefundResult
    {
        $this->log('Processing PayPal refund', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);

        try {
            // Simulate refund processing
            $success = random_int(1, 10) <= 9;

            if ($success) {
                $refundId = 'REF-PP-' . strtoupper(Str::random(10));

                $this->log('PayPal refund successful', [
                    'refund_id' => $refundId,
                    'amount' => $amount,
                ]);

                return RefundResult::success(
                    refundId: $refundId,
                    amount: $amount,
                    message: 'PayPal refund processed successfully'
                );
            }

            return RefundResult::failure('PayPal refund could not be processed');

        } catch (\Exception $e) {
            $this->logError('PayPal refund error', ['error' => $e->getMessage()]);
            return RefundResult::failure('PayPal refund failed: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateConfiguration(): bool
    {
        // In production, verify actual PayPal credentials
        if ($this->isSandbox()) {
            return true;
        }

        return !empty($this->config['client_id'])
            && !empty($this->config['client_secret']);
    }

    /**
     * Generate a PayPal-style transaction ID.
     */
    private function generateTransactionId(): string
    {
        return 'PP-' . strtoupper(Str::random(17));
    }

    /**
     * Get PayPal API base URL based on environment.
     */
    protected function getApiBaseUrl(): string
    {
        return $this->isSandbox()
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }
}
