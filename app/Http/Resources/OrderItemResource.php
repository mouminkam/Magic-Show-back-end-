<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Support\ProductImageUrlBuilder;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');
        $imageUrls = null;
        if ($product instanceof \App\Models\Product) {
            $imageUrls = ProductImageUrlBuilder::build($product);
        }

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_price' => (float) $this->total_price,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'product_attributes' => $this->product_attributes ?? [],
            'notes' => $this->notes,
            'image_urls' => $imageUrls,
            'image' => $imageUrls['thumb'] ?? null,
            // Null-safe: order items outlive product deletions.
            'product' => $this->whenLoaded('product', fn () => $this->product ? new ProductResource($this->product) : null),
        ];
    }
}

