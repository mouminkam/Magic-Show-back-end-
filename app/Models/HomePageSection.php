<?php

namespace App\Models;

use App\Helpers\LocaleHelper;
use Illuminate\Database\Eloquent\Model;

class HomePageSection extends Model
{
    protected $fillable = [
        'section_key',
        'title_ar',
        'title_en',
        'subtitle_ar',
        'subtitle_en',
        'description_ar',
        'description_en',
        'button_text_ar',
        'button_text_en',
        'button_link',
        'limit',
        'settings',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public static function getByKey(string $key): ?self
    {
        return static::where('section_key', $key)->first();
    }

    public static function getAllForApi(): \Illuminate\Support\Collection
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('section_key');
    }

    public function getTranslatedTitleAttribute(): ?string
    {
        return LocaleHelper::transAttr($this, 'title');
    }

    public function getTranslatedSubtitleAttribute(): ?string
    {
        return LocaleHelper::transAttr($this, 'subtitle');
    }

    public function getTranslatedDescriptionAttribute(): ?string
    {
        return LocaleHelper::transAttr($this, 'description');
    }

    public function getTranslatedButtonTextAttribute(): ?string
    {
        return LocaleHelper::transAttr($this, 'button_text');
    }

    public function getSetting(string $key, $default = null)
    {
        $settings = $this->settings ?? [];
        return $settings[$key] ?? $default;
    }
}
