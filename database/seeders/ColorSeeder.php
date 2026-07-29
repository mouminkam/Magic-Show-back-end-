<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Color;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            // الألوان الأساسية
            [
                'name' => 'أحمر',
                'name_en' => 'Red',
                'color_code' => '#FF0000',
                'hex_code' => '#FF0000',
                'category' => 'أساسي',
                'sort_order' => 1,
                'description' => 'اللون الأحمر الأساسي',
            ],
            [
                'name' => 'أزرق',
                'name_en' => 'Blue',
                'color_code' => '#0000FF',
                'hex_code' => '#0000FF',
                'category' => 'أساسي',
                'sort_order' => 2,
                'description' => 'اللون الأزرق الأساسي',
            ],
            [
                'name' => 'أخضر',
                'name_en' => 'Green',
                'color_code' => '#00FF00',
                'hex_code' => '#00FF00',
                'category' => 'أساسي',
                'sort_order' => 3,
                'description' => 'اللون الأخضر الأساسي',
            ],
            [
                'name' => 'أصفر',
                'name_en' => 'Yellow',
                'color_code' => '#FFFF00',
                'hex_code' => '#FFFF00',
                'category' => 'أساسي',
                'sort_order' => 4,
                'description' => 'اللون الأصفر الأساسي',
            ],
            [
                'name' => 'برتقالي',
                'name_en' => 'Orange',
                'color_code' => '#FFA500',
                'hex_code' => '#FFA500',
                'category' => 'ثانوي',
                'sort_order' => 5,
                'description' => 'اللون البرتقالي',
            ],
            [
                'name' => 'بنفسجي',
                'name_en' => 'Purple',
                'hex_code' => '#800080',
                'category' => 'ثانوي',
                'sort_order' => 6,
                'description' => 'اللون البنفسجي',
            ],
            [
                'name' => 'وردي',
                'name_en' => 'Pink',
                'hex_code' => '#FFC0CB',
                'category' => 'فساتين',
                'sort_order' => 7,
                'description' => 'اللون الوردي',
            ],
            [
                'name' => 'أسود',
                'name_en' => 'Black',
                'hex_code' => '#000000',
                'category' => 'محايد',
                'sort_order' => 8,
                'description' => 'اللون الأسود',
            ],
            [
                'name' => 'أبيض',
                'name_en' => 'White',
                'hex_code' => '#FFFFFF',
                'category' => 'محايد',
                'sort_order' => 9,
                'description' => 'اللون الأبيض',
            ],
            [
                'name' => 'رمادي',
                'name_en' => 'Gray',
                'hex_code' => '#808080',
                'category' => 'محايد',
                'sort_order' => 10,
                'description' => 'اللون الرمادي',
            ],
            [
                'name' => 'بني',
                'name_en' => 'Brown',
                'hex_code' => '#8B4513',
                'category' => 'طبيعي',
                'sort_order' => 11,
                'description' => 'اللون البني',
            ],
            [
                'name' => 'بيج',
                'name_en' => 'Beige',
                'hex_code' => '#F5F5DC',
                'category' => 'محايد',
                'sort_order' => 12,
                'description' => 'اللون البيج',
            ],
            [
                'name' => 'ذهبي',
                'name_en' => 'Gold',
                'hex_code' => '#FFD700',
                'category' => 'معدني',
                'sort_order' => 13,
                'description' => 'اللون الذهبي',
            ],
            [
                'name' => 'فضي',
                'name_en' => 'Silver',
                'hex_code' => '#C0C0C0',
                'category' => 'معدني',
                'sort_order' => 14,
                'description' => 'اللون الفضي',
            ],
            [
                'name' => 'نبيتي',
                'name_en' => 'Navy',
                'hex_code' => '#000080',
                'category' => 'أساسي',
                'sort_order' => 15,
                'description' => 'اللون النبيتي',
            ],
            [
                'name' => 'تركوازي',
                'name_en' => 'Turquoise',
                'hex_code' => '#40E0D0',
                'category' => 'ثانوي',
                'sort_order' => 16,
                'description' => 'اللون التركوازي',
            ],
            [
                'name' => 'مرجاني',
                'name_en' => 'Coral',
                'hex_code' => '#FF7F50',
                'category' => 'فساتين',
                'sort_order' => 17,
                'description' => 'اللون المرجاني',
            ],
            [
                'name' => 'خمري',
                'name_en' => 'Burgundy',
                'hex_code' => '#800020',
                'category' => 'فساتين',
                'sort_order' => 18,
                'description' => 'اللون الخمري',
            ],
            [
                'name' => 'زيتوني',
                'name_en' => 'Olive',
                'hex_code' => '#808000',
                'category' => 'طبيعي',
                'sort_order' => 19,
                'description' => 'اللون الزيتوني',
            ],
            [
                'name' => 'كريمي',
                'name_en' => 'Cream',
                'hex_code' => '#FFF8DC',
                'category' => 'محايد',
                'sort_order' => 20,
                'description' => 'اللون الكريمي',
            ],
        ];

        foreach ($colors as $colorData) {
            if (!isset($colorData['color_code']) && isset($colorData['hex_code'])) {
                $colorData['color_code'] = $colorData['hex_code'];
            }
            Color::create($colorData);
        }
    }
}
