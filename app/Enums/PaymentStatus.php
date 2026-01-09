<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Payment status enumeration for type-safe status handling.
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SUCCESSFUL => 'Successful',
            self::FAILED => 'Failed',
        };
    }

    /**
     * Check if payment is in a final state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::SUCCESSFUL, self::FAILED]);
    }

    /**
     * Get all possible status values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
