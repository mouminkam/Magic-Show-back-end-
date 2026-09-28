<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    public function run()
    {
        Warehouse::truncate();
        
        $warehouses = [
            [
                'name' => 'المستودع الرئيسي - دمشق',
                'address' => 'منطقة الصناعة، دمشق، سوريا',
                'city' => 'دمشق',
                'state' => 'دمشق',
                'country' => 'سوريا',
                'postal_code' => '10002',
                'phone' => '+963-11-1111111',
                'email' => 'warehouse.damascus@magicshoe.test',
                'manager_name' => 'عبد الرحمن محمد',
                'manager_phone' => '+963-11-9999999',
                'manager_email' => 'abdelrahman@magicshoe.test',
                'latitude' => 33.5000,
                'longitude' => 36.3000,
                'is_active' => true,
                'type' => 'main',
                'capacity' => 5000.00,
                'capacity_unit' => 'sqm',
            ],
            [
                'name' => 'مستودع التوزيع - حلب',
                'address' => 'منطقة الشهباء الصناعية، حلب، سوريا',
                'city' => 'حلب',
                'state' => 'حلب',
                'country' => 'سوريا',
                'postal_code' => '21001',
                'phone' => '+963-21-2222222',
                'email' => 'warehouse.aleppo@magicshoe.test',
                'manager_name' => 'محمود الحلبي',
                'manager_phone' => '+963-21-8888888',
                'manager_email' => 'mahmoud@magicshoe.test',
                'latitude' => 36.2000,
                'longitude' => 37.1000,
                'is_active' => true,
                'type' => 'distribution',
                'capacity' => 3000.00,
                'capacity_unit' => 'sqm',
            ],
            [
                'name' => 'مستودع التخزين - حمص',
                'address' => 'المنطقة الصناعية الشمالية، حمص، سوريا',
                'city' => 'حمص',
                'state' => 'حمص',
                'country' => 'سوريا',
                'postal_code' => '13001',
                'phone' => '+963-31-3333333',
                'email' => 'warehouse.homs@magicshoe.test',
                'manager_name' => 'عبد الله الحمصي',
                'manager_phone' => '+963-31-7777777',
                'manager_email' => 'abdullah@magicshoe.test',
                'latitude' => 34.7000,
                'longitude' => 36.7000,
                'is_active' => true,
                'type' => 'storage',
                'capacity' => 2500.00,
                'capacity_unit' => 'sqm',
            ],
            [
                'name' => 'مستودع البيع بالتجزئة - اللاذقية',
                'address' => 'منطقة الميناء، اللاذقية، سوريا',
                'city' => 'اللاذقية',
                'state' => 'اللاذقية',
                'country' => 'سوريا',
                'postal_code' => '35001',
                'phone' => '+963-41-4444444',
                'email' => 'warehouse.latakia@magicshoe.test',
                'manager_name' => 'ليلى الساحلية',
                'manager_phone' => '+963-41-6666666',
                'manager_email' => 'layla@magicshoe.test',
                'latitude' => 35.5000,
                'longitude' => 35.8000,
                'is_active' => true,
                'type' => 'retail',
                'capacity' => 1500.00,
                'capacity_unit' => 'sqm',
            ],
            [
                'name' => 'مستودع طرطوس',
                'address' => 'منطقة الميناء التجاري، طرطوس، سوريا',
                'city' => 'طرطوس',
                'state' => 'طرطوس',
                'country' => 'سوريا',
                'postal_code' => '32001',
                'phone' => '+963-43-5555555',
                'email' => 'warehouse.tartus@magicshoe.test',
                'manager_name' => 'ياسر البحري',
                'manager_phone' => '+963-43-5555555',
                'manager_email' => 'yasser@magicshoe.test',
                'latitude' => 34.9000,
                'longitude' => 35.9000,
                'is_active' => true,
                'type' => 'storage',
                'capacity' => 1200.00,
                'capacity_unit' => 'sqm',
            ]
        ];
        
        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }
        
        $this->command->info('✅ تم إنشاء المخازن بنجاح');
    }
}