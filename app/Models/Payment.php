<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Payment model representing payments for orders.
 *
 * @property int $id
 * @property int $order_id
 * @property string $payment_number
 * @property string $gateway
 * @property string|null $gateway_transaction_id
 * @property float $amount
 * @property PaymentStatus $status
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Order $order
 */
class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'payment_number',
        'gateway',
        'gateway_transaction_id',
        'amount',
        'status',
        'metadata',
        'processed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Payment $payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber();
            }
        });
    }

    /**
     * Generate a unique payment number.
     */
    public static function generatePaymentNumber(): string
    {
        do {
            $number = 'PAY-' . strtoupper(Str::random(10));
        } while (self::where('payment_number', $number)->exists());

        return $number;
    }

    /**
     * Get the order associated with this payment.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    /**
     * Check if payment was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::SUCCESSFUL;
    }

    /**
     * Check if payment failed.
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    /**
     * Get the payment method enum.
     */
    public function getPaymentMethod(): ?PaymentMethod
    {
        return PaymentMethod::tryFrom($this->gateway);
    }

    /**
     * Scope to filter by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Payment> $query
     * @param PaymentStatus|string $status
     * @return \Illuminate\Database\Eloquent\Builder<Payment>
     */
    public function scopeStatus($query, PaymentStatus|string $status)
    {
        $statusValue = $status instanceof PaymentStatus ? $status->value : $status;
        return $query->where('status', $statusValue);
    }

    /**
     * Scope to filter by gateway.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Payment> $query
     * @param string $gateway
     * @return \Illuminate\Database\Eloquent\Builder<Payment>
     */
    public function scopeGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope to filter by order.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Payment> $query
     * @param int $orderId
     * @return \Illuminate\Database\Eloquent\Builder<Payment>
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }
}
