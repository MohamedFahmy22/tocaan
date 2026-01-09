<?php

declare(strict_types=1);

namespace App\PaymentGateways;

use App\DTOs\PaymentDTO;
use App\Exceptions\GatewayException;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;
use Illuminate\Support\Str;

/**
 * Stripe payment gateway implementation.
 *
 * This gateway handles Stripe payments. In production, this would
 * integrate with Stripe's API using the official PHP SDK.
 *
 * @see AbstractPaymentGateway
 */
class StripeGateway extends AbstractPaymentGateway
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'stripe';
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return 'Stripe';
    }

    /**
     * {@inheritDoc}
     */
    public function processPayment(PaymentDTO $payment): PaymentResult
    {
        $this->log('Processing Stripe payment', [
            'amount' => $payment->amount,
            'order_id' => $payment->orderId,
        ]);

        try {
            // Validate configuration before processing
            if (!$this->validateConfiguration()) {
                throw GatewayException::invalidConfiguration($this->getName());
            }

            // In production, this would:
            // 1. Create a Stripe PaymentIntent
            // 2. Confirm the payment with the provided payment method
            // 3. Handle 3D Secure if required

            // For simulation, we process directly
            $success = $this->simulatePayment($payment);

            if ($success) {
                $transactionId = $this->generateTransactionId();

                $this->log('Stripe payment successful', [
                    'transaction_id' => $transactionId,
                    'amount' => $payment->amount,
                ]);

                return PaymentResult::success(
                    transactionId: $transactionId,
                    message: 'Stripe payment completed successfully'
                );
            }

            $this->log('Stripe payment failed');

            return PaymentResult::failure(
                message: 'Stripe payment could not be completed',
                metadata: ['reason' => 'Payment failed']
            );

        } catch (GatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError('Stripe payment error', ['error' => $e->getMessage()]);
            throw GatewayException::communicationError($this->getName(), $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function refund(string $transactionId, float $amount): RefundResult
    {
        $this->log('Processing Stripe refund', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);

        try {
            // Simulate refund processing
            $success = random_int(1, 10) <= 9;

            if ($success) {
                $refundId = 're_' . strtoupper(Str::random(24));

                $this->log('Stripe refund successful', [
                    'refund_id' => $refundId,
                    'amount' => $amount,
                ]);

                return RefundResult::success(
                    refundId: $refundId,
                    amount: $amount,
                    message: 'Stripe refund processed successfully'
                );
            }

            return RefundResult::failure('Stripe refund could not be processed');

        } catch (\Exception $e) {
            $this->logError('Stripe refund error', ['error' => $e->getMessage()]);
            return RefundResult::failure('Stripe refund failed: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateConfiguration(): bool
    {
        // In production, verify actual Stripe credentials
        if ($this->isSandbox()) {
            return true;
        }

        return !empty($this->config['secret_key'])
            && !empty($this->config['publishable_key']);
    }

    /**
     * Generate a Stripe-style transaction ID.
     */
    private function generateTransactionId(): string
    {
        return 'pi_' . strtoupper(Str::random(24));
    }
}
