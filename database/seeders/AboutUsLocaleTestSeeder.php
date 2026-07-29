<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AboutSection;
use App\Models\AboutStat;
use App\Models\TeamMember;
use App\Models\Testimonial;

/**
 * يحدّث محتوى About Us بعربي وإنجليزي واضح لاختبار التبديل في Postman.
 *
 * تشغيل السيدر:
 *   php artisan db:seed --class=AboutUsLocaleTestSeeder
 *
 * اختبار في Postman:
 *   - Base URL: /api/v1/about/
 *   - Endpoints: hero, description, stats, team-members, testimonials
 *   - Header: Accept-Language = ar  ← محتوى عربي
 *   - Header: Accept-Language = en  ← محتوى إنجليزي
 *   مثال: GET {{base}}/about/hero  مع Header Accept-Language: ar
 */
class AboutUsLocaleTestSeeder extends Seeder
{
    public function run(): void
    {
        // ——— About Sections (Hero + Description) ———
        $hero = AboutSection::where('section_key', 'hero')->first();
        if ($hero) {
            $hero->update([
                'title_ar' => 'عنا - محتوى عربي للاختبار',
                'title_en' => 'About Us - English Test Content',
                'left_badge_ar' => 'جودة ممتازة',
                'left_badge_en' => 'Premium Quality',
                'right_badge_ar' => 'منذ 2024',
                'right_badge_en' => 'Since 2024',
            ]);
        }

        $description = AboutSection::where('section_key', 'description')->first();
        if ($description) {
            $description->update([
                'title_ar' => 'قصتنا - عنوان عربي',
                'title_en' => 'Our Story - English Title',
                'subtitle_ar' => 'صناعة التميز',
                'subtitle_en' => 'Crafting Excellence',
                'description_ar' => 'نحن علامة مجوهرات متميزة نصنع قطعاً خالدة تحتفي بأهم اللحظات. بخبرة سنوات وشغف بالكمال، نقدّم لكم تصاميم راقية تمزج الحرفية التقليدية بالجمال المعاصر. هذا النص للاختبار بالعربي.',
                'description_en' => 'We are a premium jewelry brand dedicated to crafting timeless pieces that celebrate life\'s special moments. With years of expertise and a passion for perfection, we bring you exquisite designs. This is English test content.',
                'button_text_ar' => 'اعرف المزيد',
                'button_text_en' => 'Learn More',
                'features_ar' => [
                    'مواد بجودة ممتازة',
                    'حرفية خبراء',
                    'ضمان مدى الحياة',
                    'مصادر أخلاقية',
                ],
                'features_en' => [
                    'Premium Quality Materials',
                    'Expert Craftsmanship',
                    'Lifetime Warranty',
                    'Ethical Sourcing',
                ],
            ]);
        }

        // ——— About Stats ———
        $statsTitles = [
            ['title_ar' => 'عملاء سعداء', 'title_en' => 'Happy Customers'],
            ['title_ar' => 'جوائز حصدناها', 'title_en' => 'Awards Won'],
            ['title_ar' => 'منتجات مباعة', 'title_en' => 'Products Sold'],
            ['title_ar' => 'معدل النمو', 'title_en' => 'Growth Rate'],
        ];
        AboutStat::orderBy('sort_order')->get()->each(function ($stat, $i) use ($statsTitles) {
            $t = $statsTitles[$i] ?? ['title_ar' => 'عنوان عربي', 'title_en' => 'English Title'];
            $stat->update([
                'title_ar' => $t['title_ar'],
                'title_en' => $t['title_en'],
                'suffix_ar' => $stat->suffix,
                'suffix_en' => $stat->suffix,
            ]);
        });

        // ——— Team Members ———
        $team = [
            ['name_ar' => 'جون سميث - عربي', 'name_en' => 'John Smith', 'role_ar' => 'المدير المؤسس', 'role_en' => 'CEO & Founder', 'bio_ar' => 'خبرة أكثر من 20 عاماً في تصميم المجوهرات. قيادة الفريق بشغف ورؤية. نص عربي للاختبار.', 'bio_en' => 'With over 20 years of experience in jewelry design, John leads our team with passion and vision.'],
            ['name_ar' => 'سارة جونسون - عربي', 'name_en' => 'Sarah Johnson', 'role_ar' => 'رئيسة التصميم', 'role_en' => 'Head Designer', 'bio_ar' => 'سارة تجلب الإبداع والابتكار لكل قطعة. نص عربي للاختبار.', 'bio_en' => 'Sarah brings creativity and innovation to every piece, ensuring unique and timeless designs.'],
            ['name_ar' => 'مايكل براون - عربي', 'name_en' => 'Michael Brown', 'role_ar' => 'حرفي ماهر', 'role_en' => 'Master Craftsman', 'bio_ar' => 'خبرة مايكل تضمن أن كل قطعة تلبي أعلى المعايير. نص عربي للاختبار.', 'bio_en' => 'Michael\'s expertise in craftsmanship ensures every piece meets our highest quality standards.'],
            ['name_ar' => 'إيميلي ديفيس - عربي', 'name_en' => 'Emily Davis', 'role_ar' => 'مديرة علاقات العملاء', 'role_en' => 'Customer Relations Manager', 'bio_ar' => 'إيميلي تضمن تجربة استثنائية لكل عميل. نص عربي للاختبار.', 'bio_en' => 'Emily ensures every customer has an exceptional experience with our brand.'],
        ];
        TeamMember::orderBy('sort_order')->get()->each(function ($member, $i) use ($team) {
            $t = $team[$i] ?? [];
            if (!empty($t)) {
                $member->update($t);
            }
        });

        // ——— Testimonials ———
        $testimonialsData = [
            ['customer_name_ar' => 'جينيفر ويلسون - عربي', 'customer_name_en' => 'Jennifer Wilson', 'text_ar' => 'أحببت شرائي جداً! الجودة ممتازة والتصميم جميل. نص عربي للاختبار.', 'text_en' => 'Absolutely love my purchase! The quality is outstanding and the design is beautiful. Will definitely shop here again!'],
            ['customer_name_ar' => 'ديفيد مارتينيز - عربي', 'customer_name_en' => 'David Martinez', 'text_ar' => 'أفضل متجر مجوهرات. الخدمة استثنائية والمنتجات من الطراز الأول. نص عربي للاختبار.', 'text_en' => 'Best jewelry store I\'ve ever shopped at. The customer service is exceptional and the products are top-notch.'],
            ['customer_name_ar' => 'ليزا أندرسون - عربي', 'customer_name_en' => 'Lisa Anderson', 'text_ar' => 'اشتريت عقداً لزوجتي وهي تحبه. الحرفية مذهلة. نص عربي للاختبار.', 'text_en' => 'I bought a necklace for my wife and she absolutely loves it! The craftsmanship is incredible.'],
            ['customer_name_ar' => 'روبرت تايلور - عربي', 'customer_name_en' => 'Robert Taylor', 'text_ar' => 'تشكيلة رائعة وأسعار عادلة. الجودة تتحدث عن نفسها. نص عربي للاختبار.', 'text_en' => 'Great selection and fair prices. The quality speaks for itself. Highly recommend!'],
            ['customer_name_ar' => 'أماندا وايت - عربي', 'customer_name_en' => 'Amanda White', 'text_ar' => 'قطع جميلة وخدمة عملاء ممتازة. عميلة مدى الحياة. نص عربي للاختبار.', 'text_en' => 'Beautiful pieces and excellent customer service. I\'m a customer for life!'],
            ['customer_name_ar' => 'جيمس هاريس - عربي', 'customer_name_en' => 'James Harris', 'text_ar' => 'الاهتمام بالتفاصيل مذهل. كل قطعة عمل فني. نص عربي للاختبار.', 'text_en' => 'The attention to detail is amazing. Every piece is a work of art.'],
        ];
        Testimonial::orderBy('sort_order')->get()->each(function ($t, $i) use ($testimonialsData) {
            $d = $testimonialsData[$i] ?? [];
            if (!empty($d)) {
                $t->update($d);
            }
        });

        $this->command->info('About Us locale test content updated. Test in Postman with Accept-Language: ar or en');
    }
}
