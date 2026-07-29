<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // Product::truncate(); // تم تعطيله بسبب foreign key constraints
        
        // الحصول على الفئات
        $shoesCategory = Category::where('name', 'أحذية')->first();
        $bagsCategory = Category::where('name', 'حقائب')->first();
        $accessoriesCategory = Category::where('name', 'اكسسوارات')->first();
        
        $products = [
            // أحذية رجالية
            [
                'name' => 'حذاء جلد طبيعي كلاسيكي للرجال',
                'sku' => 'MSH-M-001',
                'description' => 'حذاء جلد طبيعي عالي الجودة مصنوع يدوياً في دمشق. مناسب للمناسبات الرسمية واليومية.',
                'short_description' => 'حذاء جلد طبيعي كلاسيكي للرجال',
                'price' => 85.00,
                'sale_price' => 75.00,
                'compare_price' => 95.00,
                'cost_price' => 45.00,
                'quantity' => 150,
                'min_quantity' => 10,
                'weight' => '0.8 kg',
                'dimensions' => '30 x 25 x 12 cm',
                'barcode' => '1234567890123',
                'model' => 'Classic-2025',
                'is_active' => true,
                'is_featured' => true,
                'is_digital' => false,
                'requires_shipping' => true,
                'track_quantity' => true,
                'allow_backorder' => false,
                'meta_title' => 'حذاء جلد طبيعي كلاسيكي للرجال - Magic Shoe',
                'meta_description' => 'حذاء جلد طبيعي عالي الجودة للرجال من Magic Shoe',
                'slug' => 'men-classic-leather-shoes',
                'sort_order' => 1,
            ],
            [
                'name' => 'حذاء رياضي للرجال - أبيض',
                'sku' => 'MSH-M-002',
                'description' => 'حذاء رياضي مريح وأنيق للرجال، مصنوع من مواد عالية الجودة.',
                'short_description' => 'حذاء رياضي للرجال - أبيض',
                'price' => 65.00,
                'sale_price' => null,
                'compare_price' => 75.00,
                'cost_price' => 35.00,
                'quantity' => 200,
                'min_quantity' => 15,
                'weight' => '0.6 kg',
                'dimensions' => '32 x 26 x 14 cm',
                'barcode' => '1234567890124',
                'model' => 'Sport-2025',
                'is_active' => true,
                'is_featured' => false,
                'is_digital' => false,
                'requires_shipping' => true,
                'track_quantity' => true,
                'allow_backorder' => true,
                'meta_title' => 'حذاء رياضي للرجال أبيض - Magic Shoe',
                'meta_description' => 'حذاء رياضي مريح للرجال من Magic Shoe',
                'slug' => 'men-white-sports-shoes',
                'sort_order' => 2,
            ],
            [
                'name' => 'حذاء بوت شتوي للرجال',
                'sku' => 'MSH-M-003',
                'description' => 'حذاء بوت شتوي دافئ ومقاوم للماء للرجال.',
                'short_description' => 'حذاء بوت شتوي للرجال',
                'price' => 95.00,
                'sale_price' => 85.00,
                'compare_price' => 110.00,
                'cost_price' => 55.00,
                'quantity' => 80,
                'min_quantity' => 5,
                'weight' => '1.2 kg',
                'dimensions' => '35 x 28 x 16 cm',
                'barcode' => '1234567890125',
                'model' => 'Winter-2025',
                'is_active' => true,
                'is_featured' => true,
                'is_digital' => false,
                'requires_shipping' => true,
                'track_quantity' => true,
                'allow_backorder' => false,
                'meta_title' => 'حذاء بوت شتوي للرجال - Magic Shoe',
                'meta_description' => 'حذاء بوت شتوي دافئ للرجال من Magic Shoe',
                'slug' => 'men-winter-boots',
                'sort_order' => 3,
            ],
            
            // أحذية نسائية
            [
                'name' => 'حذاء كعب عالي للنساء - أسود',
                'sku' => 'MSH-F-001',
                'description' => 'حذاء كعب عالي أنيق للنساء، مثالي للمناسبات والاحتفالات.',
                'short_description' => 'حذاء كعب عالي للنساء - أسود',
                'price' => 75.00,
                'sale_price' => 65.00,
                'compare_price' => 85.00,
                'cost_price' => 40.00,
                'quantity' => 120,
                'min_quantity' => 8,
                'weight' => '0.7 kg',
                'dimensions' => '28 x 22 x 15 cm',
                'barcode' => '1234567890126',
                'model' => 'Heels-2025',
                'is_active' => true,
                'is_featured' => true,
                'is_digital' => false,
                'requires_shipping' => true,
                'track_quantity' => true,
                'allow_backorder' => true,
                'meta_title' => 'حذاء كعب عالي للنساء أسود - Magic Shoe',
                'meta_description' => 'حذاء كعب عالي أنيق للنساء من Magic Shoe',
                'slug' => 'women-black-heels',
                'sort_order' => 4,
            ],
            [
                'name' => 'حذاء مسطح للنساء - بيج',
                'sku' => 'MSH-F-002',
                'description' => 'حذاء مسطح مريح وأنيق للنساء، مناسب للاستخدام اليومي.',
                'short_description' => 'حذاء مسطح للنساء - بيج',
                'price' => 55.00,
                'sale_price' => null,
                'compare_price' => 65.00,
                'cost_price' => 30.00,
                'quantity' => 180,
                'min_quantity' => 12,
                'weight' => '0.5 kg',
                'dimensions' => '26 x 20 x 8 cm',
                'barcode' => '1234567890127',
                'model' => 'Flat-2025',
                'is_active' => true,
                'is_featured' => false,
                'is_digital' => false,
                'requires_shipping' => true,
                'track_quantity' => true,
                'allow_backorder' => true,
                'meta_title' => 'حذاء مسطح للنساء بيج - Magic Shoe',
                'meta_description' => 'حذاء مسطح مريح للنساء من Magic Shoe',
                'slug' => 'women-beige-flats',
                'sort_order' => 5,
            ],
            [
                'name' => 'حذاء رياضي للنساء - وردي',
                'sku' => 'MSH-F-003',
                'description' => 'حذاء رياضي أنيق ومريح للنساء، مثالي للرياضة والمشي.',
                'short_description' => 'حذاء رياضي للنساء - وردي',
                'price' => 70.00,
                'sale_price' => 60.00,
                'compare_price' => 80.00,
                'cost_price' => 38.00,
                'quantity' => 160,
                'min_quantity' => 10,
                'weight' => '0.6 kg',
                'dimensions' => '28 x 22 x 12 cm',
                'barcode' => '1234567890128',
                'model' => 'Sport-F-2025',
                'is_active' => true,
                'is_featured' => true,
                'is_digital' => false,
                'requires_shipping' => true,
                'track_quantity' => true,
                'allow_backorder' => true,
                'meta_title' => 'حذاء رياضي للنساء وردي - Magic Shoe',
                'meta_description' => 'حذاء رياضي أنيق للنساء من Magic Shoe',
                'slug' => 'women-pink-sports-shoes',
                'sort_order' => 6,
            ],
            
            // حقائب
            [
                'name' => 'حقيبة يد نسائية من الجلد الطبيعي',
                'sku' => 'MSH-B-001',
                'description' => 'حقيبة يد أنيقة مصنوعة من الجلد الطبيعي، مثالية للاستخدام اليومي.',
                'short_description' => 'حقيبة يد نسائية من الجلد الطبيعي',
                'price' => 120.00,
                'sale_price' => 100.00,
                'compare_price' => 140.00,
                'cost_price' => 65.00,
                'quantity' => 90,
                'min_quantity' => 6,
                'weight' => '0.8 kg',
                'dimensions' => '35 x 25 x 12 cm',
                'barcode' => '1234567890129',
                'model' => 'Handbag-2025',
                'is_active' => true,
                'is_featured' => true,
                'is_digital' => false,
                'requires_shipping' => true,
                'track_quantity' => true,
                'allow_backorder' => false,
                'meta_title' => 'حقيبة يد نسائية من الجلد الطبيعي - Magic Shoe',
                'meta_description' => 'حقيبة يد أنيقة من الجلد الطبيعي من Magic Shoe',
                'slug' => 'women-leather-handbag',
                'sort_order' => 7,
            ],
            [
                'name' => 'حقيبة ظهر للرجال - سوداء',
                'sku' => 'MSH-B-002',
                'description' => 'حقيبة ظهر عملية وأنيقة للرجال، مناسبة للعمل والسفر.',
                'short_description' => 'حقيبة ظهر للرجال - سوداء',
                'price' => 85.00,
                'sale_price' => null,
                'compare_price' => 95.00,
                'cost_price' => 45.00,
                'quantity' => 110,
                'min_quantity' => 8,
                'weight' => '1.0 kg',
                'dimensions' => '45 x 30 x 15 cm',
                'barcode' => '1234567890130',
                'model' => 'Backpack-2025',
                'is_active' => true,
                'is_featured' => false,
                'is_digital' => false,
                'requires_shipping' => true,
                'track_quantity' => true,
                'allow_backorder' => true,
                'meta_title' => 'حقيبة ظهر للرجال سوداء - Magic Shoe',
                'meta_description' => 'حقيبة ظهر عملية للرجال من Magic Shoe',
                'slug' => 'men-black-backpack',
                'sort_order' => 8,
            ],
            
            // اكسسوارات
            [
                'name' => 'حزام جلدي للرجال - بني',
                'sku' => 'MSH-A-001',
                'description' => 'حزام جلدي أنيق للرجال، مصنوع من الجلد الطبيعي عالي الجودة.',
                'short_description' => 'حزام جلدي للرجال - بني',
                'price' => 35.00,
                'sale_price' => 30.00,
                'compare_price' => 40.00,
                'cost_price' => 18.00,
                'quantity' => 200,
                'min_quantity' => 20,
                'weight' => '0.3 kg',
                'dimensions' => '120 x 4 x 1 cm',
                'barcode' => '1234567890131',
                'model' => 'Belt-2025',
                'is_active' => true,
                'is_featured' => false,
                'is_digital' => false,
                'requires_shipping' => true,
                'track_quantity' => true,
                'allow_backorder' => true,
                'meta_title' => 'حزام جلدي للرجال بني - Magic Shoe',
                'meta_description' => 'حزام جلدي أنيق للرجال من Magic Shoe',
                'slug' => 'men-brown-leather-belt',
                'sort_order' => 9,
            ],
            [
                'name' => 'محفظة جلدية للرجال',
                'sku' => 'MSH-A-002',
                'description' => 'محفظة جلدية أنيقة وعملية للرجال، تحتوي على عدة جيوب.',
                'short_description' => 'محفظة جلدية للرجال',
                'price' => 45.00,
                'sale_price' => 40.00,
                'compare_price' => 50.00,
                'cost_price' => 25.00,
                'quantity' => 150,
                'min_quantity' => 15,
                'weight' => '0.2 kg',
                'dimensions' => '12 x 8 x 2 cm',
                'barcode' => '1234567890132',
                'model' => 'Wallet-2025',
                'is_active' => true,
                'is_featured' => false,
                'is_digital' => false,
                'requires_shipping' => true,
                'track_quantity' => true,
                'allow_backorder' => true,
                'meta_title' => 'محفظة جلدية للرجال - Magic Shoe',
                'meta_description' => 'محفظة جلدية أنيقة للرجال من Magic Shoe',
                'slug' => 'men-leather-wallet',
                'sort_order' => 10,
            ]
        ];
        
        foreach ($products as $product) {
            $createdProduct = Product::create($product);
            
            // ربط المنتجات بالفئات
            if (in_array($createdProduct->sku, ['MSH-M-001', 'MSH-M-002', 'MSH-M-003', 'MSH-F-001', 'MSH-F-002', 'MSH-F-003'])) {
                if ($shoesCategory) {
                    $createdProduct->categories()->attach($shoesCategory->id);
                }
            }
            
            if (in_array($createdProduct->sku, ['MSH-B-001', 'MSH-B-002'])) {
                if ($bagsCategory) {
                    $createdProduct->categories()->attach($bagsCategory->id);
                }
            }
            
            if (in_array($createdProduct->sku, ['MSH-A-001', 'MSH-A-002'])) {
                if ($accessoriesCategory) {
                    $createdProduct->categories()->attach($accessoriesCategory->id);
                }
            }
        }
        
        $this->command->info('✅ تم إنشاء المنتجات بنجاح');
    }
}
