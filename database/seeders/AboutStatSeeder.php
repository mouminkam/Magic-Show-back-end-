<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AboutStat;

class AboutStatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stats = [
            [
                'icon' => 'Users',
                'title' => 'Happy Customers',
                'title_ar' => 'عملاء سعداء',
                'title_en' => 'Happy Customers',
                'value' => '10',
                'suffix' => 'K+',
                'suffix_ar' => 'ك+',
                'suffix_en' => 'K+',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'icon' => 'Award',
                'title' => 'Awards Won',
                'title_ar' => 'جوائز حصدناها',
                'title_en' => 'Awards Won',
                'value' => '25',
                'suffix' => '+',
                'suffix_ar' => '+',
                'suffix_en' => '+',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'icon' => 'ShoppingBag',
                'title' => 'Products Sold',
                'title_ar' => 'منتجات مباعة',
                'title_en' => 'Products Sold',
                'value' => '50',
                'suffix' => 'K+',
                'suffix_ar' => 'ك+',
                'suffix_en' => 'K+',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'icon' => 'TrendingUp',
                'title' => 'Growth Rate',
                'title_ar' => 'معدل النمو',
                'title_en' => 'Growth Rate',
                'value' => '99',
                'suffix' => '%',
                'suffix_ar' => '%',
                'suffix_en' => '%',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($stats as $stat) {
            AboutStat::create($stat);
        }
    }
}
