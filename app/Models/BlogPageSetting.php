<?php

namespace App\Models;

use App\Models\Concerns\SyncsLegacyLocaleBaseFields;
use Illuminate\Database\Eloquent\Model;

class BlogPageSetting extends Model
{
    use SyncsLegacyLocaleBaseFields;

    protected $fillable = [
        'hero_title',
        'hero_title_ar',
        'hero_title_en',
        'hero_subtitle',
        'hero_subtitle_ar',
        'hero_subtitle_en',
        'hero_background_image',
        'hero_left_badge',
        'hero_left_badge_ar',
        'hero_left_badge_en',
        'hero_right_badge',
        'hero_right_badge_ar',
        'hero_right_badge_en',
    ];

    /**
     * Get the hero background image full URL.
     */
    public function getHeroBackgroundImageUrlAttribute(): ?string
    {
        return $this->hero_background_image
            ? asset('storage/' . $this->hero_background_image)
            : null;
    }

    /**
     * Get the single blog page settings record (singleton pattern).
     */
    public static function get(): self
    {
        $setting = static::first();
        if (!$setting) {
            $setting = static::create([
                'hero_title_ar' => 'المدونة',
                'hero_title_en' => 'Blog',
                'hero_subtitle_ar' => 'أحدث المقالات ونصائح الموضة',
                'hero_subtitle_en' => 'Latest articles and fashion tips',
                'hero_left_badge_ar' => null,
                'hero_left_badge_en' => null,
                'hero_right_badge_ar' => null,
                'hero_right_badge_en' => null,
            ]);
        }
        return $setting;
    }

    protected function getLocaleBaseFieldMap(): array
    {
        return [
            'hero_title' => ['hero_title_en', 'hero_title_ar'],
            'hero_subtitle' => ['hero_subtitle_en', 'hero_subtitle_ar'],
            'hero_left_badge' => ['hero_left_badge_en', 'hero_left_badge_ar'],
            'hero_right_badge' => ['hero_right_badge_en', 'hero_right_badge_ar'],
        ];
    }
}
