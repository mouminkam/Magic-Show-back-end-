<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AboutSection;
use App\Models\AboutStat;
use App\Models\TeamMember;
use App\Models\Testimonial;

/**
 * يمسح كل بيانات About Us فقط (بدون تعبيئة).
 * تشغيل: php artisan db:seed --class=AboutUsClearSeeder
 */
class AboutUsClearSeeder extends Seeder
{
    public function run(): void
    {
        AboutSection::query()->delete();
        AboutStat::query()->delete();
        TeamMember::query()->delete();
        Testimonial::query()->delete();

        $this->command->info('About Us data cleared (sections, stats, team members, testimonials).');
    }
}
