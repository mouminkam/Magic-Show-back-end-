<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Material;
use App\Models\Color;
use App\Services\ProductService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShoeProductSeeder extends Seeder
{
    /** مقاسات الأحذية (رجالي/نسائي) */
    private const SHOE_SIZES_MEN = ['39', '40', '41', '42', '43', '44', '45'];
    private const SHOE_SIZES_WOMEN = ['35', '36', '37', '38', '39', '40', '41'];

    /** المواسم */
    private const SEASONS = [
        'en' => ['Spring', 'Summer', 'Autumn', 'Winter'],
        'ar' => ['ربيع', 'صيف', 'خريف', 'شتاء'],
    ];

    public function run(): void
    {
        $productService = app(ProductService::class);

        $this->command->info('🗑️ جاري مسح جميع المنتجات...');

        Product::query()->each(function (Product $product) use ($productService) {
            try {
                $productService->deleteProduct($product);
            } catch (\Exception $e) {
                \Log::warning("Could not delete product {$product->id}: " . $e->getMessage());
            }
        });

        Product::query()->delete();
        $this->command->info('✅ تم مسح المنتجات بنجاح');

        $this->call(CategorySeeder::class);
        if (\App\Models\Brand::count() === 0) $this->call(BrandSeeder::class);
        if (\App\Models\Material::count() === 0) $this->call(MaterialSeeder::class);
        if (\App\Models\Color::count() === 0) $this->call(ColorSeeder::class);

        $brands = $this->getBrands();
        $materials = $this->getMaterials();
        $colors = $this->getColors();

        $products = $this->getProductsData();

        $this->command->info('📦 جاري إنشاء 10 منتجات أحذية بالمعلومات الكاملة...');

        foreach ($products as $item) {
            $product = $this->createProduct($item, $brands, $materials, $colors);
            $this->attachCategories($product, $item['category_slugs'] ?? []);
            $this->attachColors($product, $item['color_names'] ?? [], $colors);
        }

        $this->command->info('✅ تم إنشاء 10 منتجات بأحجام وألوان ومواسم بنجاح');
    }

    private function getBrands(): array
    {
        $brands = [];
        foreach (['nike', 'adidas', 'puma', 'new-balance', 'converse'] as $slug) {
            $b = Brand::where('slug', $slug)->first();
            if ($b) $brands[$slug] = $b->id;
        }
        return $brands;
    }

    private function getMaterials(): array
    {
        $m = [];
        foreach (['leather-natural', 'leather-synthetic', 'canvas', 'mesh', 'cotton'] as $slug) {
            $mat = Material::where('slug', $slug)->first();
            if ($mat) $m[$slug] = $mat->id;
        }
        return $m;
    }

    private function getColors(): array
    {
        $c = [];
        foreach (['White', 'Black', 'Brown', 'Pink', 'Beige', 'Navy', 'Gray', 'Red', 'Blue', 'Cream', 'Burgundy', 'Gold'] as $name) {
            $col = Color::where('name_en', $name)->orWhere('name', $this->colorNameAr($name))->first();
            if ($col) $c[$name] = $col->id;
        }
        return $c;
    }

    private function colorNameAr(string $en): string
    {
        return match ($en) {
            'White' => 'أبيض', 'Black' => 'أسود', 'Brown' => 'بني', 'Pink' => 'وردي', 'Beige' => 'بيج',
            'Navy' => 'نبيتي', 'Gray' => 'رمادي', 'Red' => 'أحمر', 'Blue' => 'أزرق', 'Cream' => 'كريمي',
            'Burgundy' => 'خمري', 'Gold' => 'ذهبي', default => $en
        };
    }

    private function createProduct(array $item, array $brands, array $materials, array $colors): Product
    {
        $categorySlugs = $item['category_slugs'] ?? [];
        $colorNames = $item['color_names'] ?? [];
        $sizes = $item['sizes'] ?? self::SHOE_SIZES_MEN;
        $seasons = $item['seasons'] ?? self::SEASONS['en'];
        $brandSlug = $item['brand_slug'] ?? 'nike';
        $materialSlug = $item['material_slug'] ?? 'leather-natural';

        unset($item['category_slugs'], $item['color_names'], $item['brand_slug'], $item['material_slug']);

        $name = $item['name_en'] ?? $item['name_ar'];
        $price = (float) ($item['price'] ?? 100);
        $salePrice = isset($item['sale_price']) ? (float) $item['sale_price'] : null;
        $comparePrice = $item['compare_price'] ?? $price * 1.15;
        $costPrice = $item['cost_price'] ?? $price * 0.5;

        return Product::updateOrCreate(
            ['sku' => $item['sku']],
            [
                'name' => $name,
                'name_ar' => $item['name_ar'] ?? $name,
                'name_en' => $item['name_en'] ?? $name,
                'description' => $item['description_en'] ?? $item['description_ar'] ?? null,
                'description_ar' => $item['description_ar'] ?? null,
                'description_en' => $item['description_en'] ?? null,
                'short_description' => $item['short_description_en'] ?? $item['short_description_ar'] ?? null,
                'short_description_ar' => $item['short_description_ar'] ?? null,
                'short_description_en' => $item['short_description_en'] ?? null,
                'sku' => $item['sku'],
                'price' => $price,
                'sale_price' => $salePrice,
                'compare_price' => $comparePrice,
                'cost_price' => $costPrice,
                'quantity' => $item['quantity'] ?? 100,
                'min_quantity' => $item['min_quantity'] ?? 10,
                'weight' => $item['weight'] ?? '0.6 kg',
                'dimensions' => $item['dimensions'] ?? '30 x 25 x 12 cm',
                'barcode' => $item['barcode'] ?? '8' . str_pad(rand(0, 9999999999999), 13, '0', STR_PAD_LEFT),
                'model' => $item['model'] ?? '2025',
                'brand_id' => $brands[$brandSlug] ?? null,
                'material_id' => $materials[$materialSlug] ?? null,
                'sizes' => $sizes,
                'seasons' => $seasons,
                'meta_title' => ($item['meta_title_en'] ?? $item['meta_title_ar'] ?? $name) . ' - Magic Shoe',
                'meta_description' => $item['meta_description_en'] ?? $item['meta_description_ar'] ?? null,
                'slug' => $item['slug'] ?? Str::slug($name),
                'sort_order' => $item['sort_order'] ?? 0,
                'is_active' => true,
                'is_featured' => $item['is_featured'] ?? false,
                'requires_shipping' => true,
                'track_quantity' => true,
                'allow_backorder' => $item['allow_backorder'] ?? false,
                'published_at' => now(),
            ]
        );
    }

    private function attachCategories(Product $product, array $slugs): void
    {
        $ids = [];
        foreach ($slugs as $slug) {
            $cat = Category::where('slug', $slug)->first();
            if ($cat) $ids[] = $cat->id;
        }
        $shoes = Category::where('slug', 'shoes')->orWhere('name_ar', 'أحذية')->first();
        if (empty($ids) && $shoes) $ids[] = $shoes->id;
        if (!empty($ids)) $product->categories()->sync(array_unique($ids));
    }

    private function attachColors(Product $product, array $colorNames, array $colors): void
    {
        $data = [];
        foreach ($colorNames as $i => $name) {
            $id = $colors[$name] ?? null;
            if ($id) $data[$id] = ['is_primary' => $i === 0, 'sort_order' => $i];
        }
        if (!empty($data)) $product->colors()->sync($data);
    }

    private function getProductsData(): array
    {
        return [
            [
                'name_ar' => 'حذاء جلد طبيعي كلاسيكي للرجال',
                'name_en' => 'Classic Leather Men\'s Shoes',
                'description_ar' => 'حذاء جلد طبيعي عالي الجودة مصنوع يدوياً. مناسب للمناسبات الرسمية واليومية. نعل داخلي مريح وتصميم أنيق.',
                'description_en' => 'High-quality genuine leather shoes handcrafted. Suitable for formal and casual occasions. Comfortable insole and elegant design.',
                'short_description_ar' => 'حذاء جلد طبيعي كلاسيكي للرجال',
                'short_description_en' => 'Classic leather men\'s shoes',
                'sku' => 'MSH-M-001',
                'price' => 85,
                'sale_price' => 75,
                'quantity' => 150,
                'weight' => '0.8 kg',
                'dimensions' => '30 x 25 x 12 cm',
                'model' => 'Classic-2025',
                'barcode' => '6234567890123',
                'brand_slug' => 'nike',
                'material_slug' => 'leather-natural',
                'sizes' => self::SHOE_SIZES_MEN,
                'seasons' => ['Spring', 'Autumn', 'Winter'],
                'color_names' => ['Brown', 'Black'],
                'category_slugs' => ['shoes'],
                'is_featured' => true,
                'slug' => 'men-classic-leather-shoes',
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'حذاء رياضي للرجال - أبيض',
                'name_en' => 'Men\'s Sports Shoes - White',
                'description_ar' => 'حذاء رياضي مريح وأنيق للرجال، مصنوع من مواد عالية الجودة. مثالي للجري والرياضة.',
                'description_en' => 'Comfortable and stylish sports shoes for men, made from high-quality materials. Ideal for running and sports.',
                'short_description_ar' => 'حذاء رياضي للرجال - أبيض',
                'short_description_en' => 'Men\'s sports shoes - white',
                'sku' => 'MSH-M-002',
                'price' => 65,
                'sale_price' => null,
                'quantity' => 200,
                'weight' => '0.6 kg',
                'model' => 'Sport-2025',
                'brand_slug' => 'adidas',
                'material_slug' => 'mesh',
                'sizes' => self::SHOE_SIZES_MEN,
                'seasons' => ['Spring', 'Summer'],
                'color_names' => ['White', 'Black', 'Blue'],
                'category_slugs' => ['shoes', 'sneakers'],
                'slug' => 'men-white-sports-shoes',
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'حذاء بوت شتوي للرجال',
                'name_en' => 'Men\'s Winter Boot',
                'description_ar' => 'حذاء بوت شتوي دافئ ومقاوم للماء للرجال. مثالي للطقس البارد.',
                'description_en' => 'Warm and waterproof winter boots for men. Ideal for cold weather.',
                'short_description_ar' => 'حذاء بوت شتوي للرجال',
                'short_description_en' => 'Men\'s winter boot',
                'sku' => 'MSH-M-003',
                'price' => 95,
                'sale_price' => 85,
                'quantity' => 80,
                'weight' => '1.2 kg',
                'dimensions' => '35 x 28 x 16 cm',
                'model' => 'Winter-2025',
                'brand_slug' => 'puma',
                'material_slug' => 'leather-synthetic',
                'sizes' => self::SHOE_SIZES_MEN,
                'seasons' => ['Winter', 'Autumn'],
                'color_names' => ['Black', 'Brown'],
                'category_slugs' => ['shoes'],
                'is_featured' => true,
                'slug' => 'men-winter-boots',
                'sort_order' => 3,
            ],
            [
                'name_ar' => 'حذاء كعب عالي للنساء - أسود',
                'name_en' => 'Women\'s High Heels - Black',
                'description_ar' => 'حذاء كعب عالي أنيق للنساء، مثالي للمناسبات والاحتفالات. تصميم كلاسيكي وراقي.',
                'description_en' => 'Elegant high heels for women, ideal for occasions and celebrations. Classic and sophisticated design.',
                'short_description_ar' => 'حذاء كعب عالي للنساء - أسود',
                'short_description_en' => 'Women\'s high heels - black',
                'sku' => 'MSH-F-001',
                'price' => 75,
                'sale_price' => 65,
                'quantity' => 120,
                'weight' => '0.7 kg',
                'model' => 'Heels-2025',
                'brand_slug' => 'nike',
                'material_slug' => 'leather-natural',
                'sizes' => self::SHOE_SIZES_WOMEN,
                'seasons' => ['Spring', 'Summer', 'Autumn'],
                'color_names' => ['Black', 'Red', 'Burgundy'],
                'category_slugs' => ['shoes', 'heels'],
                'is_featured' => true,
                'slug' => 'women-black-heels',
                'sort_order' => 4,
            ],
            [
                'name_ar' => 'حذاء مسطح للنساء - بيج',
                'name_en' => 'Women\'s Flat Shoes - Beige',
                'description_ar' => 'حذاء مسطح مريح وأنيق للنساء، مناسب للاستخدام اليومي والعمل.',
                'description_en' => 'Comfortable and stylish flat shoes for women, suitable for daily use and work.',
                'short_description_ar' => 'حذاء مسطح للنساء - بيج',
                'short_description_en' => 'Women\'s flat shoes - beige',
                'sku' => 'MSH-F-002',
                'price' => 55,
                'sale_price' => null,
                'quantity' => 180,
                'weight' => '0.5 kg',
                'model' => 'Flat-2025',
                'brand_slug' => 'converse',
                'material_slug' => 'leather-natural',
                'sizes' => self::SHOE_SIZES_WOMEN,
                'seasons' => ['Spring', 'Summer', 'Autumn'],
                'color_names' => ['Beige', 'Cream', 'Black'],
                'category_slugs' => ['shoes'],
                'slug' => 'women-beige-flats',
                'sort_order' => 5,
            ],
            [
                'name_ar' => 'حذاء رياضي للنساء - وردي',
                'name_en' => 'Women\'s Sports Shoes - Pink',
                'description_ar' => 'حذاء رياضي أنيق ومريح للنساء، مثالي للرياضة والمشي والاستخدام اليومي.',
                'description_en' => 'Elegant and comfortable sports shoes for women, ideal for sports, walking and daily use.',
                'short_description_ar' => 'حذاء رياضي للنساء - وردي',
                'short_description_en' => 'Women\'s sports shoes - pink',
                'sku' => 'MSH-F-003',
                'price' => 70,
                'sale_price' => 60,
                'quantity' => 160,
                'model' => 'Sport-F-2025',
                'brand_slug' => 'adidas',
                'material_slug' => 'mesh',
                'sizes' => self::SHOE_SIZES_WOMEN,
                'seasons' => ['Spring', 'Summer'],
                'color_names' => ['Pink', 'White'],
                'category_slugs' => ['shoes', 'sneakers'],
                'is_featured' => true,
                'slug' => 'women-pink-sports-shoes',
                'sort_order' => 6,
            ],
            [
                'name_ar' => 'سنيكرز كانفاس كلاسيكي - أبيض وأسود',
                'name_en' => 'Classic Canvas Sneakers - White & Black',
                'description_ar' => 'سنيكرز كانفاس كلاسيكي بتصميم عصري. مريح للاستخدام اليومي ولجميع الفصول.',
                'description_en' => 'Classic canvas sneakers with modern design. Comfortable for daily use and all seasons.',
                'short_description_ar' => 'سنيكرز كانفاس كلاسيكي',
                'short_description_en' => 'Classic canvas sneakers',
                'sku' => 'MSH-M-004',
                'price' => 58,
                'sale_price' => 49,
                'quantity' => 220,
                'brand_slug' => 'converse',
                'material_slug' => 'canvas',
                'sizes' => array_merge(self::SHOE_SIZES_MEN, self::SHOE_SIZES_WOMEN),
                'seasons' => self::SEASONS['en'],
                'color_names' => ['White', 'Black', 'Navy'],
                'category_slugs' => ['shoes', 'sneakers'],
                'slug' => 'classic-canvas-sneakers',
                'sort_order' => 7,
            ],
            [
                'name_ar' => 'حذاء كاجوال للرجال - بني',
                'name_en' => 'Men\'s Casual Shoes - Brown',
                'description_ar' => 'حذاء كاجوال أنيق للرجال مناسب للمكتب والتنزه. جلد طبيعي فاخر.',
                'description_en' => 'Elegant casual shoes for men suitable for office and stroll. Premium genuine leather.',
                'short_description_ar' => 'حذاء كاجوال للرجال',
                'short_description_en' => 'Men\'s casual shoes',
                'sku' => 'MSH-M-005',
                'price' => 72,
                'quantity' => 95,
                'brand_slug' => 'new-balance',
                'material_slug' => 'leather-natural',
                'sizes' => self::SHOE_SIZES_MEN,
                'seasons' => ['Spring', 'Autumn', 'Winter'],
                'color_names' => ['Brown', 'Black'],
                'category_slugs' => ['shoes'],
                'slug' => 'men-casual-brown-shoes',
                'sort_order' => 8,
            ],
            [
                'name_ar' => 'حذاء صيفي نسائي - ذهبي',
                'name_en' => 'Women\'s Summer Sandals - Gold',
                'description_ar' => 'صنادل صيفية أنيقة باللون الذهبي. مثالية للمناسبات والإجازات.',
                'description_en' => 'Elegant summer sandals in gold. Ideal for occasions and holidays.',
                'short_description_ar' => 'صنادل صيفية نسائية',
                'short_description_en' => 'Women\'s summer sandals',
                'sku' => 'MSH-F-004',
                'price' => 45,
                'sale_price' => 38,
                'quantity' => 140,
                'brand_slug' => 'puma',
                'material_slug' => 'leather-synthetic',
                'sizes' => self::SHOE_SIZES_WOMEN,
                'seasons' => ['Summer'],
                'color_names' => ['Gold', 'Black', 'Cream'],
                'category_slugs' => ['shoes'],
                'slug' => 'women-summer-sandals-gold',
                'sort_order' => 9,
            ],
            [
                'name_ar' => 'حذاء رياضي متعدد الاستخدامات - رمادي',
                'name_en' => 'All-Purpose Athletic Shoes - Gray',
                'description_ar' => 'حذاء رياضي متعدد الاستخدامات للجري والمشي واللياقة. تصميم عصري وخفيف.',
                'description_en' => 'All-purpose athletic shoes for running, walking and fitness. Modern and lightweight design.',
                'short_description_ar' => 'حذاء رياضي متعدد الاستخدامات',
                'short_description_en' => 'All-purpose athletic shoes',
                'sku' => 'MSH-M-006',
                'price' => 88,
                'sale_price' => 78,
                'quantity' => 110,
                'brand_slug' => 'adidas',
                'material_slug' => 'mesh',
                'sizes' => self::SHOE_SIZES_MEN,
                'seasons' => ['Spring', 'Summer', 'Autumn'],
                'color_names' => ['Gray', 'Black', 'White'],
                'category_slugs' => ['shoes', 'sneakers'],
                'is_featured' => true,
                'slug' => 'all-purpose-athletic-shoes',
                'sort_order' => 10,
            ],
        ];
    }
}
