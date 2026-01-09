<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Payment method enumeration for supported payment gateways.
 */
enum PaymentMethod: string
{
    case CREDIT_CARD = 'credit_card';
    case PAYPAL = 'paypal';
    case STRIPE = 'stripe';
    case BANK_TRANSFER = 'bank_transfer';

    /**
     * Get human-readable label for the payment method.
     */
    public function label(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'Credit Card',
            self::PAYPAL => 'PayPal',
            self::STRIPE => 'Stripe',
            self::BANK_TRANSFER => 'Bank Transfer',
        };
    }

    /**
     * Get all possible method values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
