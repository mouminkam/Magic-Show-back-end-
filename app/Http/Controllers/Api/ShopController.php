<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponseHelper;
use App\Helpers\LocaleHelper;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ShopPageSetting;
use App\Models\Size;
use App\Models\Season;
use App\Support\ProductImageUrlBuilder;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    /**
     * Query parameters this endpoint honours; used to build a bounded cache key.
     */
    private const CACHEABLE_FILTERS = [
        'category', 'minPrice', 'maxPrice', 'size', 'color', 'season', 'page', 'limit',
    ];

    /** Upper bound for the client-supplied `limit` query parameter. */
    private const MAX_LIMIT = 60;

    /**
     * Get shop banner data.
     */
    public function banner()
    {
        try {
            $locale = app()->getLocale();
            $cacheKey = "shop_banner_{$locale}";

            $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
                $setting = ShopPageSetting::get();
                $imgUrl = $setting->hero_background_image_url ?? asset('images/shop-banner.jpg');
                $ts = $setting->updated_at?->timestamp ?? time();
                $imgUrl .= (str_contains($imgUrl, '?') ? '&' : '?') . 'v=' . $ts;
                return [
                    'title' => LocaleHelper::transAttr($setting, 'hero_title') ?? __('shop.banner.title'),
                    'subtitle' => LocaleHelper::transAttr($setting, 'hero_subtitle') ?? __('shop.banner.subtitle'),
                    'backgroundImage' => $imgUrl,
                    'leftBadge' => LocaleHelper::transAttr($setting, 'hero_left_badge'),
                    'rightBadge' => LocaleHelper::transAttr($setting, 'hero_right_badge'),
                ];
            });

            return ApiResponseHelper::success($data);

        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'SHOP_BANNER_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Get shop products with advanced filters.
     * Cached for 10 minutes per unique filter combination.
     * Uses ksort to fix cache key fragmentation.
     */
    public function products(Request $request)
    {
        try {
            $version = CacheService::getVersion('products');
            // Bounded cache key (was md5 of $request->all(), i.e. attacker-controlled).
            $paramsHash = CacheService::requestFingerprint($request, self::CACHEABLE_FILTERS);
            $locale = app()->getLocale();
            $cacheKey = "shop_products_v{$version}_{$paramsHash}_{$locale}";

            return Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () use ($request) {
                return $this->fetchProducts($request);
            });
        } catch (\Exception $e) {
            Log::error('ShopController.products error', [
                'message' => $e->getMessage(),
                'exception' => $e,
                'params' => $request->all(),
            ]);
            return ApiResponseHelper::error(
                'SHOP_PRODUCTS_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Fetch products (used by cache).
     */
    private function fetchProducts(Request $request)
    {
        $query = $this->buildFilteredProductsQuery($request)
            ->with(['categories', 'brand', 'productImages', 'colors', 'sizes', 'seasons']);
        
        // Count AFTER filters (CRITICAL)
        $totalItems = $query->count();
        
        // Pagination.
        // Previously `page`/`limit` were taken raw from the query string:
        //   ?limit=0        -> division by zero in ceil($totalItems / $limit)
        //   ?limit=999999   -> entire catalogue returned in one response
        //   ?page=-5        -> negative OFFSET, SQL error
        $page = max(1, (int) $request->get('page', 1) ?: 1);
        $limit = max(1, min((int) $request->get('limit', 6) ?: 6, self::MAX_LIMIT));
        $totalPages = (int) ceil($totalItems / $limit);

        $locale = app()->getLocale();
        $colorLabelFn = fn($c) => $locale === 'ar' ? ($c->name ?? $c->name_en) : ($c->name_en ?? $c->name);
        $sizeLabelFn = fn($s) => $s->name;
        $products = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function($product) use ($colorLabelFn, $sizeLabelFn) {
                $categoryNames = $product->categories->map(fn($c) => LocaleHelper::transAttr($c, 'name'))->filter()->implode(', ');
                $imageUrls = ProductImageUrlBuilder::build($product);

                // Pivot relation vs legacy JSON column: use getRelation() for eager-loaded pivot,
                // fall back to JSON column for products not yet migrated.
                $sizesRelation = $product->getRelation('sizes');
                $seasonsRelation = $product->getRelation('seasons');

                if ($sizesRelation instanceof \Illuminate\Database\Eloquent\Collection && $sizesRelation->isNotEmpty()) {
                    $sizesOutput = $sizesRelation->sortBy([['sort_order', 'asc'], ['id', 'asc']])->map($sizeLabelFn)->values();
                } else {
                    $raw = $product->getRawOriginal('sizes');
                    $decoded = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
                    $sizesOutput = collect($decoded)->values();
                }

                if ($seasonsRelation instanceof \Illuminate\Database\Eloquent\Collection && $seasonsRelation->isNotEmpty()) {
                    $seasonsDetailed = $seasonsRelation
                        ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
                        ->map(fn($s) => ['id' => $s->id, 'value' => $s->value, 'label' => $s->translated_name])
                        ->values();
                    $seasonsOutput = $seasonsDetailed->pluck('value')->values();
                } else {
                    $raw = $product->getRawOriginal('seasons');
                    $decoded = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
                    $seasonsOutput = collect($decoded)->values();
                    $seasonsDetailed = $seasonsOutput->map(fn($v) => ['value' => $v, 'label' => $v])->values();
                }

                return [
                    'id' => $product->id,
                    'name' => LocaleHelper::transAttr($product, 'name') ?? $product->name,
                    'category' => $categoryNames ?: $product->categories->pluck('name')->implode(', '),
                    'price' => $product->sale_price ?? $product->price,
                    'originalPrice' => $product->sale_price ? $product->price : null,
                    'discount' => $product->discount_percentage,
                    'image' => $imageUrls['thumb'] ?? ($product->primary_image_thumb_url ?? $product->primary_image_url),
                    'image_urls' => $imageUrls,
                    'sizes' => $sizesOutput,
                    'colors' => $product->colors->map($colorLabelFn)->values(),
                    'colors_detailed' => $product->colors->map(function ($c) use ($colorLabelFn) {
                        return [
                            'id' => $c->id,
                            'name' => $colorLabelFn($c),
                            'color_code' => $c->color_code ?? $c->hex_code,
                            'is_primary' => (bool) ($c->pivot->is_primary ?? false),
                            'sort_order' => (int) ($c->pivot->sort_order ?? 0),
                        ];
                    })->values(),
                    'seasons' => $seasonsOutput,
                    'seasons_detailed' => $seasonsDetailed,
                    'slug' => $product->slug
                ];
            });
        
        return ApiResponseHelper::success(
            $products,
            null,
            [
                'pagination' => [
                    'currentPage' => (int)$page,
                    'limit' => (int)$limit,
                    'totalItems' => $totalItems,
                    'totalPages' => $totalPages,
                    'hasNext' => $page < $totalPages,
                    'hasPrev' => $page > 1
                ]
            ]
        );
    }

    /**
     * Get shop categories.
     * Cached for 10 minutes.
     */
    public function categories()
    {
        try {
            $locale = app()->getLocale();
            $cacheKey = "shop_categories_{$locale}";

            $categories = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
                return Category::where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn($c) => LocaleHelper::transAttr($c, 'name') ?? $c->name)
                    ->prepend('All')
                    ->values();
            });

            return ApiResponseHelper::success($categories);
            
        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'SHOP_CATEGORIES_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Get shop filters (CRITICAL: Dynamic price range).
     * Returns all available filter options from active products that have
     * at least one linked attribute via pivot tables.
     */
    public function filters(Request $request)
    {
        try {
            $locale = app()->getLocale();
            $version = CacheService::getVersion('products');
            $cacheKey = "shop_filters_v{$version}_{$locale}";

            $data = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () use ($locale) {
                $activeProductIds = Product::where('is_active', true)->pluck('id');

                $priceRange = Product::where('is_active', true)
                    ->selectRaw('MIN(price) as min, MAX(price) as max')
                    ->first();

                $sizes = Size::query()
                    ->where('sizes.is_active', true)
                    ->whereHas('products', fn($q) => $q->whereIn('products.id', $activeProductIds))
                    ->orderBy('sizes.sort_order')
                    ->orderBy('sizes.name')
                    ->get()
                    ->pluck('name')
                    ->filter()
                    ->values();

                if ($sizes->isEmpty()) {
                    $sizes = Size::active()->ordered()->pluck('name');
                }

                $seasons = Season::query()
                    ->where('seasons.is_active', true)
                    ->whereHas('products', fn($q) => $q->whereIn('products.id', $activeProductIds))
                    ->orderBy('seasons.sort_order')
                    ->get()
                    ->map(fn($s) => [
                        'value' => $s->value,
                        'label' => $s->translated_name,
                    ])
                    ->values();

                if ($seasons->isEmpty()) {
                    $seasons = Season::active()->orderBy('sort_order')
                        ->get()
                        ->map(fn($s) => ['value' => $s->value, 'label' => $s->translated_name])
                        ->values();
                }

                $colorLabelFn = fn($c) => $locale === 'ar' ? ($c->name ?? $c->name_en) : ($c->name_en ?? $c->name);
                $colors = Color::where('is_active', true)
                    ->whereHas('products', fn($q) => $q->whereIn('products.id', $activeProductIds))
                    ->get()
                    ->map($colorLabelFn)
                    ->filter()
                    ->unique()
                    ->values();

                if ($colors->isEmpty()) {
                    $colors = Color::where('is_active', true)->get()->map($colorLabelFn)->filter()->unique()->values();
                }

                return [
                    'sizes' => $sizes,
                    'colors' => $colors,
                    'seasons' => $seasons,
                    'priceRange' => [
                        'min' => (float)($priceRange->min ?? 0),
                        'max' => (float)($priceRange->max ?? 1000)
                    ]
                ];
            });

            return ApiResponseHelper::success($data);

        } catch (\Exception $e) {
            Log::error('ShopController.filters error', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            return ApiResponseHelper::error(
                'SHOP_FILTERS_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Get shop category tree (hierarchical).
     * Applies Option A logical hiding: only returns nodes where the node is active AND all ancestors are active
     * by traversing from active roots down only through active children.
     */
    public function categoryTree()
    {
        try {
            $locale = app()->getLocale();
            $cacheKey = "shop_category_tree_{$locale}";

            $tree = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () {
                $rootCategories = Category::whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

                return $this->buildShopCategoryTree($rootCategories);
            });

            return ApiResponseHelper::success($tree);
        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'SHOP_CATEGORY_TREE_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    private function buildShopCategoryTree($categories): array
    {
        $tree = [];

        foreach ($categories as $category) {
            $children = Category::where('parent_id', $category->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            $tree[] = [
                'id' => $category->id,
                'name' => LocaleHelper::transAttr($category, 'name') ?? $category->name,
                'slug' => $category->slug,
                'is_active' => (bool) $category->is_active,
                'full_path' => $category->full_path,
                'children' => $children->count() > 0 ? $this->buildShopCategoryTree($children) : [],
            ];
        }

        return $tree;
    }

    private function getEffectivelyVisibleCategoryIdsBySlug(string $slug): array
    {
        $normalizedSlug = Str::slug($slug);
        $category = Category::where('slug', $normalizedSlug)->first();
        if (!$category) return [];

        if (!$category->is_active) return [];

        $ancestors = $category->ancestors();
        $hasInactiveAncestor = false;
        if ($ancestors instanceof \Illuminate\Support\Collection) {
            $hasInactiveAncestor = $ancestors->contains(fn($a) => (bool) ($a->is_active ?? true) === false);
        } elseif ($ancestors) {
            $hasInactiveAncestor = $ancestors->where('is_active', false)->exists();
        }

        if ($hasInactiveAncestor) return [];

        $ids = [$category->id];

        $queue = [$category->id];
        while (!empty($queue)) {
            $currentId = array_shift($queue);
            $childrenIds = Category::where('parent_id', $currentId)
                ->where('is_active', true)
                ->pluck('id')
                ->all();

            foreach ($childrenIds as $childId) {
                $ids[] = $childId;
                $queue[] = $childId;
            }
        }

        return array_values(array_unique($ids));
    }

    private function buildFilteredProductsQuery(Request $request)
    {
        $query = Product::query()->where('is_active', true);

        // Category filter
        if ($request->has('category') && $request->category !== 'All') {
            $categorySlug = (string) $request->category;
            $categoryIds = $this->getEffectivelyVisibleCategoryIdsBySlug($categorySlug);

            if (empty($categoryIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            }
        }

        // Price range filter. Cast to float and require a numeric value:
        // an array or non-numeric string previously reached the query builder.
        if ($request->filled('minPrice') && is_numeric($request->input('minPrice'))) {
            $query->where('price', '>=', (float) $request->input('minPrice'));
        }
        if ($request->filled('maxPrice') && is_numeric($request->input('maxPrice'))) {
            $query->where('price', '<=', (float) $request->input('maxPrice'));
        }

        // Size filter (pivot) -- sizes table only has `name`, no `name_en`
        if ($request->filled('size')) {
            $incoming = (string) $request->size;
            $query->whereHas('sizes', function ($q) use ($incoming) {
                if (is_numeric($incoming)) {
                    $q->where('sizes.id', (int) $incoming)
                      ->orWhere('sizes.name', $incoming);
                    return;
                }
                $q->where('sizes.name', $incoming);
            });
        }

        // Color filter (pivot)
        if ($request->filled('color')) {
            $incoming = (string) $request->color;
            $query->whereHas('colors', function ($q) use ($incoming) {
                if (is_numeric($incoming)) {
                    $q->where('colors.id', (int) $incoming);
                    return;
                }
                $q->where('name', $incoming)->orWhere('name_en', $incoming);
            });
        }

        // Season filter (pivot) -- seasons table has `value`, `name_ar`, `name_en` (no `name`)
        if ($request->filled('season')) {
            $incoming = (string) $request->season;
            $query->whereHas('seasons', function ($q) use ($incoming) {
                if (is_numeric($incoming)) {
                    $q->where('seasons.id', (int) $incoming);
                    return;
                }
                $q->where('seasons.value', $incoming)
                    ->orWhere('seasons.name_ar', $incoming)
                    ->orWhere('seasons.name_en', $incoming);
            });
        }

        return $query;
    }
}
