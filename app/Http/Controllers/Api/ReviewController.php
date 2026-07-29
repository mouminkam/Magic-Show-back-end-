<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponseHelper;
use App\Models\Review;
use App\Support\ProductImageUrlBuilder;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReviewController extends Controller
{
    /** Upper bound for the client-supplied `limit` query parameter. */
    private const MAX_LIMIT = 50;

    /**
     * Get reviews list.
     * Uses TTL_VOLATILE (5 min) - user-generated content.
     */
    public function index(Request $request)
    {
        try {
            // Bounded cache key (was md5 of $request->all(), i.e. attacker-controlled).
            $paramsHash = CacheService::requestFingerprint($request, ['featured', 'limit']);
            $locale = app()->getLocale();
            $cacheKey = "reviews_index_{$paramsHash}_{$locale}";

            // Clamp: `limit` was passed straight to limit() — `?limit=1000000`
            // dumped the whole table, `?limit=-1` threw.
            $limit = max(1, min((int) $request->get('limit', 10) ?: 10, self::MAX_LIMIT));

            $reviews = Cache::remember($cacheKey, CacheService::TTL_VOLATILE, function () use ($request, $limit) {
                // productImages is required by ProductImageUrlBuilder; without it
                // this endpoint issued one extra query per review (N+1).
                $query = Review::with(['customer', 'product.productImages']);

                if ($request->boolean('featured')) {
                    $query->where('is_featured', true);
                }

                return $query->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(function($review) {
                        $product = $review->product;
                        $customer = $review->customer;
                        $productImageUrls = $product ? ProductImageUrlBuilder::build($product) : null;

                        // A review whose customer or product row was removed used
                        // to dereference null here and return a 500 for the whole list.
                        return [
                            'id' => $review->id,
                            'name' => $customer
                                ? trim($customer->first_name . ' ' . $customer->last_name)
                                : '',
                            'text' => $review->comment,
                            'comment' => $review->comment,
                            'image' => asset('images/default-user.png'),
                            'productImage' => $productImageUrls['thumb']
                                ?? $product?->primary_image_thumb_url
                                ?? $product?->primary_image_url,
                            'productImageUrls' => $productImageUrls,
                            'rating' => $review->rating,
                            'date' => $review->created_at->diffForHumans()
                        ];
                    })
                    ->values();
            });

            return ApiResponseHelper::success($reviews);

        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'REVIEWS_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }
}
