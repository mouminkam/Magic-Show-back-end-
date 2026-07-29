<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Coupon extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_amount',
        'maximum_discount',
        'usage_limit',
        'usage_limit_per_customer',
        'used_count',
        'is_active',
        'is_public',
        'starts_at',
        'expires_at',
        'applicable_products',
        'applicable_categories',
        'excluded_products',
        'excluded_categories',
        'customer_groups',
        'terms_and_conditions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_limit_per_customer' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'excluded_products' => 'array',
        'excluded_categories' => 'array',
        'customer_groups' => 'array',
    ];

    /**
     * Coupon type constants.
     */
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED_AMOUNT = 'fixed_amount';
    const TYPE_FREE_SHIPPING = 'free_shipping';

    /**
     * Available coupon types with display names.
     */
    public const TYPES = [
        self::TYPE_PERCENTAGE => 'Percentage Discount',
        self::TYPE_FIXED_AMOUNT => 'Fixed Amount Discount',
        self::TYPE_FREE_SHIPPING => 'Free Shipping',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate code if not provided
        static::creating(function ($coupon) {
            if (empty($coupon->code)) {
                $coupon->code = strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Get the coupon usage records for this coupon.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Scope a query to only include active coupons.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include public coupons.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include valid coupons (not expired).
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        })->where(function ($q) {
            $q->whereNull('starts_at')
              ->orWhere('starts_at', '<=', now());
        });
    }

    /**
     * Scope a query to only include available coupons (not exceeded usage limit).
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('usage_limit')
              ->orWhereRaw('used_count < usage_limit');
        });
    }

    /**
     * Scope a query to filter by coupon type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include percentage coupons.
     */
    public function scopePercentage($query)
    {
        return $query->where('type', self::TYPE_PERCENTAGE);
    }

    /**
     * Scope a query to only include fixed amount coupons.
     */
    public function scopeFixedAmount($query)
    {
        return $query->where('type', self::TYPE_FIXED_AMOUNT);
    }

    /**
     * Scope a query to only include free shipping coupons.
     */
    public function scopeFreeShipping($query)
    {
        return $query->where('type', self::TYPE_FREE_SHIPPING);
    }

    /**
     * Scope a query to only include expired coupons.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope a query to only include coupons that haven't started yet.
     */
    public function scopeNotStarted($query)
    {
        return $query->where('starts_at', '>', now());
    }

    /**
     * Get the coupon type display name.
     */
    public function getTypeDisplayNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? 'Unknown';
    }

    /**
     * Get the formatted value.
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            return $this->value . '%';
        } elseif ($this->type === self::TYPE_FIXED_AMOUNT) {
            return '$' . number_format($this->value, 2);
        } else {
            return 'Free';
        }
    }

    /**
     * Get the formatted minimum amount.
     */
    public function getFormattedMinimumAmountAttribute(): ?string
    {
        return $this->minimum_amount ? '$' . number_format($this->minimum_amount, 2) : null;
    }

    /**
     * Get the formatted maximum discount.
     */
    public function getFormattedMaximumDiscountAttribute(): ?string
    {
        return $this->maximum_discount ? '$' . number_format($this->maximum_discount, 2) : null;
    }

    /**
     * Check if the coupon is valid (not expired and active).
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the coupon is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the coupon has started.
     */
    public function hasStarted(): bool
    {
        return !$this->starts_at || $this->starts_at->isPast();
    }

    /**
     * Check if the coupon is available (not exceeded usage limit).
     */
    public function isAvailable(): bool
    {
        return !$this->usage_limit || $this->used_count < $this->usage_limit;
    }

    /**
     * Check if the coupon can be used by a specific customer.
     */
    public function canBeUsedBy(Customer $customer): bool
    {
        if (!$this->isValid() || !$this->isAvailable()) {
            return false;
        }

        // Check customer groups if specified
        if ($this->customer_groups && count($this->customer_groups) > 0) {
            $customerGroup = $customer->customer_group ?? $customer->group ?? null;
            if (!$customerGroup || !in_array($customerGroup, $this->customer_groups)) {
                return false;
            }
        }

        // Check usage limit per customer
        $customerUsageCount = $this->usages()
            ->where('customer_id', $customer->id)
            ->count();

        return $customerUsageCount < $this->usage_limit_per_customer;
    }

    /**
     * Check if the coupon can be applied to a specific order amount.
     */
    public function canBeAppliedToAmount(float $amount): bool
    {
        if (!$this->minimum_amount) {
            return true;
        }

        return $amount >= $this->minimum_amount;
    }

    /**
     * Calculate the discount amount for a given order amount.
     */
    public function calculateDiscount(float $orderAmount): float
    {
        if (!$this->canBeAppliedToAmount($orderAmount)) {
            return 0;
        }

        $discount = 0;

        switch ($this->type) {
            case self::TYPE_PERCENTAGE:
                $discount = ($orderAmount * $this->value) / 100;
                if ($this->maximum_discount && $discount > $this->maximum_discount) {
                    $discount = $this->maximum_discount;
                }
                break;

            case self::TYPE_FIXED_AMOUNT:
                $discount = min($this->value, $orderAmount);
                break;

            case self::TYPE_FREE_SHIPPING:
                // This would need to be handled differently based on shipping cost
                $discount = 0;
                break;
        }

        return round($discount, 2);
    }

    /**
     * Increment the usage count.
     */
    public function incrementUsage(): bool
    {
        return $this->increment('used_count');
    }

    /**
     * Decrement the usage count.
     */
    public function decrementUsage(): bool
    {
        if ($this->used_count > 0) {
            return $this->decrement('used_count');
        }
        return true;
    }

    /**
     * Get the remaining usage count.
     */
    public function getRemainingUsageAttribute(): ?int
    {
        if (!$this->usage_limit) {
            return null; // Unlimited
        }

        return max(0, $this->usage_limit - $this->used_count);
    }

    /**
     * Get the usage percentage.
     */
    public function getUsagePercentageAttribute(): ?float
    {
        if (!$this->usage_limit) {
            return null;
        }

        return round(($this->used_count / $this->usage_limit) * 100, 2);
    }

    /**
     * Check if the coupon applies to a specific product.
     */
    public function appliesToProduct(int $productId): bool
    {
        // If no specific products are set, it applies to all products
        if (!$this->applicable_products) {
            return true;
        }

        return in_array($productId, $this->applicable_products);
    }

    /**
     * Check if the coupon applies to a specific category.
     */
    public function appliesToCategory(int $categoryId): bool
    {
        // If no specific categories are set, it applies to all categories
        if (!$this->applicable_categories) {
            return true;
        }

        return in_array($categoryId, $this->applicable_categories);
    }

    /**
     * Check if the coupon excludes a specific product.
     */
    public function excludesProduct(int $productId): bool
    {
        if (!$this->excluded_products) {
            return false;
        }

        return in_array($productId, $this->excluded_products);
    }

    /**
     * Check if the coupon excludes a specific category.
     */
    public function excludesCategory(int $categoryId): bool
    {
        if (!$this->excluded_categories) {
            return false;
        }

        return in_array($categoryId, $this->excluded_categories);
    }

    /**
     * Get the status of the coupon.
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if (!$this->hasStarted()) {
            return 'not_started';
        }

        if (!$this->isAvailable()) {
            return 'exhausted';
        }

        return 'active';
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return match($this->status) {
            'inactive' => 'Inactive',
            'expired' => 'Expired',
            'not_started' => 'Not Started',
            'exhausted' => 'Exhausted',
            'active' => 'Active',
            default => 'Unknown'
        };
    }
}
