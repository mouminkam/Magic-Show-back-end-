<?php

namespace Database\Seeders;

use App\Models\ContactSetting;
use Illuminate\Database\Seeder;

class ContactSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // Hero
            'hero_title_ar' => 'تواصل معنا',
            'hero_title_en' => 'Contact Us',
            'hero_subtitle_ar' => 'نحن هنا لمساعدتك',
            'hero_subtitle_en' => "We're Here to Help",
            'hero_background_image' => null,
            'hero_left_badge_ar' => null,
            'hero_left_badge_en' => null,
            'hero_right_badge_ar' => null,
            'hero_right_badge_en' => null,
            // Details
            'details_title_ar' => 'تفاصيل التواصل',
            'details_title_en' => 'Contact Detail',
            'address_ar' => '123 شارع المجوهرات، بيروت، لبنان',
            'address_en' => '123 Jewelry Street, Beirut, Lebanon',
            'email' => 'info@magicshow.com',
            'phone' => '+961 1 234 567',
            'fax' => '+961 1 234 568',
            'about_title_ar' => 'عنا',
            'about_title_en' => 'About Us',
            'about_text_ar' => 'نحن هنا لمساعدتك في إيجاد القطعة المثالية.',
            'about_text_en' => 'We are here to help you find the perfect piece of jewelry.',
            // Map
            'map_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3312.0!2d35.5!3d33.9!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzPCsDU0JzAwLjAiTiAzNcKwMzAnMDAuMCJF!5e0!3m2!1sen!2slb!4v1234567890',
        ];

        $setting = ContactSetting::first();
        if ($setting) {
            $setting->update($data);
        } else {
            ContactSetting::create($data);
        }
    }
}
