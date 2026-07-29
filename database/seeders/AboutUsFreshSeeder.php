<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AboutSection;
use App\Models\AboutStat;
use App\Models\TeamMember;
use App\Models\Testimonial;

/**
 * يمسح كل بيانات About Us ثم يعيد تعبئتها بمحتوى عربي وإنجليزي.
 * تشغيل: php artisan db:seed --class=AboutUsFreshSeeder
 */
class AboutUsFreshSeeder extends Seeder
{
    public function run(): void
    {
        // الخطوة الأولى: مسح كل السجلات
        AboutSection::query()->delete();
        AboutStat::query()->delete();
        TeamMember::query()->delete();
        Testimonial::query()->delete();

        $this->command->info('About Us data cleared.');

        // الخطوة الثانية: تعبيئة باللغتين (عربي + إنجليزي)
        $this->call([
            AboutSectionSeeder::class,
            AboutStatSeeder::class,
            TeamMemberSeeder::class,
            TestimonialSeeder::class,
        ]);

        $this->command->info('About Us data refilled with Arabic and English content.');
    }
}
