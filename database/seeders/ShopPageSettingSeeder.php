<?php

namespace Database\Seeders;

use App\Models\ShopPageSetting;
use Illuminate\Database\Seeder;

class ShopPageSettingSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'hero_title_ar' => 'المتجر',
            'hero_title_en' => 'Shop',
            'hero_subtitle_ar' => 'تصفح مجموعتنا المميزة',
            'hero_subtitle_en' => 'Browse our premium collection',
            'hero_background_image' => null,
            'hero_left_badge_ar' => 'خصم 50%',
            'hero_left_badge_en' => 'SALE 50%',
            'hero_right_badge_ar' => 'ترندات 2024',
            'hero_right_badge_en' => 'TRENDS 2024',
        ];

        $setting = ShopPageSetting::first();
        if ($setting) {
            $setting->update($data);
        } else {
            ShopPageSetting::create($data);
        }
    }
}
