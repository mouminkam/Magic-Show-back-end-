<?php

namespace App\Models;

use App\Models\Concerns\SyncsLegacyLocaleBaseFields;
use Illuminate\Database\Eloquent\Model;

class AboutSection extends Model
{
    use SyncsLegacyLocaleBaseFields;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'section_key',
        'title',
        'title_ar',
        'title_en',
        'subtitle',
        'subtitle_ar',
        'subtitle_en',
        'description',
        'description_ar',
        'description_en',
        'image',
        'background_image',
        'left_badge',
        'left_badge_ar',
        'left_badge_en',
        'right_badge',
        'right_badge_ar',
        'right_badge_en',
        'button_text',
        'button_text_ar',
        'button_text_en',
        'button_link',
        'features',
        'features_ar',
        'features_en',
        'extra_data',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'features' => 'array',
        'features_ar' => 'array',
        'features_en' => 'array',
        'extra_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active sections.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    /**
     * Get the background image URL.
     */
    public function getBackgroundImageUrlAttribute(): ?string
    {
        return $this->background_image ? asset('storage/' . $this->background_image) : null;
    }

    protected function getLocaleBaseFieldMap(): array
    {
        return [
            'title' => ['title_en', 'title_ar'],
            'subtitle' => ['subtitle_en', 'subtitle_ar'],
            'description' => ['description_en', 'description_ar'],
            'left_badge' => ['left_badge_en', 'left_badge_ar'],
            'right_badge' => ['right_badge_en', 'right_badge_ar'],
            'button_text' => ['button_text_en', 'button_text_ar'],
            'features' => ['features_en', 'features_ar'],
        ];
    }
}
