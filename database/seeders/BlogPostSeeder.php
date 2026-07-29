<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $authorId = User::first()?->id;
        if (!$authorId) {
            $this->command->warn('No user found. Skip blog posts seeding.');
            return;
        }

        $posts = [
            [
                'title' => 'Simply Tips for Beauty',
                'title_ar' => 'نصائح بسيطة للجمال',
                'title_en' => 'Simply Tips for Beauty',
                'slug' => 'simply-tips-for-beauty',
                'excerpt' => 'Pharetra, erat sed fermentum feugiat...',
                'excerpt_ar' => 'فيراترا، أرات سيد فيرمينتوم فيوجيات، فيليت ماوريس إجيستاس كوام، أوت أليكام ماسا نيسل كويس نيكوي. فيراترا، أرات سيد فيرمينتوم فيوجيات...',
                'excerpt_en' => 'Pharetra, erat sed fermentum feugiat, velit mauris egestas quam...',
                'content' => '<p>Pharetra, erat sed fermentum feugiat...</p>',
                'content_ar' => '<p>فيراترا، أرات سيد فيرمينتوم فيوجيات، فيليت ماوريس إجيستاس كوام، أوت أليكام ماسا نيسل كويس نيكوي.</p><p>سوسبينديس أولتريسيس تيلوس إيجيت نيسل لاكينيا، فيتاي تيمبور ريسوس أليكام.</p>',
                'content_en' => '<p>Pharetra, erat sed fermentum feugiat, velit mauris egestas quam.</p><p>Suspendes ultrices telus eget nisl lacinia, vitae tempor risus aliqam.</p>',
                'status' => 'published',
                'is_featured' => true,
                'published_at' => now()->subDays(10),
            ],
            [
                'title' => 'Latest Fashion Trends',
                'title_ar' => 'أحدث صيحات الموضة',
                'title_en' => 'Latest Fashion Trends',
                'slug' => 'latest-fashion-trends',
                'excerpt' => 'Discover the latest fashion trends this season.',
                'excerpt_ar' => 'اكتشف أحدث صيحات الموضة لهذا الموسم. من الألوان إلى القصات، نقدم لك دليلك الشامل لتبقى في الصدارة.',
                'excerpt_en' => 'Discover the latest fashion trends this season. From colors to cuts, your complete guide to stay on top.',
                'content' => '<p>Discover the latest fashion trends.</p>',
                'content_ar' => '<p>اكتشف أحدث صيحات الموضة لهذا الموسم.</p><p>هذا العام نشهد عودة الكلاسيكيات مع لمسة عصرية.</p>',
                'content_en' => '<p>Discover the latest fashion trends this season.</p><p>This year we see the return of classics with a modern touch.</p>',
                'status' => 'published',
                'is_featured' => true,
                'published_at' => now()->subDays(7),
            ],
            [
                'title' => 'Summer Collection Guide',
                'title_ar' => 'دليل مجموعة الصيف',
                'title_en' => 'Summer Collection Guide',
                'slug' => 'summer-collection-guide',
                'excerpt' => 'Summer collection guide.',
                'excerpt_ar' => 'فيراترا، أرات سيد فيرمينتوم فيوجيات...',
                'excerpt_en' => 'Summer collection combines comfort and elegance.',
                'content' => '<p>Summer collection guide.</p>',
                'content_ar' => '<p>مجموعة الصيف هذا العام تجمع بين الراحة والأناقة.</p><p>الكتان والقطن من أفضل الخيارات للموسم.</p>',
                'content_en' => '<p>This year\'s summer collection combines comfort and elegance.</p><p>Linen and cotton are among the best choices for the season.</p>',
                'status' => 'published',
                'is_featured' => false,
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Winter Fashion Essentials',
                'title_ar' => 'أساسيات موضة الشتاء',
                'title_en' => 'Winter Fashion Essentials',
                'slug' => 'winter-fashion-essentials',
                'excerpt' => 'Winter fashion essentials.',
                'excerpt_ar' => 'تجهيز خزانتك لفصل الشتاء؟ إليك القطع الأساسية التي لا غنى عنها في موسم البرد.',
                'excerpt_en' => 'Preparing your wardrobe for winter? Here are the essential pieces for the cold season.',
                'content' => '<p>Winter fashion essentials.</p>',
                'content_ar' => '<p>تجهيز خزانتك لفصل الشتاء؟</p><p>المعاطف الدافئة، البوتات، والكنزات الصوفية هي أساس إطلالة شتوية أنيقة.</p>',
                'content_en' => '<p>Preparing your wardrobe for winter?</p><p>Warm coats, boots, and wool sweaters are the foundation of an elegant winter look.</p>',
                'status' => 'published',
                'is_featured' => false,
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Spring Collection Preview',
                'title_ar' => 'معاينة مجموعة الربيع',
                'title_en' => 'Spring Collection Preview',
                'slug' => 'spring-collection-preview',
                'excerpt' => 'Spring collection preview.',
                'excerpt_ar' => 'لمحة أولى عن مجموعة الربيع القادمة. ألوان نابضة بالحياة وقطع تناسب كل المناسبات.',
                'excerpt_en' => 'A first look at the upcoming spring collection. Vibrant colors and pieces for every occasion.',
                'content' => '<p>Spring collection preview.</p>',
                'content_ar' => '<p>لمحة أولى عن مجموعة الربيع القادمة.</p><p>الربيع هو وقت التجديد.</p>',
                'content_en' => '<p>A first look at the upcoming spring collection.</p><p>Spring is a time for renewal.</p>',
                'status' => 'published',
                'is_featured' => false,
                'published_at' => now()->subDay(),
            ],
            [
                'title' => 'Accessories Guide 2024',
                'title_ar' => 'دليل الإكسسوارات 2024',
                'title_en' => 'Accessories Guide 2024',
                'slug' => 'accessories-guide-2024',
                'excerpt' => 'Accessories that make the difference.',
                'excerpt_ar' => 'الإكسسوارات التي تصنع الفرق. من الحقائب إلى الإكسسوارات، كيف تكمّل إطلالتك.',
                'excerpt_en' => 'Accessories that make the difference. How to complete your look.',
                'content' => '<p>Accessories that make the difference.</p>',
                'content_ar' => '<p>الإكسسوارات التي تصنع الفرق.</p><p>قطعة واحدة من الإكسسوارات يمكن أن ترفع مستوى أي إطلالة بسيطة.</p>',
                'content_en' => '<p>Accessories that make the difference.</p><p>One accessory can elevate any simple outfit.</p>',
                'status' => 'published',
                'is_featured' => false,
                'published_at' => now(),
            ],
        ];

        foreach ($posts as $data) {
            BlogPost::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'author_id' => $authorId,
                    'category_id' => null,
                    'allow_comments' => true,
                    'view_count' => rand(50, 500),
                    'comment_count' => 0,
                ])
            );
        }
    }
}
