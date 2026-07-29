<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AboutSection;

class AboutSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hero Section
        AboutSection::updateOrCreate(
            ['section_key' => 'hero'],
            [
                'title' => 'About Us',
                'title_ar' => 'عنا',
                'title_en' => 'About Us',
                'background_image' => null,
                'left_badge' => 'Premium Quality',
                'left_badge_ar' => 'جودة ممتازة',
                'left_badge_en' => 'Premium Quality',
                'right_badge' => 'Since 2024',
                'right_badge_ar' => 'منذ 2024',
                'right_badge_en' => 'Since 2024',
                'is_active' => true,
            ]
        );

        // Description Section
        AboutSection::updateOrCreate(
            ['section_key' => 'description'],
            [
                'title' => 'Our Story',
                'title_ar' => 'قصتنا',
                'title_en' => 'Our Story',
                'subtitle' => 'Crafting Excellence',
                'subtitle_ar' => 'صناعة التميز',
                'subtitle_en' => 'Crafting Excellence',
                'description' => 'We are a premium jewelry brand dedicated to crafting timeless pieces that celebrate life\'s special moments. With years of expertise and a passion for perfection, we bring you exquisite designs that blend traditional craftsmanship with modern aesthetics.',
                'description_ar' => 'نحن علامة مجوهرات متميزة نصنع قطعاً خالدة تحتفي بأهم اللحظات. بخبرة سنوات وشغف بالكمال، نقدّم لكم تصاميم راقية تمزج الحرفية التقليدية بالجمال المعاصر.',
                'description_en' => 'We are a premium jewelry brand dedicated to crafting timeless pieces that celebrate life\'s special moments. With years of expertise and a passion for perfection, we bring you exquisite designs that blend traditional craftsmanship with modern aesthetics.',
                'image' => null,
                'features' => ['Premium Quality Materials', 'Expert Craftsmanship', 'Lifetime Warranty', 'Ethical Sourcing'],
                'features_ar' => ['مواد بجودة ممتازة', 'حرفية خبراء', 'ضمان مدى الحياة', 'مصادر أخلاقية'],
                'features_en' => ['Premium Quality Materials', 'Expert Craftsmanship', 'Lifetime Warranty', 'Ethical Sourcing'],
                'button_text' => 'Learn More',
                'button_text_ar' => 'اعرف المزيد',
                'button_text_en' => 'Learn More',
                'button_link' => '/shop',
                'is_active' => true,
            ]
        );
    }
}
