<?php

namespace App\Http\Resources;

use App\Helpers\LocaleHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => LocaleHelper::transAttr($this->resource, 'name') ?? $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => LocaleHelper::transAttr($this->resource, 'description') ?? $this->description,
            'short_description' => LocaleHelper::transAttr($this->resource, 'short_description') ?? $this->short_description,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price ? (float) $this->sale_price : null,
            'currency' => $this->currency ?? 'SYP',
            'is_featured' => (bool) $this->is_featured,
            'is_active' => (bool) $this->is_active,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            // products.brand_id is nullable. `new BrandResource($this->whenLoaded('brand'))`
            // passed a bare null through to BrandResource::toArray() when the
            // relation was eager-loaded but empty, throwing
            // "Attempt to read property \"id\" on null". Shape is unchanged:
            // key omitted when not loaded, null when loaded-but-empty.
            'brand' => $this->whenLoaded(
                'brand',
                fn () => $this->brand ? new BrandResource($this->brand) : null
            ),
            'images' => $this->buildOrderedImages(),
            'featured_image' => $this->featured_image ? $this->cacheBustUrl(asset('storage/' . $this->featured_image)) : null,
            'featured_image_urls' => [
                'thumb' => $this->cacheBustUrl($this->featured_image_thumb_url ?? asset('images/no-image.png')),
                'medium' => $this->cacheBustUrl($this->featured_image_medium_url ?? asset('images/no-image.png')),
                'full' => $this->cacheBustUrl($this->featured_image_full_url ?? asset('images/no-image.png')),
            ],
            // Backward compatibility (one release): names array
            'colors' => $this->whenLoaded('colors', fn() => $this->colors->map(fn($c) => $c->name_en ?? $c->name)->values()),
            // New structured colors payload
            'colors_detailed' => $this->whenLoaded('colors', function () {
                return $this->colors->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'name' => $c->name_en ?? $c->name,
                        'color_code' => $c->color_code ?? $c->hex_code,
                        'is_primary' => (bool) ($c->pivot->is_primary ?? false),
                        'sort_order' => (int) ($c->pivot->sort_order ?? 0),
                    ];
                })->values();
            }),
            'sizes' => $this->resolveSizes(),
            'seasons' => $this->resolveSeasons(),
            'seasons_detailed' => $this->resolveSeasonsDetailed(),
            'stock_quantity' => $this->quantity ?? 0,
            'in_stock' => ($this->quantity ?? 0) > 0,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'model' => $this->model,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    private function resolveSizes(): array
    {
        if ($this->resource->relationLoaded('sizes') && $this->getRelation('sizes')->isNotEmpty()) {
            return $this->getRelation('sizes')->pluck('name')->values()->all();
        }
        return $this->getSizesFallback();
    }

    private function resolveSeasons(): array
    {
        if ($this->resource->relationLoaded('seasons') && $this->getRelation('seasons')->isNotEmpty()) {
            return $this->getRelation('seasons')->pluck('value')->values()->all();
        }
        return $this->getSeasonsFallback();
    }

    private function resolveSeasonsDetailed(): ?array
    {
        if ($this->resource->relationLoaded('seasons') && $this->getRelation('seasons')->isNotEmpty()) {
            return $this->getRelation('seasons')->map(fn($s) => [
                'id' => $s->id,
                'value' => $s->value,
                'label' => $s->translated_name,
            ])->values()->all();
        }
        $fallback = $this->getSeasonsFallback();
        if (empty($fallback)) return null;
        return collect($fallback)->map(fn($v) => ['value' => $v, 'label' => $v])->values()->all();
    }

    private function getSizesFallback(): array
    {
        $raw = $this->resource->getRawOriginal('sizes');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    private function getSeasonsFallback(): array
    {
        $raw = $this->resource->getRawOriginal('seasons');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    private function cacheBustUrl(string $url): string
    {
        $ts = $this->updated_at?->timestamp ?? time();
        return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . $ts;
    }

    /**
     * Build images array: featured_image first, then productImages (primary first, then by order).
     * Ensures the main image from dashboard is always shown first on the product page.
     */
    private function buildOrderedImages(): array
    {
        $result = [];
        $featuredPath = $this->featured_image;

        if ($featuredPath) {
            $result[] = [
                'id' => null,
                'url' => $this->cacheBustUrl(asset('storage/' . $featuredPath)),
                'image_urls' => [
                    'thumb' => $this->cacheBustUrl($this->featured_image_thumb_url ?? asset('images/no-image.png')),
                    'medium' => $this->cacheBustUrl($this->featured_image_medium_url ?? asset('images/no-image.png')),
                    'full' => $this->cacheBustUrl($this->featured_image_full_url ?? asset('images/no-image.png')),
                ],
                'alt_text' => null,
                'is_primary' => true,
                'order' => 0,
            ];
        }

        $productImages = $this->resource->productImages ?? collect();
        if (!($productImages instanceof \Illuminate\Support\Collection)) {
            $productImages = collect($productImages);
        }

        foreach ($productImages as $img) {
            if ($featuredPath && $img->path === $featuredPath) {
                continue;
            }
            $result[] = (new ProductImageResource($img))->toArray(request());
        }

        return $result;
    }
}
