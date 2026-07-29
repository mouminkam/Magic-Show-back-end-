<?php

namespace App\Models;

use App\Models\Concerns\SyncsLegacyLocaleBaseFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Size;
use App\Models\Season;

class Product extends Model
{
    use HasFactory;
    use SyncsLegacyLocaleBaseFields;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'sku',
        'brand_id',
        'material_id',
        'description',
        'description_ar',
        'description_en',
        'short_description',
        'short_description_ar',
        'short_description_en',
        'price',
        'sale_price',
        'compare_price',
        'cost_price',
        'quantity',
        'min_quantity',
        'weight',
        'dimensions',
        'barcode',
        'model',
        'images',
        'featured_image',
        'featured_image_thumb',
        'featured_image_full',
        'featured_image_medium',
        'is_active',
        'is_featured',
        'is_digital',
        'requires_shipping',
        'track_quantity',
        'allow_backorder',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'slug',
        'sort_order',
        'published_at',
    ];

    /**
     * The relationships that should always be loaded.
     * Removed automatic eager loading to prevent array/collection conflicts
     */
    // protected $with = ['productImages']; // Removed to prevent conflicts

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'quantity' => 'integer',
        'min_quantity' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_digital' => 'boolean',
        'requires_shipping' => 'boolean',
        'track_quantity' => 'boolean',
        'allow_backorder' => 'boolean',
        'images' => 'array',
        'meta_keywords' => 'array',
        'sort_order' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name if not provided
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            
            // Set published_at if not set and product is active
            if (empty($product->published_at) && $product->is_active) {
                $product->published_at = now();
            }
        });

        // Update slug when name changes
        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Get the categories for this product.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Get the attribute values for this product.
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttributeValue::class, 'product_product_attribute_values');
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include published products.
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include products in stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope a query to only include products that are low in stock.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'min_quantity');
    }

    /**
     * Scope a query to only include products that require shipping.
     */
    public function scopeRequiresShipping($query)
    {
        return $query->where('requires_shipping', true);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope a query to search products by name, SKU, or description.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('brand', 'like', "%{$term}%");
        });
    }

    /**
     * Scope a query to filter products by price range.
     */
    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * Get the formatted price attribute.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get the formatted compare price attribute.
     */
    public function getFormattedComparePriceAttribute(): ?string
    {
        return $this->compare_price ? '$' . number_format($this->compare_price, 2) : null;
    }

    /**
     * Get the discount percentage if compare price exists.
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if ($this->sale_price && $this->sale_price < $this->price) {
            return round((($this->price - $this->sale_price) / $this->price) * 100, 2);
        }
        
        return null;
    }

    public function getFeaturedImageMediumUrlAttribute(): ?string
    {
        if ($this->featured_image_medium && Storage::disk('public')->exists($this->featured_image_medium)) {
            return Storage::url($this->featured_image_medium);
        }

        if ($this->featured_image_full && Storage::disk('public')->exists($this->featured_image_full)) {
            return Storage::url($this->featured_image_full);
        }

        if ($this->featured_image && Storage::disk('public')->exists($this->featured_image)) {
            return Storage::url($this->featured_image);
        }

        return $this->featured_image_thumb_url;
    }

    public function getPrimaryImageThumbUrlAttribute(): ?string
    {
        $featuredThumb = $this->featured_image_thumb_url;
        if ($featuredThumb) {
            return $featuredThumb;
        }

        if ($this->relationLoaded('productImages') && $this->productImages->count() > 0) {
            $firstImage = $this->productImages->first();
            if ($firstImage) {
                return $firstImage->thumb_url;
            }
        }

        if ($this->images && is_array($this->images) && count($this->images) > 0) {
            $path = $this->images[0];
            if ($path && Storage::disk('public')->exists($path)) {
                return Storage::url($path);
            }
        }

        return null;
    }

    /**
     * Get the profit margin percentage.
     */
    public function getProfitMarginAttribute(): ?float
    {
        if ($this->cost_price && $this->cost_price > 0) {
            return round((($this->price - $this->cost_price) / $this->cost_price) * 100, 2);
        }
        
        return null;
    }

    /**
     * Check if the product is in stock.
     */
    public function isInStock(): bool
    {
        return $this->quantity > 0 || $this->allow_backorder;
    }

    /**
     * Check if the product is low in stock.
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_quantity;
    }

    /**
     * Check if the product is on sale.
     */
    public function isOnSale(): bool
    {
        return $this->sale_price && $this->sale_price < $this->price;
    }

    /**
     * Check if the product is published.
     */
    public function isPublished(): bool
    {
        return $this->published_at && $this->published_at <= now();
    }

    /**
     * Get the primary image URL.
     */
    public function getPrimaryImageAttribute(): ?string
    {
        return $this->featured_image ?: ($this->images ? $this->images[0] : null);
    }

    /**
     * Get all images as an array.
     */
    public function getAllImagesAttribute(): array
    {
        $images = $this->images ?: [];
        
        if ($this->featured_image && !in_array($this->featured_image, $images)) {
            array_unshift($images, $this->featured_image);
        }
        
        return $images;
    }

    /**
     * Get the stock status.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->quantity > $this->min_quantity) {
            return 'in_stock';
        } elseif ($this->quantity > 0) {
            return 'low_stock';
        } elseif ($this->allow_backorder) {
            return 'backorder';
        } else {
            return 'out_of_stock';
        }
    }

    /**
     * Get the stock status label.
     */
    public function getStockStatusLabelAttribute(): string
    {
        return match($this->stock_status) {
            'in_stock' => 'In Stock',
            'low_stock' => 'Low Stock',
            'backorder' => 'Backorder',
            'out_of_stock' => 'Out of Stock',
            default => 'Unknown'
        };
    }

    /**
     * Get the brand that owns the product.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the material that owns the product.
     */
    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Get the images for the product.
     * Ordered: is_primary first, then by order, then created_at.
     */
    public function productImages()
    {
        return $this->hasMany(ProductImage::class)
            ->orderBy('is_primary', 'desc')
            ->orderBy('order')
            ->orderBy('created_at');
    }

    /**
     * Get the primary image for the product.
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * العلاقة مع الألوان
     */
    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class, 'product_colors')
                    ->withPivot(['is_primary', 'sort_order'])
                    ->withTimestamps()
                    ->orderBy('product_colors.sort_order');
    }

    /**
     * العلاقة مع المقاسات.
     */
    public function sizes(): BelongsToMany
    {
        return $this->belongsToMany(Size::class, 'product_sizes')
            ->withTimestamps()
            ->orderBy('sizes.sort_order')
            ->orderBy('sizes.name');
    }

    /**
     * العلاقة مع المواسم.
     */
    public function seasons(): BelongsToMany
    {
        return $this->belongsToMany(Season::class, 'product_seasons')
            ->withTimestamps()
            ->orderBy('seasons.sort_order');
    }

    /**
     * العلاقة مع اللون الأساسي
     */
    public function primaryColor(): BelongsToMany
    {
        return $this->colors()->wherePivot('is_primary', true);
    }

    /**
     * Get the featured image URL safely.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        $full = $this->featured_image_full_url;
        if ($full) {
            return $full;
        }

        if (!$this->featured_image) {
            return null;
        }

        if (is_array($this->featured_image)) {
            return isset($this->featured_image[0]) ? Storage::url($this->featured_image[0]) : null;
        }

        return Storage::url($this->featured_image);
    }

    public function getFeaturedImageThumbUrlAttribute(): ?string
    {
        if ($this->featured_image_thumb && Storage::disk('public')->exists($this->featured_image_thumb)) {
            return Storage::url($this->featured_image_thumb);
        }

        if ($this->featured_image_full && Storage::disk('public')->exists($this->featured_image_full)) {
            return Storage::url($this->featured_image_full);
        }

        if ($this->featured_image && Storage::disk('public')->exists($this->featured_image)) {
            return Storage::url($this->featured_image);
        }

        return null;
    }

    public function getFeaturedImageFullUrlAttribute(): ?string
    {
        if ($this->featured_image_full && Storage::disk('public')->exists($this->featured_image_full)) {
            return Storage::url($this->featured_image_full);
        }

        if ($this->featured_image && Storage::disk('public')->exists($this->featured_image)) {
            return Storage::url($this->featured_image);
        }

        return $this->featured_image_thumb_url;
    }

    public function getFeaturedImageUrlsAttribute(): array
    {
        return [
            'thumb' => $this->featured_image_thumb_url ?? asset('images/no-image.png'),
            'medium' => $this->featured_image_medium_url ?? asset('images/no-image.png'),
            'full' => $this->featured_image_full_url ?? asset('images/no-image.png'),
        ];
    }

    /**
     * Get the primary image URL safely.
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $featuredFull = $this->featured_image_full_url;
        if ($featuredFull) {
            return $featuredFull;
        }
        
        // Try first product image from relationship
        if ($this->relationLoaded('productImages') && $this->productImages->count() > 0) {
            $firstImage = $this->productImages->first();
            if ($firstImage) {
                return $firstImage->full_url;
            }
        }
        
        // Fallback to old images field
        if ($this->images && is_array($this->images) && count($this->images) > 0) {
            $path = $this->images[0];
            if ($path && Storage::disk('public')->exists($path)) {
                return Storage::url($path);
            }
        }
        
        return null;
    }

    /**
     * Get safe product images count.
     */
    public function getSafeProductImagesCountAttribute(): int
    {
        $images = $this->productImages;
        if (is_array($images)) {
            return count($images);
        }
        return $images ? $images->count() : 0;
    }

    /**
     * Get safe categories count.
     */
    public function getSafeCategoriesCountAttribute(): int
    {
        $categories = $this->categories;
        if (is_array($categories)) {
            return count($categories);
        }
        return $categories ? $categories->count() : 0;
    }

    /**
     * Get the primary color for the product.
     */
    public function getPrimaryColorAttribute(): ?Color
    {
        return $this->colors()->wherePivot('is_primary', true)->first();
    }

    /**
     * Get the inventory records for this product.
     */
    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the total quantity across all inventory locations.
     */
    public function getTotalInventoryQuantityAttribute(): int
    {
        return $this->inventory()->sum('available_quantity');
    }

    /**
     * Get the total quantity including reserved items.
     */
    public function getTotalInventoryWithReservedAttribute(): int
    {
        return $this->inventory()->sum('quantity');
    }

    /**
     * Check if product has inventory records.
     */
    public function hasInventory(): bool
    {
        return $this->inventory()->exists();
    }

    /**
     * Get inventory status based on inventory table or fallback to product quantity.
     */
    public function getInventoryStatusAttribute(): string
    {
        if ($this->hasInventory()) {
            $totalAvailable = $this->total_inventory_quantity;
            $totalMinQuantity = $this->inventory()->sum('min_quantity');
            
            if ($totalAvailable > $totalMinQuantity) {
                return 'in_stock';
            } elseif ($totalAvailable > 0) {
                return 'low_stock';
            } else {
                return 'out_of_stock';
            }
        }
        
        // Fallback to product quantity
        return $this->stock_status;
    }

    /**
     * Get primary image URL safely for inventory display
     */
    public function getPrimaryImageUrlForInventoryAttribute(): ?string
    {
        try {
            // Try different image sources
            if ($this->featured_image && Storage::disk('public')->exists($this->featured_image)) {
                return Storage::url($this->featured_image);
            }
            
            if ($this->images && is_array($this->images) && count($this->images) > 0) {
                $firstImage = $this->images[0];
                if (Storage::disk('public')->exists($firstImage)) {
                    return Storage::url($firstImage);
                }
            }
            
            // Try productImages relationship
            if ($this->relationLoaded('productImages') && $this->productImages->count() > 0) {
                $primaryImage = $this->productImages->where('is_primary', true)->first() 
                              ?? $this->productImages->first();
                
                if ($primaryImage && Storage::disk('public')->exists($primaryImage->path)) {
                    return Storage::url($primaryImage->path);
                }
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get formatted cost price for inventory display
     */
    public function getFormattedCostPriceForInventoryAttribute(): ?string
    {
        return $this->cost_price ? number_format($this->cost_price, 2) . ' SAR' : null;
    }

    /**
     * Get inventory location info for display
     */
    public function getInventoryLocationInfoAttribute(): array
    {
        return [
            'type' => 'App\Models\Product',
            'id' => $this->id,
            'name' => 'المخزون الأساسي',
            'icon' => 'fas fa-box',
            'color' => 'text-purple-500',
        ];
    }

    /**
     * Check if product can be managed as inventory
     */
    public function canBeInventoryManaged(): bool
    {
        return $this->track_quantity && !$this->is_digital;
    }

    /**
     * Get all colors for the product.
     */
    public function getAllColorsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->colors;
    }

    /**
     * Get colors count for the product.
     */
    public function getColorsCountAttribute(): int
    {
        return $this->colors()->count();
    }

    protected function getLocaleBaseFieldMap(): array
    {
        return [
            'name' => ['name_en', 'name_ar'],
            'description' => ['description_en', 'description_ar'],
            'short_description' => ['short_description_en', 'short_description_ar'],
        ];
    }
}
