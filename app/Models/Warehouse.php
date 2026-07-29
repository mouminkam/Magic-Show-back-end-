<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Warehouse extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'manager_name',
        'manager_phone',
        'manager_email',
        'latitude',
        'longitude',
        'is_active',
        'type',
        'capacity',
        'capacity_unit',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'capacity' => 'decimal:2',
    ];

    /**
     * Warehouse types constants.
     */
    const TYPE_MAIN = 'main';
    const TYPE_DISTRIBUTION = 'distribution';
    const TYPE_RETAIL = 'retail';
    const TYPE_STORAGE = 'storage';

    /**
     * Available warehouse types with display names.
     */
    public const TYPES = [
        self::TYPE_MAIN => 'Main Warehouse',
        self::TYPE_DISTRIBUTION => 'Distribution Center',
        self::TYPE_RETAIL => 'Retail Store',
        self::TYPE_STORAGE => 'Storage Facility',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate code from name if not provided
        static::creating(function ($warehouse) {
            if (empty($warehouse->code)) {
                $warehouse->code = strtoupper(Str::slug($warehouse->name, ''));
            }
        });

        // Update code when name changes
        static::updating(function ($warehouse) {
            if ($warehouse->isDirty('name') && empty($warehouse->code)) {
                $warehouse->code = strtoupper(Str::slug($warehouse->name, ''));
            }
        });
    }

    /**
     * Get the inventory records for this warehouse.
     */
    public function inventory()
    {
        return $this->morphMany(Inventory::class, 'location');
    }

    /**
     * Scope a query to only include active warehouses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by city.
     */
    public function scopeInCity($query, $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Scope a query to filter by country.
     */
    public function scopeInCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope a query to only include main warehouses.
     */
    public function scopeMain($query)
    {
        return $query->where('type', self::TYPE_MAIN);
    }

    /**
     * Scope a query to only include distribution centers.
     */
    public function scopeDistribution($query)
    {
        return $query->where('type', self::TYPE_DISTRIBUTION);
    }

    /**
     * Scope a query to only include retail stores.
     */
    public function scopeRetail($query)
    {
        return $query->where('type', self::TYPE_RETAIL);
    }

    /**
     * Scope a query to only include storage facilities.
     */
    public function scopeStorage($query)
    {
        return $query->where('type', self::TYPE_STORAGE);
    }

    /**
     * Get the full address attribute.
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address;
        
        if ($this->city) {
            $address .= ', ' . $this->city;
        }
        
        if ($this->state) {
            $address .= ', ' . $this->state;
        }
        
        if ($this->country) {
            $address .= ', ' . $this->country;
        }
        
        if ($this->postal_code) {
            $address .= ' ' . $this->postal_code;
        }
        
        return $address;
    }

    /**
     * Get the GPS coordinates as an array.
     */
    public function getCoordinatesAttribute(): ?array
    {
        if ($this->latitude && $this->longitude) {
            return [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude
            ];
        }
        
        return null;
    }

    /**
     * Get the formatted capacity.
     */
    public function getFormattedCapacityAttribute(): ?string
    {
        if (!$this->capacity) {
            return null;
        }

        return number_format($this->capacity, 2) . ' ' . $this->capacity_unit;
    }

    /**
     * Get the warehouse type display name.
     */
    public function getTypeDisplayNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? 'Unknown Type';
    }

    /**
     * Check if the warehouse has GPS coordinates.
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Check if this is a main warehouse.
     */
    public function isMain(): bool
    {
        return $this->type === self::TYPE_MAIN;
    }

    /**
     * Check if this is a distribution center.
     */
    public function isDistribution(): bool
    {
        return $this->type === self::TYPE_DISTRIBUTION;
    }

    /**
     * Check if this is a retail store.
     */
    public function isRetail(): bool
    {
        return $this->type === self::TYPE_RETAIL;
    }

    /**
     * Check if this is a storage facility.
     */
    public function isStorage(): bool
    {
        return $this->type === self::TYPE_STORAGE;
    }

    /**
     * Get the distance to another warehouse (in kilometers).
     */
    public function distanceTo(Warehouse $warehouse): ?float
    {
        if (!$this->hasCoordinates() || !$warehouse->hasCoordinates()) {
            return null;
        }

        $lat1 = (float) $this->latitude;
        $lon1 = (float) $this->longitude;
        $lat2 = (float) $warehouse->latitude;
        $lon2 = (float) $warehouse->longitude;

        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    /**
     * Get the capacity utilization percentage.
     */
    public function getCapacityUtilizationAttribute(): ?float
    {
        if (!$this->capacity) {
            return null;
        }

        // This would need to be calculated based on actual inventory
        // For now, return null as we don't have inventory tracking yet
        return null;
    }
}
