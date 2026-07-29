<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TeamMember;

class TeamMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = [
            [
                'name' => 'John Smith',
                'name_ar' => 'جون سميث',
                'name_en' => 'John Smith',
                'role' => 'CEO & Founder',
                'role_ar' => 'المدير المؤسس',
                'role_en' => 'CEO & Founder',
                'bio' => 'With over 20 years of experience in jewelry design, John leads our team with passion and vision.',
                'bio_ar' => 'خبرة أكثر من 20 عاماً في تصميم المجوهرات. قيادة الفريق بشغف ورؤية.',
                'bio_en' => 'With over 20 years of experience in jewelry design, John leads our team with passion and vision.',
                'email' => 'john@magicshow.com',
                'phone' => '+961 1 234 567',
                'social_links' => [
                    'linkedin' => 'https://linkedin.com/in/johnsmith',
                    'twitter' => 'https://twitter.com/johnsmith'
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Sarah Johnson',
                'name_ar' => 'سارة جونسون',
                'name_en' => 'Sarah Johnson',
                'role' => 'Head Designer',
                'role_ar' => 'رئيسة التصميم',
                'role_en' => 'Head Designer',
                'bio' => 'Sarah brings creativity and innovation to every piece, ensuring unique and timeless designs.',
                'bio_ar' => 'سارة تجلب الإبداع والابتكار لكل قطعة، تصاميم فريدة وخالدة.',
                'bio_en' => 'Sarah brings creativity and innovation to every piece, ensuring unique and timeless designs.',
                'email' => 'sarah@magicshow.com',
                'phone' => '+961 1 234 568',
                'social_links' => [
                    'linkedin' => 'https://linkedin.com/in/sarahjohnson',
                    'instagram' => 'https://instagram.com/sarahjohnson'
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Michael Brown',
                'name_ar' => 'مايكل براون',
                'name_en' => 'Michael Brown',
                'role' => 'Master Craftsman',
                'role_ar' => 'حرفي ماهر',
                'role_en' => 'Master Craftsman',
                'bio' => 'Michael\'s expertise in craftsmanship ensures every piece meets our highest quality standards.',
                'bio_ar' => 'خبرة مايكل تضمن أن كل قطعة تلبي أعلى المعايير.',
                'bio_en' => 'Michael\'s expertise in craftsmanship ensures every piece meets our highest quality standards.',
                'email' => 'michael@magicshow.com',
                'phone' => '+961 1 234 569',
                'social_links' => [
                    'linkedin' => 'https://linkedin.com/in/michaelbrown'
                ],
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Emily Davis',
                'name_ar' => 'إيميلي ديفيس',
                'name_en' => 'Emily Davis',
                'role' => 'Customer Relations Manager',
                'role_ar' => 'مديرة علاقات العملاء',
                'role_en' => 'Customer Relations Manager',
                'bio' => 'Emily ensures every customer has an exceptional experience with our brand.',
                'bio_ar' => 'إيميلي تضمن تجربة استثنائية لكل عميل مع علامتنا.',
                'bio_en' => 'Emily ensures every customer has an exceptional experience with our brand.',
                'email' => 'emily@magicshow.com',
                'phone' => '+961 1 234 570',
                'social_links' => [
                    'linkedin' => 'https://linkedin.com/in/emilydavis',
                    'twitter' => 'https://twitter.com/emilydavis'
                ],
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($members as $member) {
            TeamMember::create($member);
        }

        $this->command->info('Team members seeded successfully!');
    }
}
