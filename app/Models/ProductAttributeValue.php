<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductAttributeValue extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_attribute_id',
        'value',
        'display_value',
        'color_code',
        'image',
        'sort_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product attribute that owns this value.
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    /**
     * Get the products that have this attribute value.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_product_attribute_values');
    }

    /**
     * Scope a query to only include active values.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('value');
    }

    /**
     * Scope a query to filter by attribute.
     */
    public function scopeForAttribute($query, $attributeId)
    {
        return $query->where('product_attribute_id', $attributeId);
    }

    /**
     * Get the display value or fallback to the value.
     */
    public function getDisplayValueAttribute($value): string
    {
        return $value ?: $this->value;
    }

    /**
     * Check if this value has a color code.
     */
    public function hasColorCode(): bool
    {
        return !empty($this->color_code);
    }

    /**
     * Check if this value has an image.
     */
    public function hasImage(): bool
    {
        return !empty($this->image);
    }

    /**
     * Get the formatted color code (with # prefix if missing).
     */
    public function getFormattedColorCodeAttribute(): ?string
    {
        if (!$this->color_code) {
            return null;
        }

        return str_starts_with($this->color_code, '#') ? $this->color_code : '#' . $this->color_code;
    }

    /**
     * Get the image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    /**
     * Check if this value is for a color attribute.
     */
    public function isColorValue(): bool
    {
        return $this->attribute && $this->attribute->slug === 'color';
    }

    /**
     * Check if this value is for a size attribute.
     */
    public function isSizeValue(): bool
    {
        return $this->attribute && $this->attribute->slug === 'size';
    }

    /**
     * Check if this value is for a material attribute.
     */
    public function isMaterialValue(): bool
    {
        return $this->attribute && $this->attribute->slug === 'material';
    }
}
