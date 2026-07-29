<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Testimonial;

class TestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testimonials = [
            [
                'customer_name' => 'Jennifer Wilson',
                'customer_name_ar' => 'جينيفر ويلسون',
                'customer_name_en' => 'Jennifer Wilson',
                'text' => 'Absolutely love my purchase! The quality is outstanding and the design is beautiful. Will definitely shop here again!',
                'text_ar' => 'أحببت شرائي جداً! الجودة ممتازة والتصميم جميل. سأتسوق هنا مجدداً.',
                'text_en' => 'Absolutely love my purchase! The quality is outstanding and the design is beautiful. Will definitely shop here again!',
                'rating' => 5,
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'customer_name' => 'David Martinez',
                'customer_name_ar' => 'ديفيد مارتينيز',
                'customer_name_en' => 'David Martinez',
                'text' => 'Best jewelry store I\'ve ever shopped at. The customer service is exceptional and the products are top-notch.',
                'text_ar' => 'أفضل متجر مجوهرات. الخدمة استثنائية والمنتجات من الطراز الأول.',
                'text_en' => 'Best jewelry store I\'ve ever shopped at. The customer service is exceptional and the products are top-notch.',
                'rating' => 5,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'customer_name' => 'Lisa Anderson',
                'customer_name_ar' => 'ليزا أندرسون',
                'customer_name_en' => 'Lisa Anderson',
                'text' => 'I bought a necklace for my wife and she absolutely loves it! The craftsmanship is incredible.',
                'text_ar' => 'اشتريت عقداً لزوجتي وهي تحبه جداً! الحرفية مذهلة.',
                'text_en' => 'I bought a necklace for my wife and she absolutely loves it! The craftsmanship is incredible.',
                'rating' => 5,
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'customer_name' => 'Robert Taylor',
                'customer_name_ar' => 'روبرت تايلور',
                'customer_name_en' => 'Robert Taylor',
                'text' => 'Great selection and fair prices. The quality speaks for itself. Highly recommend!',
                'text_ar' => 'تشكيلة رائعة وأسعار عادلة. الجودة تتحدث عن نفسها. أنصح بشدة!',
                'text_en' => 'Great selection and fair prices. The quality speaks for itself. Highly recommend!',
                'rating' => 5,
                'is_featured' => true,
                'sort_order' => 4,
            ],
            [
                'customer_name' => 'Amanda White',
                'customer_name_ar' => 'أماندا وايت',
                'customer_name_en' => 'Amanda White',
                'text' => 'Beautiful pieces and excellent customer service. I\'m a customer for life!',
                'text_ar' => 'قطع جميلة وخدمة عملاء ممتازة. عميلة مدى الحياة!',
                'text_en' => 'Beautiful pieces and excellent customer service. I\'m a customer for life!',
                'rating' => 5,
                'is_featured' => true,
                'sort_order' => 5,
            ],
            [
                'customer_name' => 'James Harris',
                'customer_name_ar' => 'جيمس هاريس',
                'customer_name_en' => 'James Harris',
                'text' => 'The attention to detail is amazing. Every piece is a work of art.',
                'text_ar' => 'الاهتمام بالتفاصيل مذهل. كل قطعة عمل فني.',
                'text_en' => 'The attention to detail is amazing. Every piece is a work of art.',
                'rating' => 5,
                'is_featured' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create($testimonial);
        }

        $this->command->info('Testimonials seeded successfully!');
    }
}
