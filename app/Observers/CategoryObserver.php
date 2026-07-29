<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\CacheService;

class CategoryObserver
{
    /**
     * Handle the Category "saved" event (created + updated).
     */
    public function saved(Category $category): void
    {
        CacheService::invalidateCategories();
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        CacheService::invalidateCategories();
    }

    /**
     * Handle the Category "force deleted" event.
     */
    public function forceDeleted(Category $category): void
    {
        CacheService::invalidateCategories();
    }
}
