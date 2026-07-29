<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\ProductImageUrlBuilder;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Query parameters this endpoint actually honours. Anything else is
     * ignored when building the cache key so the key space stays bounded.
     */
    private const CACHEABLE_FILTERS = [
        'category_id', 'brand_id', 'search', 'featured', 'onSale',
        'sort', 'sort_by', 'sort_order', 'per_page', 'page',
    ];

    /** Sort directions accepted from the client. */
    private const SORT_DIRECTIONS = ['asc', 'desc'];

    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        try {
            $version = CacheService::getVersion('products');
            $paramsHash = CacheService::requestFingerprint($request, self::CACHEABLE_FILTERS);
            $locale = app()->getLocale();
            $cacheKey = "products_index_v{$version}_{$paramsHash}_{$locale}";

            $products = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () use ($request) {
                $query = Product::with(['categories', 'brand', 'productImages'])
                    ->where('is_active', true);

                if ($request->has('category_id')) {
                    $query->whereHas('categories', function($q) use ($request) {
                        $q->where('categories.id', $request->category_id);
                    });
                }

                if ($request->has('brand_id')) {
                    $query->where('brand_id', $request->brand_id);
                }

                // Reject non-scalar input outright: an array-valued `search`
                // (?search[]=a&search[]=b) reached the query builder and 500'd,
                // and casting an array to string is itself an ErrorException.
                if ($request->filled('search') && is_scalar($request->input('search'))) {
                    $search = (string) $request->input('search');
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%')
                          ->orWhere('description', 'like', '%' . $search . '%')
                          ->orWhere('sku', 'like', '%' . $search . '%');
                    });
                }

                if ($request->has('featured')) {
                    $query->where('is_featured', $request->boolean('featured'));
                }

                if ($request->boolean('onSale')) {
                    $query->whereNotNull('sale_price');
                }

                $sort = $request->get('sort');
                if ($sort === 'newest') {
                    $query->orderBy('created_at', 'desc');
                } elseif ($sort === 'bestseller') {
                    $query->orderBy('sales_count', 'desc');
                } else {
                    $sortBy = $request->get('sort_by', 'created_at');
                    $sortOrder = strtolower((string) $request->get('sort_order', 'desc'));
                    $allowedSorts = ['name', 'price', 'created_at', 'updated_at'];
                    // sort_order was passed straight through; anything other
                    // than asc/desc made orderBy() throw and surfaced as a 500.
                    $query->orderBy(
                        in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at',
                        in_array($sortOrder, self::SORT_DIRECTIONS, true) ? $sortOrder : 'desc'
                    );
                }

                // Clamp into [1, 100]: `per_page=0` returned the entire table
                // and a non-numeric value compared as a string against 100.
                $perPage = max(1, min((int) $request->get('per_page', 15) ?: 15, 100));
                return $query->paginate($perPage);
            });

            return new ProductCollection($products);

        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'PRODUCTS_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Display the specified product.
     * Uses Cache::forget on invalidate - immediate update after dashboard edit.
     */
    public function show($id)
    {
        try {
            $locale = app()->getLocale();
            $version = CacheService::getVersion('products');
            $cacheKey = "product_{$id}_{$locale}_v{$version}";

            $product = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () use ($id) {
                return Product::with(['categories', 'brand', 'productImages', 'material', 'colors', 'sizes', 'seasons'])
                    ->where('is_active', true)
                    ->find($id);
            });

            if (!$product) {
                return ApiResponseHelper::notFound(__('errors.product_not_found'));
            }

            return ApiResponseHelper::success(new ProductResource($product));

        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'PRODUCT_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Get related products.
     * Uses Cache::forget on invalidate - immediate update after dashboard edit.
     */
    public function related($id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return ApiResponseHelper::notFound(__('errors.product_not_found'));
            }

            $locale = app()->getLocale();
            $version = CacheService::getVersion('products');
            $cacheKey = "product_related_{$id}_{$locale}_v{$version}";

            $related = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () use ($product, $id) {
                return Product::whereHas('categories', function($q) use ($product) {
                    $q->whereIn('categories.id', $product->categories->pluck('id'));
                })
                ->where('id', '!=', $id)
                ->where('is_active', true)
                ->limit(6)
                ->get()
                ->map(function($p) {
                    $imageUrls = ProductImageUrlBuilder::build($p);
                    $img = $imageUrls['thumb'] ?? ($p->primary_image_thumb_url ?? $p->primary_image_url ?? '');
                    return [
                        'id' => $p->id,
                        'name' => \App\Helpers\LocaleHelper::transAttr($p, 'name') ?? $p->name,
                        'price' => $p->sale_price ?? $p->price,
                        'image' => $img ?: '/images/img04.jpg',
                        'image_urls' => $imageUrls,
                    ];
                })
                ->values();
            });

            return ApiResponseHelper::success($related);

        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'RELATED_PRODUCTS_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }
}
