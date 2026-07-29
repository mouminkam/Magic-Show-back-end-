<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => [
                // ResourceCollection::collectResource() has ALREADY mapped every
                // item through ProductResource (inferred from this class name).
                // Wrapping a second time made $this->resource a ProductResource
                // instead of a Product, and LocaleHelper::transAttr()'s
                // `Model $model` type hint then threw a TypeError — i.e.
                // GET /api/v1/products returned 500 for any non-empty result set.
                'items' => $this->collection->map(
                    fn ($product) => $product instanceof ProductResource
                        ? $product->toArray($request)
                        : (new ProductResource($product))->toArray($request)
                )->values(),
            ],
            'meta' => [
                'pagination' => [
                    'currentPage' => $this->currentPage(),
                    'limit' => $this->perPage(),
                    'totalItems' => $this->total(),
                    'totalPages' => $this->lastPage(),
                    'hasNext' => $this->hasMorePages(),
                    'hasPrev' => $this->currentPage() > 1,
                ]
            ],
        ];
    }
}
