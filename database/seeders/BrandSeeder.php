<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'description' => 'شركة نايكي الأمريكية المتخصصة في الأحذية الرياضية والملابس الرياضية',
                'website' => 'https://www.nike.com',
                'contact_email' => 'partnerships@nike.com',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
                'meta_title' => 'Nike - شركة نايكي للأحذية الرياضية',
                'meta_description' => 'أحذية نايكي الرياضية عالية الجودة للمحترفين والهواة',
                'meta_keywords' => 'نايكي, أحذية رياضية, نايكي السعودية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Adidas',
                'slug' => 'adidas',
                'description' => 'شركة أديداس الألمانية الرائدة في صناعة الأحذية الرياضية',
                'website' => 'https://www.adidas.com',
                'contact_email' => 'partnerships@adidas.com',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'meta_title' => 'Adidas - شركة أديداس للأحذية الرياضية',
                'meta_description' => 'أحذية أديداس الرياضية الألمانية عالية الجودة',
                'meta_keywords' => 'أديداس, أحذية رياضية, أديداس السعودية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Puma',
                'slug' => 'puma',
                'description' => 'شركة بوما الألمانية المتخصصة في الأحذية الرياضية والملابس',
                'website' => 'https://www.puma.com',
                'contact_email' => 'partnerships@puma.com',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
                'meta_title' => 'Puma - شركة بوما للأحذية الرياضية',
                'meta_description' => 'أحذية بوما الرياضية الألمانية للرياضيين المحترفين',
                'meta_keywords' => 'بوما, أحذية رياضية, بوما السعودية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'New Balance',
                'slug' => 'new-balance',
                'description' => 'شركة نيوبالانس الأمريكية المتخصصة في الأحذية الرياضية المريحة',
                'website' => 'https://www.newbalance.com',
                'contact_email' => 'partnerships@newbalance.com',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
                'meta_title' => 'New Balance - أحذية رياضية مريحة',
                'meta_description' => 'أحذية نيوبالانس الرياضية المريحة عالية الجودة',
                'meta_keywords' => 'نيوبالانس, أحذية رياضية, أحذية مريحة',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Converse',
                'slug' => 'converse',
                'description' => 'شركة كونفيرس الأمريكية الشهيرة بأحذية الكانفاس الكلاسيكية',
                'website' => 'https://www.converse.com',
                'contact_email' => 'partnerships@converse.com',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 5,
                'meta_title' => 'Converse - أحذية كانفاس كلاسيكية',
                'meta_description' => 'أحذية كونفيرس الكانفاس الكلاسيكية للشباب',
                'meta_keywords' => 'كونفيرس, أحذية كانفاس, أحذية كلاسيكية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Vans',
                'slug' => 'vans',
                'description' => 'شركة فانز الأمريكية المتخصصة في أحذية التزلج والستايل الحضري',
                'website' => 'https://www.vans.com',
                'contact_email' => 'partnerships@vans.com',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 6,
                'meta_title' => 'Vans - أحذية تزلج وستايل حضري',
                'meta_description' => 'أحذية فانز للتزلج والستايل الحضري للشباب',
                'meta_keywords' => 'فانز, أحذية تزلج, أحذية ستايل',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Reebok',
                'slug' => 'reebok',
                'description' => 'شركة ريبوك البريطانية المتخصصة في الأحذية الرياضية واللياقة البدنية',
                'website' => 'https://www.reebok.com',
                'contact_email' => 'partnerships@reebok.com',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 7,
                'meta_title' => 'Reebok - أحذية رياضية ولياقة بدنية',
                'meta_description' => 'أحذية ريبوك الرياضية للتدريب واللياقة البدنية',
                'meta_keywords' => 'ريبوك, أحذية رياضية, لياقة بدنية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Under Armour',
                'slug' => 'under-armour',
                'description' => 'شركة أندر آرمور الأمريكية المتخصصة في الأحذية الرياضية والأداء العالي',
                'website' => 'https://www.underarmour.com',
                'contact_email' => 'partnerships@underarmour.com',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 8,
                'meta_title' => 'Under Armour - أحذية رياضية عالية الأداء',
                'meta_description' => 'أحذية أندر آرمور الرياضية عالية الأداء للمحترفين',
                'meta_keywords' => 'أندر آرمور, أحذية رياضية, أداء عالي',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        DB::table('brands')->insert($brands);
    }
}
