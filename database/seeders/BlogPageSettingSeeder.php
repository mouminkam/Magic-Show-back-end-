<?php

namespace Database\Seeders;

use App\Models\BlogPageSetting;
use Illuminate\Database\Seeder;

class BlogPageSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'hero_title_ar' => 'المدونة',
            'hero_title_en' => 'Blog',
            'hero_subtitle_ar' => 'أحدث المقالات ونصائح الموضة',
            'hero_subtitle_en' => 'Latest articles and fashion tips',
            'hero_background_image' => null,
            'hero_left_badge_ar' => 'ترندات 2024',
            'hero_left_badge_en' => 'Trends 2024',
            'hero_right_badge_ar' => 'نصائح ستايل',
            'hero_right_badge_en' => 'Style tips',
        ];

        $setting = BlogPageSetting::first();
        if ($setting) {
            $setting->update($data);
        } else {
            BlogPageSetting::create($data);
        }
    }
}
