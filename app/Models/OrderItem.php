<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'total_price',
        'discount_amount',
        'tax_amount',
        'product_attributes',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'product_attributes' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate total_price when quantity or unit_price changes
        static::saving(function ($orderItem) {
            $orderItem->total_price = $orderItem->quantity * $orderItem->unit_price;
        });
    }

    /**
     * Get the order that owns this order item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product that this order item references.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope a query to only include items for a specific order.
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope a query to only include items for a specific product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope a query to only include items with a specific quantity.
     */
    public function scopeWithQuantity($query, $quantity)
    {
        return $query->where('quantity', $quantity);
    }

    /**
     * Scope a query to only include items above a specific quantity.
     */
    public function scopeWithQuantityAbove($query, $quantity)
    {
        return $query->where('quantity', '>', $quantity);
    }

    /**
     * Scope a query to only include items below a specific quantity.
     */
    public function scopeWithQuantityBelow($query, $quantity)
    {
        return $query->where('quantity', '<', $quantity);
    }

    /**
     * Scope a query to only include items within a price range.
     */
    public function scopePriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('unit_price', [$minPrice, $maxPrice]);
    }

    /**
     * Get the formatted unit price.
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return '$' . number_format($this->unit_price, 2);
    }

    /**
     * Get the formatted total price.
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return '$' . number_format($this->total_price, 2);
    }

    /**
     * Get the formatted discount amount.
     */
    public function getFormattedDiscountAmountAttribute(): string
    {
        return '$' . number_format($this->discount_amount, 2);
    }

    /**
     * Get the formatted tax amount.
     */
    public function getFormattedTaxAmountAttribute(): string
    {
        return '$' . number_format($this->tax_amount, 2);
    }

    /**
     * Get the net price (total price - discount + tax).
     */
    public function getNetPriceAttribute(): float
    {
        return $this->total_price - $this->discount_amount + $this->tax_amount;
    }

    /**
     * Get the formatted net price.
     */
    public function getFormattedNetPriceAttribute(): string
    {
        return '$' . number_format($this->net_price, 2);
    }

    /**
     * Get the discount percentage.
     */
    public function getDiscountPercentageAttribute(): float
    {
        if ($this->total_price == 0) {
            return 0;
        }

        return round(($this->discount_amount / $this->total_price) * 100, 2);
    }

    /**
     * Get the tax percentage.
     */
    public function getTaxPercentageAttribute(): float
    {
        if ($this->total_price == 0) {
            return 0;
        }

        return round(($this->tax_amount / $this->total_price) * 100, 2);
    }

    /**
     * Get the product attributes as a formatted string.
     */
    public function getFormattedAttributesAttribute(): string
    {
        if (!$this->product_attributes || empty($this->product_attributes)) {
            return 'No attributes';
        }

        $formatted = [];
        foreach ($this->product_attributes as $key => $value) {
            $formatted[] = ucfirst($key) . ': ' . $value;
        }

        return implode(', ', $formatted);
    }

    /**
     * Check if this item has a discount.
     */
    public function hasDiscount(): bool
    {
        return $this->discount_amount > 0;
    }

    /**
     * Check if this item has tax.
     */
    public function hasTax(): bool
    {
        return $this->tax_amount > 0;
    }

    /**
     * Check if this item has product attributes.
     */
    public function hasAttributes(): bool
    {
        return !empty($this->product_attributes);
    }

    /**
     * Get a specific product attribute value.
     */
    public function getProductAttributeValue(string $attributeName): ?string
    {
        return $this->product_attributes[$attributeName] ?? null;
    }

    /**
     * Set a specific product attribute value.
     */
    public function setProductAttributeValue(string $attributeName, string $value): void
    {
        $attributes = $this->product_attributes ?? [];
        $attributes[$attributeName] = $value;
        $this->product_attributes = $attributes;
    }

    /**
     * Get the profit margin for this item.
     */
    public function getProfitMarginAttribute(): ?float
    {
        if (!$this->product || !$this->product->cost_price) {
            return null;
        }

        $profit = $this->unit_price - $this->product->cost_price;
        return round(($profit / $this->product->cost_price) * 100, 2);
    }

    /**
     * Get the formatted profit margin.
     */
    public function getFormattedProfitMarginAttribute(): ?string
    {
        return $this->profit_margin ? $this->profit_margin . '%' : null;
    }

    /**
     * Get the total profit for this item.
     */
    public function getTotalProfitAttribute(): ?float
    {
        if (!$this->product || !$this->product->cost_price) {
            return null;
        }

        $unitProfit = $this->unit_price - $this->product->cost_price;
        return $unitProfit * $this->quantity;
    }

    /**
     * Get the formatted total profit.
     */
    public function getFormattedTotalProfitAttribute(): ?string
    {
        return $this->total_profit ? '$' . number_format($this->total_profit, 2) : null;
    }
}
