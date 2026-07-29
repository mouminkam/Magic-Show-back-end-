<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_ar' => 'أحذية',
                'name_en' => 'Shoes',
                'description_ar' => 'مجموعة متنوعة من الأحذية',
                'description_en' => 'A variety of shoes',
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'حقائب',
                'name_en' => 'Bags',
                'description_ar' => 'حقائب يد وحقائب ظهر',
                'description_en' => 'Handbags and backpacks',
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'إكسسوارات',
                'name_en' => 'Accessories',
                'description_ar' => 'إكسسوارات الموضة',
                'description_en' => 'Fashion accessories',
                'sort_order' => 3,
            ],
            [
                'name_ar' => 'كعوب',
                'name_en' => 'Heels',
                'description_ar' => 'كعوب عالية وأنيقة',
                'description_en' => 'Elegant high heels',
                'sort_order' => 4,
            ],
            [
                'name_ar' => 'سنيكرز',
                'name_en' => 'Sneakers',
                'description_ar' => 'أحذية رياضية ومريحة',
                'description_en' => 'Comfortable sports shoes',
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $item) {
            $name = $item['name_en'] ?? $item['name_ar'];
            $slug = Str::slug($name);
            Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'name_ar' => $item['name_ar'],
                    'name_en' => $item['name_en'],
                    'description' => $item['description_en'] ?? $item['description_ar'] ?? null,
                    'description_ar' => $item['description_ar'] ?? null,
                    'description_en' => $item['description_en'] ?? null,
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
