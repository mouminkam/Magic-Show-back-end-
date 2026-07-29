<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'coupon_usage';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'coupon_id',
        'customer_id',
        'order_id',
        'discount_amount',
        'order_total',
        'order_total_after_discount',
        'coupon_code',
        'used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_amount' => 'decimal:2',
        'order_total' => 'decimal:2',
        'order_total_after_discount' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set used_at timestamp when creating
        static::creating(function ($couponUsage) {
            if (empty($couponUsage->used_at)) {
                $couponUsage->used_at = now();
            }
        });
    }

    /**
     * Get the coupon that owns this usage record.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the customer that used this coupon.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the order that this coupon was used for.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope a query to only include usages for a specific coupon.
     */
    public function scopeForCoupon($query, $couponId)
    {
        return $query->where('coupon_id', $couponId);
    }

    /**
     * Scope a query to only include usages by a specific customer.
     */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope a query to only include usages for a specific order.
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope a query to only include usages within a date range.
     */
    public function scopeUsedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('used_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include recent usages.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('used_at', '>=', now()->subDays($days));
    }

    /**
     * Get the formatted discount amount.
     */
    public function getFormattedDiscountAmountAttribute(): string
    {
        return '$' . number_format($this->discount_amount, 2);
    }

    /**
     * Get the formatted order total.
     */
    public function getFormattedOrderTotalAttribute(): string
    {
        return '$' . number_format($this->order_total, 2);
    }

    /**
     * Get the formatted order total after discount.
     */
    public function getFormattedOrderTotalAfterDiscountAttribute(): string
    {
        return '$' . number_format($this->order_total_after_discount, 2);
    }

    /**
     * Get the discount percentage.
     */
    public function getDiscountPercentageAttribute(): float
    {
        if ($this->order_total == 0) {
            return 0;
        }

        return round(($this->discount_amount / $this->order_total) * 100, 2);
    }

    /**
     * Get the formatted discount percentage.
     */
    public function getFormattedDiscountPercentageAttribute(): string
    {
        return $this->discount_percentage . '%';
    }

    /**
     * Get the savings amount (same as discount amount).
     */
    public function getSavingsAmountAttribute(): float
    {
        return $this->discount_amount;
    }

    /**
     * Get the formatted savings amount.
     */
    public function getFormattedSavingsAmountAttribute(): string
    {
        return '$' . number_format($this->savings_amount, 2);
    }

    /**
     * Check if this usage was recent.
     */
    public function isRecent(int $days = 30): bool
    {
        return $this->used_at->isAfter(now()->subDays($days));
    }

    /**
     * Get the time since usage.
     */
    public function getTimeSinceUsageAttribute(): string
    {
        return $this->used_at->diffForHumans();
    }

    /**
     * Get the usage summary.
     */
    public function getUsageSummaryAttribute(): string
    {
        return "Used {$this->coupon_code} on {$this->used_at->format('M j, Y')} for {$this->formatted_discount_amount} discount";
    }
}
