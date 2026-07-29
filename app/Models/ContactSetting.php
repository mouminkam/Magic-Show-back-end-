<?php

namespace App\Models;

use App\Models\Concerns\SyncsLegacyLocaleBaseFields;
use Illuminate\Database\Eloquent\Model;

class ContactSetting extends Model
{
    use SyncsLegacyLocaleBaseFields;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
        'details_title',
        'details_title_ar',
        'details_title_en',
        'address',
        'address_ar',
        'address_en',
        'email',
        'phone',
        'fax',
        'about_title',
        'about_title_ar',
        'about_title_en',
        'about_text',
        'about_text_ar',
        'about_text_en',
        'map_url',
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
     * Get the single contact settings record (singleton pattern).
     */
    public static function get(): self
    {
        $setting = static::first();
        if (!$setting) {
            $setting = static::create([
                'hero_title_ar' => 'تواصل معنا',
                'hero_title_en' => 'Contact Us',
                'hero_subtitle_ar' => 'نحن هنا لمساعدتك',
                'hero_subtitle_en' => "We're Here to Help",
                'details_title_ar' => 'تفاصيل التواصل',
                'details_title_en' => 'Contact Detail',
                'address_ar' => '',
                'address_en' => '',
                'email' => '',
                'phone' => '',
                'fax' => '',
                'about_title_ar' => 'عنا',
                'about_title_en' => 'About Us',
                'about_text_ar' => '',
                'about_text_en' => '',
                'map_url' => '',
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
            'details_title' => ['details_title_en', 'details_title_ar'],
            'address' => ['address_en', 'address_ar'],
            'about_title' => ['about_title_en', 'about_title_ar'],
            'about_text' => ['about_text_en', 'about_text_ar'],
        ];
    }
}
