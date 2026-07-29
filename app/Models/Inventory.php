<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class Inventory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'location_type',
        'location_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'min_quantity',
        'max_quantity',
        'cost_price',
        'batch_number',
        'expiry_date',
        'rack_location',
        'shelf_location',
        'notes',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'available_quantity' => 'integer',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'cost_price' => 'decimal:2',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate available_quantity when quantity or reserved_quantity changes
        static::saving(function ($inventory) {
            $inventory->available_quantity = $inventory->quantity - $inventory->reserved_quantity;
        });
    }

    /**
     * Get the product that owns this inventory.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the location (Branch or Warehouse) that owns this inventory.
     */
    public function location(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include active inventory.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inventory with stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('available_quantity', '>', 0);
    }

    /**
     * Scope a query to only include low stock inventory.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('available_quantity', '<=', 'min_quantity');
    }

    /**
     * Scope a query to only include out of stock inventory.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('available_quantity', '<=', 0);
    }

    /**
     * Scope a query to filter by location type.
     */
    public function scopeAtLocation($query, $locationType, $locationId)
    {
        return $query->where('location_type', $locationType)
                    ->where('location_id', $locationId);
    }

    /**
     * Scope a query to filter by product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope a query to only include inventory in branches.
     */
    public function scopeInBranches($query)
    {
        return $query->where('location_type', Branch::class);
    }

    /**
     * Scope a query to only include inventory in warehouses.
     */
    public function scopeInWarehouses($query)
    {
        return $query->where('location_type', Warehouse::class);
    }

    /**
     * Scope a query to only include inventory that will expire soon.
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', Carbon::now()->addDays($days));
    }

    /**
     * Scope a query to only include expired inventory.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', Carbon::now());
    }

    /**
     * Check if the inventory is in stock.
     */
    public function isInStock(): bool
    {
        return $this->available_quantity > 0;
    }

    /**
     * Check if the inventory is low in stock.
     */
    public function isLowStock(): bool
    {
        return $this->available_quantity <= $this->min_quantity;
    }

    /**
     * Check if the inventory is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->available_quantity <= 0;
    }

    /**
     * Check if the inventory is overstocked.
     */
    public function isOverstocked(): bool
    {
        return $this->max_quantity && $this->quantity > $this->max_quantity;
    }

    /**
     * Check if the inventory item is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if the inventory item will expire soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date->isFuture() && $this->expiry_date->diffInDays(Carbon::now()) <= $days;
    }

    /**
     * Get the stock status.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        } elseif ($this->isLowStock()) {
            return 'low_stock';
        } elseif ($this->isOverstocked()) {
            return 'overstocked';
        } else {
            return 'in_stock';
        }
    }

    /**
     * Get the stock status label.
     */
    public function getStockStatusLabelAttribute(): string
    {
        return match($this->stock_status) {
            'out_of_stock' => 'Out of Stock',
            'low_stock' => 'Low Stock',
            'overstocked' => 'Overstocked',
            'in_stock' => 'In Stock',
            default => 'Unknown'
        };
    }

    /**
     * Get the physical location string.
     */
    public function getPhysicalLocationAttribute(): string
    {
        $location = [];
        
        if ($this->rack_location) {
            $location[] = "Rack: {$this->rack_location}";
        }
        
        if ($this->shelf_location) {
            $location[] = "Shelf: {$this->shelf_location}";
        }
        
        return implode(', ', $location) ?: 'Not specified';
    }

    /**
     * Get the formatted cost price.
     */
    public function getFormattedCostPriceAttribute(): ?string
    {
        return $this->cost_price ? '$' . number_format($this->cost_price, 2) : null;
    }

    /**
     * Reserve quantity for an order.
     */
    public function reserveQuantity(int $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            return false; // Not enough available quantity
        }

        $this->reserved_quantity += $quantity;
        $this->available_quantity -= $quantity;
        return $this->save();
    }

    /**
     * Release reserved quantity.
     */
    public function releaseQuantity(int $quantity): bool
    {
        if ($this->reserved_quantity < $quantity) {
            return false; // Not enough reserved quantity
        }

        $this->reserved_quantity -= $quantity;
        $this->available_quantity += $quantity;
        return $this->save();
    }

    /**
     * Add quantity to inventory.
     */
    public function addQuantity(int $quantity): bool
    {
        $this->quantity += $quantity;
        $this->available_quantity += $quantity;
        return $this->save();
    }

    /**
     * Remove quantity from inventory.
     */
    public function removeQuantity(int $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            return false; // Not enough available quantity
        }

        $this->quantity -= $quantity;
        $this->available_quantity -= $quantity;
        return $this->save();
    }

    /**
     * Get the total value of this inventory item.
     */
    public function getTotalValueAttribute(): ?float
    {
        return $this->cost_price ? $this->quantity * $this->cost_price : null;
    }

    /**
     * Get the formatted total value.
     */
    public function getFormattedTotalValueAttribute(): ?string
    {
        return $this->total_value ? '$' . number_format($this->total_value, 2) : null;
    }
}
