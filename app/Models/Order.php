<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'customer_id',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'shipping_address',
        'billing_address',
        'shipping_method',
        'notes',
        'customer_notes',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'tracking_number',
        'coupon_code',
        'admin_notes',
        'contact_status',
        'contacted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'contacted_at' => 'datetime',
    ];

    /**
     * Order status constants.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Payment status constants.
     */
    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_FAILED = 'failed';
    const PAYMENT_STATUS_REFUNDED = 'refunded';
    const PAYMENT_STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    /**
     * Available order statuses with display names.
     */
    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_SHIPPED => 'Shipped',
        self::STATUS_DELIVERED => 'Delivered',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_REFUNDED => 'Refunded',
    ];

    /**
     * Available payment statuses with display names.
     */
    public const PAYMENT_STATUSES = [
        self::PAYMENT_STATUS_PENDING => 'Pending',
        self::PAYMENT_STATUS_PAID => 'Paid',
        self::PAYMENT_STATUS_FAILED => 'Failed',
        self::PAYMENT_STATUS_REFUNDED => 'Refunded',
        self::PAYMENT_STATUS_PARTIALLY_REFUNDED => 'Partially Refunded',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate order number if not provided
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Get the customer that owns this order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the order items for this order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scope a query to only include orders with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include orders with a specific payment status.
     */
    public function scopeWithPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * Scope a query to only include orders for a specific customer.
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope a query to only include orders created within a date range.
     */
    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include confirmed orders.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope a query to only include shipped orders.
     */
    public function scopeShipped($query)
    {
        return $query->where('status', self::STATUS_SHIPPED);
    }

    /**
     * Scope a query to only include delivered orders.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    /**
     * Scope a query to only include cancelled orders.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope a query to only include paid orders.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_STATUS_PAID);
    }

    /**
     * Get the order status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'Unknown';
    }

    /**
     * Get the payment status display name.
     */
    public function getPaymentStatusDisplayNameAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? 'Unknown';
    }

    /**
     * Get the formatted total amount.
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return number_format($this->total_amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get the formatted subtotal amount.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return number_format($this->subtotal, 2) . ' ' . $this->currency;
    }

    /**
     * Get the formatted tax amount.
     */
    public function getFormattedTaxAmountAttribute(): string
    {
        return number_format($this->tax_amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get the formatted shipping amount.
     */
    public function getFormattedShippingAmountAttribute(): string
    {
        return number_format($this->shipping_amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get the formatted discount amount.
     */
    public function getFormattedDiscountAmountAttribute(): string
    {
        return number_format($this->discount_amount, 2) . ' ' . $this->currency;
    }

    /**
     * Check if the order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the order is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * Check if the order is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if the order is shipped.
     */
    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    /**
     * Check if the order is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Check if the order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if the order is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Check if the order is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    /**
     * Check if the order payment is pending.
     */
    public function isPaymentPending(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PENDING;
    }

    /**
     * Check if the order payment failed.
     */
    public function isPaymentFailed(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_FAILED;
    }

    /**
     * Confirm the order.
     */
    public function confirm(): bool
    {
        return $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Mark the order as processing.
     */
    public function markAsProcessing(): bool
    {
        return $this->update(['status' => self::STATUS_PROCESSING]);
    }

    /**
     * Ship the order.
     */
    public function ship(string $trackingNumber = null): bool
    {
        return $this->update([
            'status' => self::STATUS_SHIPPED,
            'shipped_at' => now(),
            'tracking_number' => $trackingNumber,
        ]);
    }

    /**
     * Mark the order as delivered.
     */
    public function markAsDelivered(): bool
    {
        return $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Cancel the order.
     */
    public function cancel(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Mark the order as paid.
     */
    public function markAsPaid(): bool
    {
        return $this->update(['payment_status' => self::PAYMENT_STATUS_PAID]);
    }

    /**
     * Mark the order payment as failed.
     */
    public function markPaymentAsFailed(): bool
    {
        return $this->update(['payment_status' => self::PAYMENT_STATUS_FAILED]);
    }

    /**
     * Get the total items count.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->orderItems()->sum('quantity');
    }

    /**
     * Get the order duration in days.
     */
    public function getDurationInDaysAttribute(): ?int
    {
        if ($this->delivered_at) {
            return $this->created_at->diffInDays($this->delivered_at);
        }
        
        return null;
    }

    /**
     * Get the shipping address formatted string.
     */
    public function getFormattedShippingAddressAttribute(): string
    {
        if (!$this->shipping_address) {
            return 'Not specified';
        }

        $address = $this->shipping_address;
        $parts = [];

        if (isset($address['street'])) $parts[] = $address['street'];
        if (isset($address['city'])) $parts[] = $address['city'];
        if (isset($address['state'])) $parts[] = $address['state'];
        if (isset($address['country'])) $parts[] = $address['country'];
        if (isset($address['postal_code'])) $parts[] = $address['postal_code'];

        return implode(', ', $parts);
    }

    /**
     * Get the billing address formatted string.
     */
    public function getFormattedBillingAddressAttribute(): string
    {
        if (!$this->billing_address) {
            return 'Same as shipping address';
        }

        $address = $this->billing_address;
        $parts = [];

        if (isset($address['street'])) $parts[] = $address['street'];
        if (isset($address['city'])) $parts[] = $address['city'];
        if (isset($address['state'])) $parts[] = $address['state'];
        if (isset($address['country'])) $parts[] = $address['country'];
        if (isset($address['postal_code'])) $parts[] = $address['postal_code'];

        return implode(', ', $parts);
    }
}
