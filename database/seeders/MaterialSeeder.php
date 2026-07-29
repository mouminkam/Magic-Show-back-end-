<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materials = [
            [
                'name' => 'جلد طبيعي',
                'slug' => 'leather-natural',
                'description' => 'جلد طبيعي عالي الجودة من الحيوانات',
                'category' => 'leather',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'جلد صناعي',
                'slug' => 'leather-synthetic',
                'description' => 'جلد صناعي مقاوم للماء وسهل التنظيف',
                'category' => 'leather',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'كانفاس',
                'slug' => 'canvas',
                'description' => 'قماش كانفاس قوي ومتين للاستخدام اليومي',
                'category' => 'fabric',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'قماش شبكي',
                'slug' => 'mesh',
                'description' => 'قماش شبكي خفيف ومسامي للتهوية',
                'category' => 'fabric',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'مطاط طبيعي',
                'slug' => 'rubber-natural',
                'description' => 'مطاط طبيعي مرن ومقاوم للانزلاق',
                'category' => 'rubber',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'مطاط صناعي',
                'slug' => 'rubber-synthetic',
                'description' => 'مطاط صناعي مقاوم للبلى والتآكل',
                'category' => 'rubber',
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'بوليستر',
                'slug' => 'polyester',
                'description' => 'نسيج بوليستر مقاوم للرطوبة وسريع الجفاف',
                'category' => 'synthetic',
                'is_active' => true,
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'نايلون',
                'slug' => 'nylon',
                'description' => 'نسيج نايلون قوي ومرن ومقاوم للتمزق',
                'category' => 'synthetic',
                'is_active' => true,
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'قماش قطني',
                'slug' => 'cotton',
                'description' => 'قماش قطني ناعم ومريح للارتداء',
                'category' => 'fabric',
                'is_active' => true,
                'sort_order' => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'قماش مخلوط',
                'slug' => 'blend',
                'description' => 'خليط من القطن والبوليستر للراحة والمتانة',
                'category' => 'fabric',
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        DB::table('materials')->insert($materials);
    }
}
