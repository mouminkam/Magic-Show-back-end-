<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    public function run()
    {
        // حذف العملات الموجودة
        Currency::truncate();
        
        $currencies = [
            [
                'name' => 'الدولار الأمريكي',
                'code' => 'USD',
                'symbol' => '$',
                'symbol_position' => 'before',
                'exchange_rate' => 1.0,
                'is_default' => true,
                'is_base' => true,
                'is_active' => true,
                'decimal_places' => 2,
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'sort_order' => 1,
            ],
            [
                'name' => 'الليرة السورية',
                'code' => 'SYP',
                'symbol' => 'ل.س',
                'symbol_position' => 'after',
                'exchange_rate' => 11500.0, // سعر صرف الدولار مقابل الليرة السورية
                'is_default' => false,
                'is_base' => false,
                'is_active' => true,
                'decimal_places' => 0,
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'sort_order' => 2,
            ],
            [
                'name' => 'اليورو',
                'code' => 'EUR',
                'symbol' => '€',
                'symbol_position' => 'before',
                'exchange_rate' => 0.92,
                'is_default' => false,
                'is_base' => false,
                'is_active' => true,
                'decimal_places' => 2,
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'sort_order' => 3,
            ]
        ];
        
        foreach ($currencies as $currency) {
            Currency::create($currency);
        }
        
        $this->command->info('✅ تم إنشاء العملات بنجاح');
    }
}
