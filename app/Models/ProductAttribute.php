<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductAttribute extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'is_required',
        'is_filterable',
        'is_visible',
        'sort_order',
        'options',
        'validation_rules',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_required' => 'boolean',
        'is_filterable' => 'boolean',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
        'options' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name if not provided
        static::creating(function ($attribute) {
            if (empty($attribute->slug)) {
                $attribute->slug = Str::slug($attribute->name);
            }
        });

        // Update slug when name changes
        static::updating(function ($attribute) {
            if ($attribute->isDirty('name') && empty($attribute->slug)) {
                $attribute->slug = Str::slug($attribute->name);
            }
        });
    }

    /**
     * Get the attribute values for this attribute.
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * Scope a query to only include required attributes.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope a query to only include filterable attributes.
     */
    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    /**
     * Scope a query to only include visible attributes.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope a query to filter by attribute type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if this attribute has predefined options.
     */
    public function hasOptions(): bool
    {
        return in_array($this->type, ['select', 'multiselect']) && !empty($this->options);
    }

    /**
     * Get the options for select/multiselect attributes.
     */
    public function getOptionsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set the options for select/multiselect attributes.
     */
    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get the validation rules for this attribute.
     */
    public function getValidationRulesAttribute($value)
    {
        return $value ? explode('|', $value) : [];
    }

    /**
     * Set the validation rules for this attribute.
     */
    public function setValidationRulesAttribute($value)
    {
        $this->attributes['validation_rules'] = is_array($value) ? implode('|', $value) : $value;
    }

    /**
     * Check if this attribute is a select type.
     */
    public function isSelectType(): bool
    {
        return in_array($this->type, ['select', 'multiselect']);
    }

    /**
     * Check if this attribute is a text type.
     */
    public function isTextType(): bool
    {
        return in_array($this->type, ['text', 'number']);
    }

    /**
     * Check if this attribute is a boolean type.
     */
    public function isBooleanType(): bool
    {
        return $this->type === 'boolean';
    }

    /**
     * Check if this attribute is a date type.
     */
    public function isDateType(): bool
    {
        return $this->type === 'date';
    }
}
