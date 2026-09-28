<?php

namespace App\Models;

use App\Models\Concerns\SyncsLegacyLocaleBaseFields;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use SyncsLegacyLocaleBaseFields;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_name',
        'customer_name_ar',
        'customer_name_en',
        'customer_image',
        'customer_image_thumb',
        'text',
        'text_ar',
        'text_en',
        'rating',
        'is_featured',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope a query to only include featured testimonials.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    /**
     * Get the customer image URL.
     */
    public function getCustomerImageUrlAttribute(): ?string
    {
        return $this->customer_image_path;
    }

    /**
     * Get the customer thumbnail image URL.
     */
    public function getCustomerImageThumbUrlAttribute(): ?string
    {
        return $this->customer_image_thumb_path ?: $this->customer_image_path;
    }

    /**
     * Normalize legacy stored values to a relative storage path.
     * Examples it handles:
     * - testimonials/original/foo.jpg
     * - /storage/testimonials/original/foo.jpg
     * - http://localhost:8000/storage/testimonials/original/foo.jpg?v=123
     * - https://api.yourdomain.com/storage/testimonials/original/foo.jpg
     */
    protected function normalizeStoragePath(?string $value): ?string
    {
        if (!$value) return null;
        $v = trim($value);
        if ($v === '') return null;

        $storagePos = stripos($v, '/storage/');
        if ($storagePos !== false) {
            $path = substr($v, $storagePos);
            $qPos = strpos($path, '?');
            if ($qPos !== false) {
                $path = substr($path, 0, $qPos);
            }
            return $path;
        }

        $qPos = strpos($v, '?');
        if ($qPos !== false) {
            $v = substr($v, 0, $qPos);
        }

        if (str_starts_with($v, 'http://') || str_starts_with($v, 'https://')) {
            return null;
        }

        if (str_starts_with($v, '/')) {
            return $v;
        }

        return '/storage/' . ltrim($v, '/');
    }

    /**
     * Customer image path (relative).
     */
    public function getCustomerImagePathAttribute(): ?string
    {
        return $this->normalizeStoragePath($this->customer_image);
    }

    /**
     * Customer thumbnail image path (relative).
     */
    public function getCustomerImageThumbPathAttribute(): ?string
    {
        return $this->normalizeStoragePath($this->customer_image_thumb);
    }

    protected function getLocaleBaseFieldMap(): array
    {
        return [
            'customer_name' => ['customer_name_en', 'customer_name_ar'],
            'text' => ['text_en', 'text_ar'],
        ];
    }
}
