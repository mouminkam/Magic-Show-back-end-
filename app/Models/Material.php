<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Material extends Model
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
        'category',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($material) {
            if (empty($material->slug)) {
                $material->slug = Str::slug($material->name);
            }
        });

        static::updating(function ($material) {
            if ($material->isDirty('name') && empty($material->slug)) {
                $material->slug = Str::slug($material->name);
            }
        });
    }

    /**
     * Get the products for the material.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the active products for the material.
     */
    public function activeProducts(): HasMany
    {
        return $this->hasMany(Product::class)->where('is_active', true);
    }

    /**
     * Scope a query to only include active materials.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter materials by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to order materials by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }


    /**
     * Get the count of products for this material.
     */
    public function productsCount(): int
    {
        return $this->products()->count();
    }

    /**
     * Check if the material has any products.
     */
    public function isUsed(): bool
    {
        return $this->products()->count() > 0;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get available material categories.
     */
    public static function getCategories(): array
    {
        return [
            'leather' => 'جلد',
            'fabric' => 'قماش',
            'synthetic' => 'صناعي',
            'rubber' => 'مطاط',
            'canvas' => 'كانفاس',
            'mesh' => 'شبكي',
            'other' => 'أخرى'
        ];
    }

}
