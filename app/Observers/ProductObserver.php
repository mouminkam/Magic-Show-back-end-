<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\CacheService;

class ProductObserver
{
    /**
     * Handle the Product "saved" event (created + updated).
     */
    public function saved(Product $product): void
    {
        CacheService::invalidateProducts();
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        CacheService::invalidateProducts();
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        CacheService::invalidateProducts();
    }
}
