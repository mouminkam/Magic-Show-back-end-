<?php

namespace App\Support;

use App\Models\Product;

class ProductImageUrlBuilder
{
    public static function build(Product $product): array
    {
        $ts = $product->updated_at?->timestamp ?? time();

        $thumb = $product->primary_image_thumb_url ?? '';
        $full = $product->primary_image_url ?? '';

        $firstGalleryMedium = ($product->relationLoaded('productImages') && $product->productImages && $product->productImages->count() > 0)
            ? ($product->productImages->first()?->medium_url ?? '')
            : '';

        $medium = $product->featured_image_medium_url ?? ($firstGalleryMedium ?: $full);

        $thumbBusted = self::cacheBust($thumb, $ts);
        $mediumBusted = self::cacheBust($medium, $ts);
        $fullBusted = self::cacheBust($full, $ts);

        return [
            'thumb' => $thumbBusted ?: ($thumb ?: $full),
            'medium' => $mediumBusted ?: ($medium ?: $full),
            'full' => $fullBusted ?: ($full ?: $medium ?: $thumb),
        ];
    }

    private static function cacheBust(?string $url, int $ts): string
    {
        $url = is_string($url) ? trim($url) : '';
        if ($url === '') {
            return '';
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . $ts;
    }
}
