<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponseHelper;
use App\Models\Category;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /**
     * Get categories list.
     */
    public function index(Request $request)
    {
        try {
            $version = CacheService::getVersion('categories');
            // Bounded cache key: only the parameter this endpoint reads.
            $paramsHash = CacheService::requestFingerprint($request, ['featured']);
            $locale = app()->getLocale();
            $cacheKey = "categories_index_v{$version}_{$paramsHash}_{$locale}";

            $categories = Cache::remember($cacheKey, CacheService::TTL_DYNAMIC, function () use ($request) {
                $query = Category::where('is_active', true);

                if ($request->boolean('featured')) {
                    $query->where('is_featured', true);
                }

                return $query->orderBy('sort_order')
                    ->get()
                    ->map(function($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'image' => $category->image ? asset('storage/' . $category->image) : null,
                            'order' => $category->sort_order
                        ];
                    })
                    ->values();
            });

            return ApiResponseHelper::success($categories);

        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'CATEGORIES_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }
}
