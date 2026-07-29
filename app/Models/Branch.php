<?php

namespace App\Models;

use App\Models\Concerns\SyncsLegacyLocaleBaseFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Branch extends Model
{
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
        'code',
        'description',
        'description_ar',
        'description_en',
        'address',
        'address_ar',
        'address_en',
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
        'map_url',
        'is_active',
        'opening_time',
        'closing_time',
        'working_days',
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
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
        'working_days' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate code from name if not provided
        static::creating(function ($branch) {
            if (empty($branch->code)) {
                $branch->code = strtoupper(Str::slug($branch->name, ''));
            }
        });

        // Update code when name changes
        static::updating(function ($branch) {
            if ($branch->isDirty('name') && empty($branch->code)) {
                $branch->code = strtoupper(Str::slug($branch->name, ''));
            }
        });
    }

    /**
     * Get the inventory records for this branch.
     */
    public function inventory()
    {
        return $this->morphMany(Inventory::class, 'location');
    }

    /**
     * Scope a query to only include active branches.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
     * Check if the branch is currently open.
     */
    public function isOpen(): bool
    {
        if (!$this->opening_time || !$this->closing_time) {
            return true; // Assume always open if times not set
        }

        $now = now();
        $currentDay = strtolower($now->format('l')); // Monday, Tuesday, etc.
        
        // Check if today is a working day
        if ($this->working_days && !in_array($currentDay, $this->working_days)) {
            return false;
        }

        $openingTime = $this->opening_time->format('H:i');
        $closingTime = $this->closing_time->format('H:i');
        $currentTime = $now->format('H:i');

        return $currentTime >= $openingTime && $currentTime <= $closingTime;
    }

    /**
     * Get the working hours as a formatted string.
     */
    public function getWorkingHoursAttribute(): string
    {
        if (!$this->opening_time || !$this->closing_time) {
            return '24/7';
        }

        return $this->opening_time->format('H:i') . ' - ' . $this->closing_time->format('H:i');
    }

    /**
     * Get the working days as a formatted string.
     */
    public function getWorkingDaysStringAttribute(): string
    {
        if (!$this->working_days) {
            return 'Every day';
        }

        $dayNames = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday'
        ];

        $formattedDays = array_map(function($day) use ($dayNames) {
            return $dayNames[$day] ?? ucfirst($day);
        }, $this->working_days);

        return implode(', ', $formattedDays);
    }

    /**
     * Check if the branch has GPS coordinates.
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Get the distance to another branch (in kilometers).
     */
    public function distanceTo(Branch $branch): ?float
    {
        if (!$this->hasCoordinates() || !$branch->hasCoordinates()) {
            return null;
        }

        $lat1 = (float) $this->latitude;
        $lon1 = (float) $this->longitude;
        $lat2 = (float) $branch->latitude;
        $lon2 = (float) $branch->longitude;

        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    protected function getLocaleBaseFieldMap(): array
    {
        return [
            'name' => ['name_en', 'name_ar'],
            'description' => ['description_en', 'description_ar'],
            'address' => ['address_en', 'address_ar'],
        ];
    }
}
