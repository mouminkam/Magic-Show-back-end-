<?php

namespace App\Observers;

use App\Models\HomePageSection;
use App\Services\CacheService;

class HomePageSectionObserver
{
    /**
     * Handle the HomePageSection "saved" event (created + updated).
     */
    public function saved(HomePageSection $homePageSection): void
    {
        CacheService::invalidateHome();
    }

    /**
     * Handle the HomePageSection "deleted" event.
     */
    public function deleted(HomePageSection $homePageSection): void
    {
        CacheService::invalidateHome();
    }

    /**
     * Handle the HomePageSection "force deleted" event.
     */
    public function forceDeleted(HomePageSection $homePageSection): void
    {
        CacheService::invalidateHome();
    }
}
