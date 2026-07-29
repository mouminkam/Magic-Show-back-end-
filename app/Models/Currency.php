<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Currency extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'symbol_position',
        'decimal_places',
        'decimal_separator',
        'thousands_separator',
        'exchange_rate',
        'is_base',
        'is_active',
        'is_default',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'decimal_places' => 'integer',
        'exchange_rate' => 'decimal:6',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Symbol position constants.
     */
    const SYMBOL_POSITION_BEFORE = 'before';
    const SYMBOL_POSITION_AFTER = 'after';

    /**
     * Available symbol positions with display names.
     */
    public const SYMBOL_POSITIONS = [
        self::SYMBOL_POSITION_BEFORE => 'Before Amount',
        self::SYMBOL_POSITION_AFTER => 'After Amount',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when currencies are updated
        static::saved(function ($currency) {
            Cache::forget('currencies');
            Cache::forget('active_currencies');
            Cache::forget('default_currency');
            Cache::forget('base_currency');
        });

        static::deleted(function ($currency) {
            Cache::forget('currencies');
            Cache::forget('active_currencies');
            Cache::forget('default_currency');
            Cache::forget('base_currency');
        });
    }

    /**
     * Scope a query to only include active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include base currency.
     */
    public function scopeBase($query)
    {
        return $query->where('is_base', true);
    }

    /**
     * Scope a query to only include default currency.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the symbol position display name.
     */
    public function getSymbolPositionDisplayNameAttribute(): string
    {
        return self::SYMBOL_POSITIONS[$this->symbol_position] ?? 'Unknown';
    }

    /**
     * Get the formatted exchange rate.
     */
    public function getFormattedExchangeRateAttribute(): string
    {
        return number_format($this->exchange_rate, 6);
    }

    /**
     * Format an amount with this currency.
     */
    public function formatAmount(float $amount): string
    {
        $formattedAmount = number_format(
            $amount,
            $this->decimal_places,
            $this->decimal_separator,
            $this->thousands_separator
        );

        if ($this->symbol_position === self::SYMBOL_POSITION_BEFORE) {
            return $this->symbol . $formattedAmount;
        } else {
            return $formattedAmount . ' ' . $this->symbol;
        }
    }

    /**
     * Convert an amount from this currency to another currency.
     */
    public function convertTo(Currency $targetCurrency, float $amount): float
    {
        if ($this->id === $targetCurrency->id) {
            return $amount;
        }

        // Convert to base currency first, then to target currency
        $baseAmount = $amount / $this->exchange_rate;
        return $baseAmount * $targetCurrency->exchange_rate;
    }

    /**
     * Convert an amount from another currency to this currency.
     */
    public function convertFrom(Currency $sourceCurrency, float $amount): float
    {
        if ($this->id === $sourceCurrency->id) {
            return $amount;
        }

        // Convert to base currency first, then to this currency
        $baseAmount = $amount / $sourceCurrency->exchange_rate;
        return $baseAmount * $this->exchange_rate;
    }

    /**
     * Check if this currency is the base currency.
     */
    public function isBase(): bool
    {
        return $this->is_base;
    }

    /**
     * Check if this currency is the default currency.
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Check if this currency is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the currency display name with code.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }

    /**
     * Get the currency symbol with position.
     */
    public function getSymbolWithPositionAttribute(): string
    {
        return $this->symbol_position === self::SYMBOL_POSITION_BEFORE 
            ? $this->symbol . '1.00' 
            : '1.00' . $this->symbol;
    }

    /**
     * Get all active currencies.
     */
    public static function getActiveCurrencies()
    {
        return Cache::remember('active_currencies', 3600, function () {
            return static::active()->ordered()->get();
        });
    }

    /**
     * Get the default currency.
     */
    public static function getDefaultCurrency()
    {
        return Cache::remember('default_currency', 3600, function () {
            return static::default()->first();
        });
    }

    /**
     * Get the base currency.
     */
    public static function getBaseCurrency()
    {
        return Cache::remember('base_currency', 3600, function () {
            return static::base()->first();
        });
    }

    /**
     * Get currency by code.
     */
    public static function getByCode(string $code)
    {
        return static::where('code', strtoupper($code))->first();
    }

    /**
     * Set as base currency (only one can be base).
     */
    public function setAsBase(): bool
    {
        // Remove base flag from all other currencies
        static::where('id', '!=', $this->id)->update(['is_base' => false]);
        
        // Set this currency as base
        $this->is_base = true;
        $this->exchange_rate = 1.000000;
        
        return $this->save();
    }

    /**
     * Set as default currency (only one can be default).
     */
    public function setAsDefault(): bool
    {
        // Remove default flag from all other currencies
        static::where('id', '!=', $this->id)->update(['is_default' => false]);
        
        // Set this currency as default
        $this->is_default = true;
        
        return $this->save();
    }

    /**
     * Update exchange rate.
     */
    public function updateExchangeRate(float $rate): bool
    {
        $this->exchange_rate = $rate;
        return $this->save();
    }

    /**
     * Clear all currency cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('currencies');
        Cache::forget('active_currencies');
        Cache::forget('default_currency');
        Cache::forget('base_currency');
    }
}
