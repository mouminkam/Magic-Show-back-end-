<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\LocaleHelper;
use App\Http\Controllers\Controller;
use App\Support\ProductImageUrlBuilder;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\HomePageSection;
use App\Models\Product;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    private function disabledPayload(): array
    {
        return ['enabled' => false, 'data' => null];
    }

    private function enabledPayload($data): array
    {
        return ['enabled' => true, 'data' => $data];
    }

    /**
     * Get hero section data from DB.
     */
    public function hero()
    {
        $locale = app()->getLocale();
        $cacheKey = "home_hero_{$locale}";

        $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
            $section = HomePageSection::getByKey('hero');
            if (!$section || !$section->is_active) return $this->disabledPayload();

            $settings = $section->settings ?? [];
            $heroImagePath = $settings['hero_image'] ?? '';
            $heroImageUrl = $this->resolveHeroImageUrl($heroImagePath);
            $ts = $section->updated_at?->timestamp ?? time();
            $heroImageUrl .= (str_contains($heroImageUrl, '?') ? '&' : '?') . 'v=' . $ts;
            return $this->enabledPayload([
                'badge' => $settings['badge'] ?? 'EST. 2024',
                'title' => LocaleHelper::transAttr($section, 'title') ?? 'ELEGANT',
                'subtitle' => LocaleHelper::transAttr($section, 'subtitle') ?? 'JEWELRY',
                'description' => LocaleHelper::transAttr($section, 'description') ?? 'Crafted with precision. Timeless designs.',
                'ctaPrimary' => $settings['cta_primary'] ?? 'Shop Now',
                'ctaPrimaryLink' => $settings['cta_primary_link'] ?? $section->button_link ?? '/shop',
                'ctaSecondary' => $settings['cta_secondary'] ?? 'View Collection',
                'ctaSecondaryLink' => $settings['cta_secondary_link'] ?? $section->button_link ?? '/shop',
                'heroImage' => $heroImageUrl,
                'image' => $heroImageUrl,
                'stats' => $settings['stats'] ?? [
                    ['value' => '100+', 'label' => 'Designs'],
                    ['value' => '100%', 'label' => 'Quality Guaranteed'],
                ],
                'socialLinks' => $settings['social_links'] ?? [],
            ]);
        });

        return ApiResponseHelper::success($data);
    }

    protected function resolveHeroImageUrl(?string $path): string
    {
        if (empty($path)) {
            return asset('images/img27.png');
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, '/')) {
            return asset(ltrim($path, '/'));
        }
        return asset('storage/' . $path);
    }

    protected function defaultHero(): array
    {
        return [
            'badge' => 'EST. 2024',
            'title' => 'ELEGANT',
            'subtitle' => 'JEWELRY',
            'description' => 'Crafted with precision. Timeless designs.',
            'ctaPrimary' => 'Shop Now',
            'ctaPrimaryLink' => '/shop',
            'ctaSecondary' => 'View Collection',
            'ctaSecondaryLink' => '/shop',
            'heroImage' => asset('images/img27.png'),
            'image' => asset('images/img27.png'),
            'stats' => [
                ['value' => '100+', 'label' => 'Designs'],
                ['value' => '100%', 'label' => 'Quality Guaranteed'],
            ],
            'socialLinks' => [],
        ];
    }

    /**
     * Get about us section data (for home page about block if used).
     */
    public function aboutUs()
    {
        $locale = app()->getLocale();
        $cacheKey = "home_about_us_{$locale}";

        $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
            $ts = time();
            return [
                'title' => __('home.about.title'),
                'backgroundImage' => asset('images/about-banner.jpg') . '?v=' . $ts,
                'leftBadge' => __('home.about.left_badge'),
                'rightBadge' => __('home.about.right_badge'),
            ];
        });

        return ApiResponseHelper::success($data);
    }

    /**
     * Get featured categories.
     */
    public function featuredCategories()
    {
        $locale = app()->getLocale();
        $cacheKey = "home_featured_categories_{$locale}";

        $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
            $section = HomePageSection::getByKey('featured_categories');
            if (!$section || !$section->is_active) return $this->disabledPayload();

            $limit = $section && $section->is_active ? (int) $section->limit : 5;
            $settings = $section?->settings ?? [];
            $categoryIds = $settings['category_ids'] ?? [];

            $query = Category::where('is_active', true);
            if (!empty($categoryIds)) {
                $categoryIds = array_map('intval', $categoryIds);
                $query->whereIn('id', $categoryIds);
            } else {
                // Stable fallback: show active categories by sort_order so limit changes always reflect
                $query->orderBy('sort_order')->orderBy('id');
            }
            $categories = $query->limit($limit)->get();
            if (!empty($categoryIds)) {
                $order = array_flip($categoryIds);
                $categories = $categories->sortBy(fn($c) => $order[$c->id] ?? 999)->values();
            }
            $ts = $section?->updated_at?->timestamp ?? time();
            $overrides = $settings['featured_categories_overrides'] ?? [];

            $categoriesArray = $categories->map(function ($cat) use ($ts, $overrides) {
                $cidKey = (string) (int) $cat->id;
                $ov = is_array($overrides) ? ($overrides[$cidKey] ?? []) : [];

                $imgSource = $ov['image'] ?? null;
                if (is_string($imgSource) && $imgSource !== '') {
                    if (str_starts_with($imgSource, 'http://') || str_starts_with($imgSource, 'https://')) {
                        $img = $imgSource;
                    } elseif (str_starts_with($imgSource, '/')) {
                        $img = asset(ltrim($imgSource, '/'));
                    } else {
                        $img = asset('storage/' . $imgSource);
                    }
                } else {
                    $img = $cat->image ? asset('storage/' . $cat->image) : asset('images/img25.png');
                }

                $img .= (str_contains($img, '?') ? '&' : '?') . 'v=' . $ts;

                $link = $ov['link'] ?? null;
                return [
                    'name' => LocaleHelper::transAttr($cat, 'name') ?? $cat->name,
                    'image' => $img,
                    'slug' => $cat->slug,
                    'link' => is_string($link) && $link !== '' ? $link : null,
                ];
            })->toArray();

            return $this->enabledPayload([
                'title'      => $section ? (LocaleHelper::transAttr($section, 'title') ?: null) : null,
                'subtitle'   => $section ? (LocaleHelper::transAttr($section, 'subtitle') ?: null) : null,
                'categories' => $categoriesArray,
            ]);
        });

        return ApiResponseHelper::success($data);
    }

    /**
     * Get new arrivals: header from DB + products.
     */
    public function newArrivals()
    {
        $locale = app()->getLocale();
        $cacheKey = "home_new_arrivals_{$locale}";

        $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
            $section = HomePageSection::getByKey('new_arrivals');
            if (!$section || !$section->is_active) return $this->disabledPayload();

            $limit = $section && $section->is_active ? (int) $section->limit : 8;
            $settings = $section?->settings ?? [];
            $productIds = $settings['product_ids'] ?? [];

            $header = [
                'title' => $section && $section->is_active ? (LocaleHelper::transAttr($section, 'title') ?? 'New Arrivals') : 'New Arrivals',
                'subtitle' => $section ? LocaleHelper::transAttr($section, 'subtitle') : 'Latest Collection',
                'description' => $section ? LocaleHelper::transAttr($section, 'description') : 'Be the first to discover our newest arrivals',
                'buttonText' => $section ? LocaleHelper::transAttr($section, 'button_text') : 'View All',
                'buttonLink' => $section?->button_link ?? '/shop?sort=newest',
            ];

            $query = Product::with(['categories', 'productImages'])->where('is_active', true);
            if (!empty($productIds)) {
                $productIds = array_map('intval', $productIds);
                $products = $query->whereIn('id', $productIds)->limit($limit)->get();
                $order = array_flip($productIds);
                $products = $products->sortBy(fn($p) => $order[$p->id] ?? 999)->values();
            } else {
                $products = $query->orderBy('created_at', 'desc')->limit($limit)->get();
            }
            $products = $products->map(fn($p) => $this->mapProductForHome($p));

            return $this->enabledPayload([
                'header' => $header,
                'products' => $products->toArray(),
            ]);
        });

        return ApiResponseHelper::success($data);
    }

    /**
     * Get best sellers: header from DB + products.
     */
    public function bestSellers()
    {
        $locale = app()->getLocale();
        $cacheKey = "home_best_sellers_{$locale}";

        $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
            $section = HomePageSection::getByKey('best_sellers');
            if (!$section || !$section->is_active) return $this->disabledPayload();

            $limit = $section && $section->is_active ? (int) $section->limit : 8;
            $settings = $section?->settings ?? [];
            $productIds = $settings['product_ids'] ?? [];

            $header = [
                'title' => $section && $section->is_active ? (LocaleHelper::transAttr($section, 'title') ?? 'Best Sellers') : 'Best Sellers',
                'subtitle' => $section ? LocaleHelper::transAttr($section, 'subtitle') : 'Most Popular',
                'description' => $section ? LocaleHelper::transAttr($section, 'description') : "Our customers' favorite picks",
                'buttonText' => $section ? LocaleHelper::transAttr($section, 'button_text') : 'View All',
                'buttonLink' => $section?->button_link ?? '/shop?sort=bestseller',
            ];

            $query = Product::with(['categories', 'productImages'])->where('is_active', true);
            if (!empty($productIds)) {
                $productIds = array_map('intval', $productIds);
                $products = $query->whereIn('id', $productIds)->limit($limit)->get();
                $order = array_flip($productIds);
                $products = $products->sortBy(fn($p) => $order[$p->id] ?? 999)->values();
            } else {
                $products = $query->orderBy('sales_count', 'desc')->limit($limit)->get();
            }
            $products = $products->map(fn($p) => $this->mapProductForHome($p));

            return $this->enabledPayload([
                'header' => $header,
                'products' => $products->toArray(),
            ]);
        });

        return ApiResponseHelper::success($data);
    }

    /**
     * Get featured products: headers from DB + featured + onSale products.
     */
    public function featuredProducts()
    {
        $locale = app()->getLocale();
        $cacheKey = "home_featured_products_{$locale}";

        $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
            $section = HomePageSection::getByKey('featured_products');
            if (!$section || !$section->is_active) return $this->disabledPayload();

            $limit = $section && $section->is_active ? (int) $section->limit : 6;
            $settings = $section?->settings ?? [];
            $onSaleLimit = (int) ($settings['on_sale_limit'] ?? 6);

            $featuredHeader = [
                'title' => $section && $section->is_active ? (LocaleHelper::transAttr($section, 'title') ?? 'Featured Products') : 'Featured Products',
                'subtitle' => $section ? LocaleHelper::transAttr($section, 'subtitle') : 'MAGIC SHOE STILETTO',
                'description' => $section ? LocaleHelper::transAttr($section, 'description') : 'Browse our featured products',
                'buttonText' => $section ? LocaleHelper::transAttr($section, 'button_text') : 'See more',
                'buttonLink' => $section?->button_link ?? '/shop?featured=true',
            ];

            $onSaleHeader = [
                'title' => $settings['on_sale_title_en'] ?? $settings['on_sale_title_ar'] ?? 'ON SALE',
                'subtitle' => $settings['on_sale_subtitle_en'] ?? $settings['on_sale_subtitle_ar'] ?? 'SAVE UP TO 30%',
                'description' => $settings['on_sale_description_en'] ?? $settings['on_sale_description_ar'] ?? '',
                'buttonText' => $settings['on_sale_button_text_en'] ?? $settings['on_sale_button_text_ar'] ?? 'See more',
                'buttonLink' => $settings['on_sale_button_link'] ?? '/shop?onSale=true',
            ];

            $featuredIds = $settings['featured_product_ids'] ?? [];
            $onSaleIds = $settings['on_sale_product_ids'] ?? [];

            $featuredQuery = Product::with(['categories', 'productImages'])->where('is_active', true);
            if (!empty($featuredIds)) {
                $featuredIds = array_map('intval', $featuredIds);
                $featured = $featuredQuery->whereIn('id', $featuredIds)->limit($limit)->get();
                $order = array_flip($featuredIds);
                $featured = $featured->sortBy(fn($p) => $order[$p->id] ?? 999)->values();
            } else {
                $featured = $featuredQuery->where('is_featured', true)->limit($limit)->get();
            }
            $featured = $featured->map(fn($p) => $this->mapProductForFeatured($p));

            $onSaleQuery = Product::with(['categories', 'productImages'])->where('is_active', true);
            if (!empty($onSaleIds)) {
                $onSaleIds = array_map('intval', $onSaleIds);
                $onSale = $onSaleQuery->whereIn('id', $onSaleIds)->limit($onSaleLimit)->get();
                $order = array_flip($onSaleIds);
                $onSale = $onSale->sortBy(fn($p) => $order[$p->id] ?? 999)->values();
            } else {
                $onSale = $onSaleQuery->whereNotNull('sale_price')->limit($onSaleLimit)->get();
            }
            $onSale = $onSale->map(fn($p) => $this->mapProductForOnSale($p));

            return $this->enabledPayload([
                'featuredHeader' => $featuredHeader,
                'onSaleHeader' => $onSaleHeader,
                'featuredProducts' => $featured->toArray(),
                'onSaleProducts' => $onSale->toArray(),
            ]);
        });

        return ApiResponseHelper::success($data);
    }

    /**
     * Get blog section data from DB (latest_blog section).
     */
    public function blogSection()
    {
        $locale = app()->getLocale();
        $cacheKey = "home_blog_section_{$locale}";

        $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
            $section = HomePageSection::getByKey('latest_blog');
            $ts = $section?->updated_at?->timestamp ?? time();
            return [
                'title' => $section ? LocaleHelper::transAttr($section, 'title') : 'Latest Blog',
                'subtitle' => $section ? LocaleHelper::transAttr($section, 'subtitle') : null,
                'description' => $section ? LocaleHelper::transAttr($section, 'description') : 'There are many variations of passages of Lorem Ipsum available.',
                'buttonText' => $section ? LocaleHelper::transAttr($section, 'button_text') : 'View All',
                'buttonLink' => $section?->button_link ?? '/blog',
                'backgroundImage' => asset('images/blog-banner.jpg') . '?v=' . $ts,
            ];
        });

        return ApiResponseHelper::success($data);
    }

    /**
     * Get latest blog posts.
     */
    public function latestBlog()
    {
        $locale = app()->getLocale();
        $cacheKey = "home_latest_blog_{$locale}";

        $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
            $section = HomePageSection::getByKey('latest_blog');
            if (!$section || !$section->is_active) return $this->disabledPayload();

            $limit = $section && $section->is_active ? (int) $section->limit : 3;

            $posts = BlogPost::with('author')
                ->where('status', 'published')
                ->latest('published_at')
                ->limit($limit)
                ->get()
                ->map(function ($post) {
                    $img = $post->featured_image_url ?? '';
                    $ts = $post->updated_at?->timestamp ?? time();
                    if ($img) {
                        $img .= (str_contains($img, '?') ? '&' : '?') . 'v=' . $ts;
                    }
                    return [
                        'id' => $post->id,
                        'title' => LocaleHelper::transAttr($post, 'title') ?? $post->title,
                        'excerpt' => LocaleHelper::transAttr($post, 'excerpt') ?? $post->excerpt,
                        'image' => $img,
                        'author' => $post->author?->name ?? 'Admin',
                        'date' => $post->published_at?->format('Y-m-d'),
                        'slug' => $post->slug,
                    ];
                });

            return $this->enabledPayload([
                'title' => $section ? LocaleHelper::transAttr($section, 'title') : __('home.blog.title'),
                'description' => $section ? LocaleHelper::transAttr($section, 'description') : __('home.blog.description'),
                'posts' => $posts->toArray(),
            ]);
        });

        return ApiResponseHelper::success($data);
    }

    /**
     * Get why choose us from DB.
     */
    public function whyChooseUs()
    {
        $locale = app()->getLocale();
        $cacheKey = "home_why_choose_us_{$locale}";

        $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () use ($locale) {
            $section = HomePageSection::getByKey('why_choose_us');
            if (!$section || !$section->is_active) return $this->disabledPayload();

            $settings = $section?->settings ?? [];
            $features = $settings['features'] ?? [];
            if (! is_array($features)) {
                $features = [];
            }
            $features = array_values(array_filter($features, 'is_array'));
            if (count($features) === 0) {
                return $this->disabledPayload();
            }

            $features = array_map(function ($f) use ($locale) {
                $title = ($locale === 'ar' && isset($f['title_ar'])) ? $f['title_ar'] : ($f['title_en'] ?? $f['title'] ?? '');
                $desc = ($locale === 'ar' && isset($f['description_ar'])) ? $f['description_ar'] : ($f['description_en'] ?? $f['description'] ?? '');
                return ['icon' => $f['icon'] ?? 'Package', 'title' => $title, 'description' => $desc];
            }, $features);

            return $this->enabledPayload([
                'title' => $section ? LocaleHelper::transAttr($section, 'title') : 'Why Choose Us',
                'subtitle' => $section ? LocaleHelper::transAttr($section, 'subtitle') : '',
                'features' => $features,
            ]);
        });

        return ApiResponseHelper::success($data);
    }

    /**
     * Get customer reviews (from Testimonials).
     */
    public function customerReviews()
    {
        $locale = app()->getLocale();
        $cacheKey = "home_customer_reviews_{$locale}";

        $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
            $section = HomePageSection::getByKey('customer_reviews');
            if (!$section || !$section->is_active) return $this->disabledPayload();

            $limit = $section && $section->is_active ? (int) $section->limit : 6;

            $reviews = Testimonial::featured()
                ->ordered()
                ->limit($limit)
                ->get()
                ->map(function ($t) {
                    $img = $t->customer_image_url ?? '';
                    $ts = $t->updated_at?->timestamp ?? time();
                    if ($img) {
                        $img .= (str_contains($img, '?') ? '&' : '?') . 'v=' . $ts;
                    }
                    return [
                        'id' => $t->id,
                        'name' => LocaleHelper::transAttr($t, 'customer_name') ?? $t->customer_name,
                        'text' => LocaleHelper::transAttr($t, 'text') ?? $t->text,
                        'comment' => LocaleHelper::transAttr($t, 'text') ?? $t->text,
                        'image' => $img ?: null,
                        'productImage' => $img ?: null,
                        'rating' => $t->rating ?? 5,
                        'date' => $t->created_at?->diffForHumans(),
                    ];
                });

            if ($reviews->isEmpty()) {
                return $this->disabledPayload();
            }

            return $this->enabledPayload($reviews->toArray());
        });

        return ApiResponseHelper::success($data);
    }

    /**
     * Get newsletter section.
     */
    public function newsletter()
    {
        $locale = app()->getLocale();
        $cacheKey = "home_newsletter_{$locale}";

        $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
            $section = HomePageSection::getByKey('newsletter');
            if (!$section || !$section->is_active) return $this->disabledPayload();

            return $this->enabledPayload([
                'title' => $section ? LocaleHelper::transAttr($section, 'title') : 'Newsletter',
                'subtitle' => $section ? LocaleHelper::transAttr($section, 'subtitle') : 'Subscribe for offers',
                'buttonText' => $section ? LocaleHelper::transAttr($section, 'button_text') : 'Subscribe',
            ]);
        });

        return ApiResponseHelper::success($data);
    }

    protected function mapProductForHome(Product $product): array
    {
        $price = (float) ($product->sale_price ?? $product->price);
        $originalPrice = $product->sale_price ? (float) $product->price : null;
        $categoryName = $product->categories->first();
        $categoryName = $categoryName ? (LocaleHelper::transAttr($categoryName, 'name') ?? $categoryName->name) : '';

        $imageUrls = ProductImageUrlBuilder::build($product);

        return [
            'id' => $product->id,
            'name' => LocaleHelper::transAttr($product, 'name') ?? $product->name,
            'image' => $imageUrls['thumb'] ?? ($product->primary_image_thumb_url ?? $product->primary_image_url),
            'image_urls' => $imageUrls,
            'price' => $price,
            'originalPrice' => $originalPrice,
            'category' => $categoryName,
        ];
    }

    protected function mapProductForFeatured(Product $product): array
    {
        $price = (float) ($product->sale_price ?? $product->price);
        $originalPrice = $product->sale_price ? (float) $product->price : null;
        $imageUrls = ProductImageUrlBuilder::build($product);
        $img = $imageUrls['thumb'] ?? ($product->primary_image_thumb_url ?? $product->primary_image_url ?? asset('images/img25.png'));

        return [
            'id' => $product->id,
            'image' => $img,
            'image_urls' => $imageUrls,
            'alt' => LocaleHelper::transAttr($product, 'name') ?? $product->name,
            'name' => LocaleHelper::transAttr($product, 'name') ?? $product->name,
            'originalPrice' => $originalPrice ? '$' . number_format($originalPrice, 2) : null,
            'discountedPrice' => '$' . number_format($price, 2),
            'width' => 600,
            'height' => 800,
        ];
    }

    protected function mapProductForOnSale(Product $product): array
    {
        $price = (float) ($product->sale_price ?? $product->price);
        $originalPrice = (float) $product->price;
        $imageUrls = ProductImageUrlBuilder::build($product);
        $img = $imageUrls['thumb'] ?? ($product->primary_image_thumb_url ?? $product->primary_image_url ?? asset('images/img25.png'));

        return [
            'id' => $product->id,
            'image' => $img,
            'image_urls' => $imageUrls,
            'alt' => LocaleHelper::transAttr($product, 'name') ?? $product->name,
            'name' => LocaleHelper::transAttr($product, 'name') ?? $product->name,
            'originalPrice' => '$' . number_format($originalPrice, 2),
            'discountedPrice' => '$' . number_format($price, 2),
            'width' => 600,
            'height' => 800,
        ];
    }
}
