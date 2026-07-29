<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopPageSetting extends Model
{
    protected $fillable = [
        'hero_title_ar',
        'hero_title_en',
        'hero_subtitle_ar',
        'hero_subtitle_en',
        'hero_background_image',
        'hero_left_badge_ar',
        'hero_left_badge_en',
        'hero_right_badge_ar',
        'hero_right_badge_en',
    ];

    public function getHeroBackgroundImageUrlAttribute(): ?string
    {
        return $this->hero_background_image
            ? asset('storage/' . $this->hero_background_image)
            : null;
    }

    public static function get(): self
    {
        $setting = static::first();
        if (!$setting) {
            $setting = static::create([
                'hero_title_ar' => 'المتجر',
                'hero_title_en' => 'Shop',
                'hero_subtitle_ar' => 'تصفح مجموعتنا المميزة',
                'hero_subtitle_en' => 'Browse our premium collection',
                'hero_left_badge_ar' => null,
                'hero_left_badge_en' => null,
                'hero_right_badge_ar' => null,
                'hero_right_badge_en' => null,
            ]);
        }
        return $setting;
    }
}
