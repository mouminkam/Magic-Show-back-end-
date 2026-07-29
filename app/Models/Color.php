<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Color extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'color_code',
        'hex_code',
        'rgb_code',
        'category',
        'is_active',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * العلاقة مع المنتجات
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_colors')
                    ->withPivot(['is_primary'])
                    ->withTimestamps();
    }

    /**
     * العلاقة مع المنتجات النشطة
     */
    public function activeProducts(): BelongsToMany
    {
        return $this->products()->where('is_active', true);
    }

    /**
     * Scope للألوان النشطة
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للترتيب
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope حسب الفئة
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Accessor للحصول على RGB من HEX
     */
    public function getRgbArrayAttribute(): array
    {
        $hex = ltrim(($this->color_code ?: $this->hex_code) ?? '', '#');
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Accessor للحصول على RGB كـ string
     */
    public function getRgbStringAttribute(): string
    {
        $rgb = $this->rgb_array;
        return "{$rgb['r']},{$rgb['g']},{$rgb['b']}";
    }

    /**
     * Accessor للحصول على CSS style
     */
    public function getCssStyleAttribute(): string
    {
        $colorCode = $this->color_code ?: $this->hex_code;
        return "background-color: {$colorCode};";
    }

    /**
     * Accessor للحصول على text color (أسود أو أبيض حسب درجة اللون)
     */
    public function getTextColorAttribute(): string
    {
        $rgb = $this->rgb_array;
        $brightness = (($rgb['r'] * 299) + ($rgb['g'] * 587) + ($rgb['b'] * 114)) / 1000;
        return $brightness > 128 ? '#000000' : '#FFFFFF';
    }

    /**
     * الحصول على عدد المنتجات التي تستخدم هذا اللون
     */
    public function productsCount(): int
    {
        return $this->products()->count();
    }

    /**
     * التحقق من استخدام اللون
     */
    public function isUsed(): bool
    {
        return $this->products()->exists();
    }

    /**
     * Boot method لإعداد RGB من HEX
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($color) {
            if ($color->color_code && !$color->hex_code) {
                $color->hex_code = $color->color_code;
            }

            if ($color->hex_code && !$color->color_code) {
                $color->color_code = $color->hex_code;
            }

            if ($color->color_code) {
                $normalized = strtoupper(ltrim($color->color_code, '#'));
                if (strlen($normalized) === 6 && ctype_xdigit($normalized)) {
                    $color->color_code = '#' . $normalized;
                }
            }

            if ($color->hex_code) {
                $normalized = strtoupper(ltrim($color->hex_code, '#'));
                if (strlen($normalized) === 6 && ctype_xdigit($normalized)) {
                    $color->hex_code = '#' . $normalized;
                }
            }

            if (($color->color_code || $color->hex_code) && !$color->rgb_code) {
                $hex = ltrim(($color->color_code ?: $color->hex_code) ?? '', '#');
                if (strlen($hex) === 6) {
                    $color->rgb_code = implode(',', [
                        hexdec(substr($hex, 0, 2)),
                        hexdec(substr($hex, 2, 2)),
                        hexdec(substr($hex, 4, 2)),
                    ]);
                }
            }
        });
    }
}
