<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'options',
        'is_public',
        'is_required',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'options' => 'array',
        'is_public' => 'boolean',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Setting type constants.
     */
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';
    const TYPE_TEXT = 'text';
    const TYPE_EMAIL = 'email';
    const TYPE_URL = 'url';
    const TYPE_SELECT = 'select';
    const TYPE_RADIO = 'radio';
    const TYPE_CHECKBOX = 'checkbox';

    /**
     * Available setting types with display names.
     */
    public const TYPES = [
        self::TYPE_STRING => 'String',
        self::TYPE_INTEGER => 'Integer',
        self::TYPE_BOOLEAN => 'Boolean',
        self::TYPE_JSON => 'JSON',
        self::TYPE_TEXT => 'Text',
        self::TYPE_EMAIL => 'Email',
        self::TYPE_URL => 'URL',
        self::TYPE_SELECT => 'Select',
        self::TYPE_RADIO => 'Radio',
        self::TYPE_CHECKBOX => 'Checkbox',
    ];

    /**
     * Setting group constants.
     */
    const GROUP_GENERAL = 'general';
    const GROUP_EMAIL = 'email';
    const GROUP_PAYMENT = 'payment';
    const GROUP_SHIPPING = 'shipping';
    const GROUP_TAX = 'tax';
    const GROUP_CURRENCY = 'currency';
    const GROUP_SOCIAL = 'social';
    const GROUP_SEO = 'seo';
    const GROUP_SECURITY = 'security';
    const GROUP_MAINTENANCE = 'maintenance';

    /**
     * Available setting groups with display names.
     */
    public const GROUPS = [
        self::GROUP_GENERAL => 'General',
        self::GROUP_EMAIL => 'Email',
        self::GROUP_PAYMENT => 'Payment',
        self::GROUP_SHIPPING => 'Shipping',
        self::GROUP_TAX => 'Tax',
        self::GROUP_CURRENCY => 'Currency',
        self::GROUP_SOCIAL => 'Social Media',
        self::GROUP_SEO => 'SEO',
        self::GROUP_SECURITY => 'Security',
        self::GROUP_MAINTENANCE => 'Maintenance',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when settings are updated
        static::saved(function ($setting) {
            Cache::forget('settings');
            Cache::forget("setting.{$setting->key}");
        });

        static::deleted(function ($setting) {
            Cache::forget('settings');
            Cache::forget("setting.{$setting->key}");
        });
    }

    /**
     * Scope a query to only include public settings.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include required settings.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope a query to filter by group.
     */
    public function scopeInGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    /**
     * Get the setting type display name.
     */
    public function getTypeDisplayNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? 'Unknown';
    }

    /**
     * Get the setting group display name.
     */
    public function getGroupDisplayNameAttribute(): string
    {
        return self::GROUPS[$this->group] ?? 'Unknown';
    }

    /**
     * Get the typed value based on the setting type.
     */
    public function getTypedValueAttribute()
    {
        switch ($this->type) {
            case self::TYPE_INTEGER:
                return (int) $this->value;
            case self::TYPE_BOOLEAN:
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
            case self::TYPE_JSON:
                return json_decode($this->value, true);
            default:
                return $this->value;
        }
    }

    /**
     * Set the value with proper type casting.
     */
    public function setValueAttribute($value)
    {
        switch ($this->type) {
            case self::TYPE_JSON:
                $this->attributes['value'] = is_array($value) ? json_encode($value) : $value;
                break;
            case self::TYPE_BOOLEAN:
                $this->attributes['value'] = $value ? '1' : '0';
                break;
            default:
                $this->attributes['value'] = $value;
        }
    }

    /**
     * Check if this setting has options.
     */
    public function hasOptions(): bool
    {
        return in_array($this->type, [self::TYPE_SELECT, self::TYPE_RADIO, self::TYPE_CHECKBOX]) && !empty($this->options);
    }

    /**
     * Get the options for select/radio/checkbox settings.
     */
    public function getOptionsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set the options for select/radio/checkbox settings.
     */
    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Check if this setting is a select type.
     */
    public function isSelectType(): bool
    {
        return in_array($this->type, [self::TYPE_SELECT, self::TYPE_RADIO, self::TYPE_CHECKBOX]);
    }

    /**
     * Check if this setting is a text type.
     */
    public function isTextType(): bool
    {
        return in_array($this->type, [self::TYPE_STRING, self::TYPE_TEXT, self::TYPE_EMAIL, self::TYPE_URL]);
    }

    /**
     * Check if this setting is a numeric type.
     */
    public function isNumericType(): bool
    {
        return $this->type === self::TYPE_INTEGER;
    }

    /**
     * Check if this setting is a boolean type.
     */
    public function isBooleanType(): bool
    {
        return $this->type === self::TYPE_BOOLEAN;
    }

    /**
     * Check if this setting is a JSON type.
     */
    public function isJsonType(): bool
    {
        return $this->type === self::TYPE_JSON;
    }

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->typed_value : $default;
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, $value, string $type = self::TYPE_STRING, string $group = self::GROUP_GENERAL): bool
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
            ]
        );

        return $setting->exists;
    }

    /**
     * Get all settings as a key-value array.
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('settings', 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get settings by group.
     */
    public static function getSettingsByGroup(string $group): array
    {
        return static::where('group', $group)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Get public settings.
     */
    public static function getPublicSettings(): array
    {
        return static::public()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Bulk update settings.
     */
    public static function bulkUpdate(array $settings): bool
    {
        try {
            foreach ($settings as $key => $value) {
                static::setValue($key, $value);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clear all settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('settings');
        Cache::flush();
    }
}
