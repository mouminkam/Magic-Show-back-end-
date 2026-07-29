<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'filename',
        'path',
        'thumb_path',
        'medium_path',
        'full_path',
        'alt_text',
        'size',
        'is_primary',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'size' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get the product that owns the image.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the full URL for the image.
     */
    public function getUrlAttribute(): string
    {
        return $this->full_url;
    }

    public function getThumbUrlAttribute(): string
    {
        if ($this->thumb_path && Storage::disk('public')->exists($this->thumb_path)) {
            return Storage::url($this->thumb_path);
        }

        if ($this->full_path && Storage::disk('public')->exists($this->full_path)) {
            return Storage::url($this->full_path);
        }

        if ($this->path && Storage::disk('public')->exists($this->path)) {
            return Storage::url($this->path);
        }

        return asset('images/no-image.png');
    }

    public function getFullUrlAttribute(): string
    {
        if ($this->full_path && Storage::disk('public')->exists($this->full_path)) {
            return Storage::url($this->full_path);
        }

        if ($this->path && Storage::disk('public')->exists($this->path)) {
            return Storage::url($this->path);
        }

        return $this->thumb_url;
    }

    public function getMediumUrlAttribute(): string
    {
        if ($this->medium_path && Storage::disk('public')->exists($this->medium_path)) {
            return Storage::url($this->medium_path);
        }

        if ($this->full_path && Storage::disk('public')->exists($this->full_path)) {
            return Storage::url($this->full_path);
        }

        if ($this->path && Storage::disk('public')->exists($this->path)) {
            return Storage::url($this->path);
        }

        return $this->thumb_url;
    }

    public function getImageUrlsAttribute(): array
    {
        return [
            'thumb' => $this->thumb_url,
            'medium' => $this->medium_url,
            'full' => $this->full_url,
        ];
    }

    /**
     * Scope a query to only include primary images.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope a query to order images by order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('created_at');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($image) {
            Storage::disk('public')->delete($image->thumb_path);
            Storage::disk('public')->delete($image->medium_path);
            Storage::disk('public')->delete($image->full_path);
            Storage::disk('public')->delete($image->path);
        });
    }
}
