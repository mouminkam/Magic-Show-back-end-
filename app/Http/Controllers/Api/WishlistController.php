<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\LocaleHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wishlist\AddToWishlistRequest;
use App\Support\ProductImageUrlBuilder;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * Get wishlist items for the authenticated customer.
     */
    public function index(Request $request)
    {
        $items = $request->user()
            ->wishlists()
            ->with(['product.categories', 'product.productImages', 'product.colors'])
            ->orderByDesc('wishlists.created_at')
            ->get()
            ->map(function ($item) {
                $product = $item->product;
                if (!$product || !$product->is_active) {
                    return null;
                }
                $categoryNames = $product->categories->map(fn ($c) => LocaleHelper::transAttr($c, 'name'))->filter()->implode(', ');
                $locale = app()->getLocale();
                $colorLabelFn = fn ($c) => $locale === 'ar' ? ($c->name ?? $c->name_en) : ($c->name_en ?? $c->name);

                $imageUrls = ProductImageUrlBuilder::build($product);

                return [
                    'id' => $item->id,
                    'productId' => $product->id,
                    'name' => LocaleHelper::transAttr($product, 'name') ?? $product->name,
                    'category' => $categoryNames ?: $product->categories->pluck('name')->implode(', '),
                    'price' => (float) ($product->sale_price ?? $product->price),
                    'originalPrice' => $product->sale_price ? (float) $product->price : null,
                    'discount' => $product->discount_percentage,
                    'image' => $imageUrls['thumb'] ?? ($product->primary_image_thumb_url ?? $product->primary_image_url),
                    'image_urls' => $imageUrls,
                    'slug' => $product->slug,
                    'addedAt' => $item->created_at->toISOString(),
                ];
            })
            ->filter()
            ->values();

        return ApiResponseHelper::success([
            'items' => $items,
            'count' => $items->count(),
        ]);
    }

    /**
     * Add a product to wishlist.
     */
    public function store(AddToWishlistRequest $request)
    {
        $productId = (int) $request->validated()['product_id'];
        $customer = $request->user();

        $product = Product::where('id', $productId)->where('is_active', true)->first();
        if (!$product) {
            return ApiResponseHelper::notFound(__('errors.product_not_found'));
        }

        $existing = Wishlist::where('customer_id', $customer->id)->where('product_id', $productId)->first();
        if ($existing) {
            return ApiResponseHelper::success(
                ['inWishlist' => true],
                __('messages.wishlist_already_added'),
                null,
                200
            );
        }

        Wishlist::create([
            'customer_id' => $customer->id,
            'product_id' => $productId,
        ]);

        return ApiResponseHelper::success(
            ['inWishlist' => true],
            __('messages.wishlist_added'),
            null,
            201
        );
    }

    /**
     * Remove a product from wishlist.
     */
    public function destroy(Request $request, int $productId)
    {
        $deleted = Wishlist::where('customer_id', $request->user()->id)
            ->where('product_id', $productId)
            ->delete();

        if (!$deleted) {
            return ApiResponseHelper::notFound(__('messages.wishlist_item_not_found'));
        }

        return ApiResponseHelper::success(
            ['inWishlist' => false],
            __('messages.wishlist_removed')
        );
    }

    /**
     * Check if product is in wishlist (returns product IDs for batch check).
     */
    public function check(Request $request)
    {
        $productIds = $request->get('product_ids', []);
        if (!is_array($productIds)) {
            $productIds = [];
        }
        $productIds = array_map('intval', array_filter($productIds));

        if (empty($productIds)) {
            return ApiResponseHelper::success(['productIds' => []]);
        }

        $ids = Wishlist::where('customer_id', $request->user()->id)
            ->whereIn('product_id', $productIds)
            ->pluck('product_id')
            ->toArray();

        return ApiResponseHelper::success(['productIds' => $ids]);
    }
}
