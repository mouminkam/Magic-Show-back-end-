<?php

namespace Database\Seeders;

use App\Models\StorePageSetting;
use Illuminate\Database\Seeder;

class StorePageSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'hero_title_ar' => 'أين تجدنا',
            'hero_title_en' => 'Where to Find Us',
            'hero_subtitle_ar' => 'زر فروعنا',
            'hero_subtitle_en' => 'Visit Our Branches',
            'hero_background_image' => null,
            'hero_left_badge_ar' => null,
            'hero_left_badge_en' => null,
            'hero_right_badge_ar' => null,
            'hero_right_badge_en' => null,
        ];

        $setting = StorePageSetting::first();
        if ($setting) {
            $setting->update($data);
        } else {
            StorePageSetting::create($data);
        }
    }
}
