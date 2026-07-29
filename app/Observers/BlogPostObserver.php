<?php

namespace App\Observers;

use App\Models\BlogPost;
use App\Services\CacheService;

class BlogPostObserver
{
    /**
     * Handle the BlogPost "saved" event (created + updated).
     */
    public function saved(BlogPost $blogPost): void
    {
        CacheService::invalidateBlog();
    }

    /**
     * Handle the BlogPost "deleted" event.
     */
    public function deleted(BlogPost $blogPost): void
    {
        CacheService::invalidateBlog();
    }

    /**
     * Handle the BlogPost "force deleted" event.
     */
    public function forceDeleted(BlogPost $blogPost): void
    {
        CacheService::invalidateBlog();
    }
}
