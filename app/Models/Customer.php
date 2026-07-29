<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\CustomerResetPasswordNotification;
use Carbon\Carbon;

class Customer extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use AuthenticatableTrait, HasApiTokens, HasFactory, Notifiable, CanResetPassword;

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomerResetPasswordNotification($token));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    /**
     * The attributes hidden from array / JSON serialization.
     *
     * CustomerResource is the intended public representation, but this is a
     * defence-in-depth guard so a raw Customer model can never leak its
     * password hash or remember token through any future code path.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'national_id',
        'occupation',
        'latitude',
        'longitude',
        'is_active',
        'email_verified',
        'phone_verified',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'preferred_language',
        'preferred_currency',
        'preferences',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'email_verified' => 'boolean',
        'phone_verified' => 'boolean',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'preferences' => 'array',
        'password' => 'hashed',
    ];

    /**
     * Gender constants.
     */
    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';
    const GENDER_OTHER = 'other';

    /**
     * Available genders with display names.
     */
    public const GENDERS = [
        self::GENDER_MALE => 'Male',
        self::GENDER_FEMALE => 'Female',
        self::GENDER_OTHER => 'Other',
    ];

    /**
     * Get the orders for this customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the wishlist items for this customer.
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Scope a query to only include active customers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include verified customers.
     */
    public function scopeVerified($query)
    {
        return $query->where('email_verified', true);
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
     * Scope a query to filter by gender.
     */
    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope a query to only include customers who logged in recently.
     */
    public function scopeRecentlyActive($query, $days = 30)
    {
        return $query->where('last_login_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope a query to only include customers who haven't logged in recently.
     */
    public function scopeInactive($query, $days = 90)
    {
        return $query->where(function ($q) use ($days) {
            $q->whereNull('last_login_at')
              ->orWhere('last_login_at', '<', Carbon::now()->subDays($days));
        });
    }

    /**
     * Get the full name attribute.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get the full address attribute.
     */
    public function getFullAddressAttribute(): string
    {
        $address = [];
        
        if ($this->address) {
            $address[] = $this->address;
        }
        
        if ($this->city) {
            $address[] = $this->city;
        }
        
        if ($this->state) {
            $address[] = $this->state;
        }
        
        if ($this->country) {
            $address[] = $this->country;
        }
        
        if ($this->postal_code) {
            $address[] = $this->postal_code;
        }
        
        return implode(', ', $address) ?: 'Not specified';
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
     * Get the gender display name.
     */
    public function getGenderDisplayNameAttribute(): ?string
    {
        return $this->gender ? self::GENDERS[$this->gender] : null;
    }

    /**
     * Get the age attribute.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Get the initials attribute.
     */
    public function getInitialsAttribute(): string
    {
        $firstInitial = $this->first_name ? strtoupper(substr($this->first_name, 0, 1)) : '';
        $lastInitial = $this->last_name ? strtoupper(substr($this->last_name, 0, 1)) : '';
        
        return $firstInitial . $lastInitial;
    }

    /**
     * Check if the customer has GPS coordinates.
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Check if the customer is verified.
     */
    public function isVerified(): bool
    {
        return $this->email_verified && $this->phone_verified;
    }

    /**
     * Check if the customer is recently active.
     */
    public function isRecentlyActive(int $days = 30): bool
    {
        return $this->last_login_at && $this->last_login_at->isAfter(Carbon::now()->subDays($days));
    }

    /**
     * Check if the customer is inactive.
     */
    public function isInactive(int $days = 90): bool
    {
        return !$this->last_login_at || $this->last_login_at->isBefore(Carbon::now()->subDays($days));
    }

    /**
     * Mark email as verified.
     */
    public function markEmailAsVerified(): bool
    {
        return $this->update([
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Mark phone as verified.
     */
    public function markPhoneAsVerified(): bool
    {
        return $this->update([
            'phone_verified' => true,
            'phone_verified_at' => now(),
        ]);
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): bool
    {
        return $this->update(['last_login_at' => now()]);
    }

    /**
     * Get the customer's total orders count.
     */
    public function getTotalOrdersAttribute(): int
    {
        return $this->orders()->count();
    }

    /**
     * Get the customer's total spent amount.
     */
    public function getTotalSpentAttribute(): float
    {
        return $this->orders()->sum('total_amount') ?? 0;
    }

    /**
     * Get the formatted total spent amount.
     */
    public function getFormattedTotalSpentAttribute(): string
    {
        return number_format($this->total_spent, 2) . ' ' . $this->preferred_currency;
    }

    /**
     * Get the customer's average order value.
     */
    public function getAverageOrderValueAttribute(): ?float
    {
        $totalOrders = $this->total_orders;
        return $totalOrders > 0 ? $this->total_spent / $totalOrders : null;
    }

    /**
     * Get the formatted average order value.
     */
    public function getFormattedAverageOrderValueAttribute(): ?string
    {
        return $this->average_order_value ? number_format($this->average_order_value, 2) . ' ' . $this->preferred_currency : null;
    }

    /**
     * Get the customer's last order.
     */
    public function getLastOrderAttribute()
    {
        return $this->orders()->latest()->first();
    }

    /**
     * Get the customer's last order date.
     */
    public function getLastOrderDateAttribute(): ?Carbon
    {
        return $this->last_order?->created_at;
    }

    /**
     * Get the customer's preferred language display name.
     */
    public function getPreferredLanguageDisplayNameAttribute(): string
    {
        $languages = [
            'ar' => 'Arabic',
            'en' => 'English',
            'fr' => 'French',
            'es' => 'Spanish',
        ];

        return $languages[$this->preferred_language] ?? ucfirst($this->preferred_language);
    }

    /**
     * Get the customer's preferred currency display name.
     */
    public function getPreferredCurrencyDisplayNameAttribute(): string
    {
        $currencies = [
            'SAR' => 'Saudi Riyal',
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
        ];

        return $currencies[$this->preferred_currency] ?? $this->preferred_currency;
    }
}
