<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    private const VERSION_KEYS = [
        'products' => 'cache_version_products',
        'categories' => 'cache_version_categories',
        'home' => 'cache_version_home',
        'blog' => 'cache_version_blog',
        'shop' => 'cache_version_shop',
    ];

    /** @var int Unified TTL for all public API endpoints (10 minutes) */
    public const PUBLIC_API_TTL = 600;

    public const TTL_STATIC = 3600;    // 1 hour - About, Contact, Stores
    public const TTL_DYNAMIC = 600;    // 10 min - Products, Shop, Home
    public const TTL_VOLATILE = 300;   // 5 min - Reviews, Comments

    public static function getVersion(string $type): int
    {
        return (int) Cache::get(self::VERSION_KEYS[$type] ?? 'cache_version_default', 1);
    }

    /**
     * Build a deterministic, bounded cache-key fragment from request input.
     *
     * Callers used to hash `$request->all()`, which let any client mint an
     * unlimited number of distinct cache keys (`?a=1`, `?a=2`, ...) and flood
     * the cache store — with CACHE_STORE=database that is unbounded table
     * growth and a cheap denial-of-service. Only the query parameters an
     * endpoint actually reads take part in the key; everything else is ignored.
     *
     * @param  array<int, string>  $allowed  Whitelist of query parameter names.
     */
    public static function requestFingerprint(\Illuminate\Http\Request $request, array $allowed): string
    {
        $params = [];

        foreach ($allowed as $key) {
            if (!$request->has($key)) {
                continue;
            }

            $value = $request->input($key);

            // Flatten scalars only; arrays/objects are collapsed to a stable
            // marker so nested input cannot expand the key space either.
            $params[$key] = is_scalar($value) || $value === null
                ? (string) $value
                : md5(json_encode($value));
        }

        ksort($params);

        return md5(http_build_query($params));
    }

    public static function invalidateProducts(): void
    {
        $v = (int) Cache::get(self::VERSION_KEYS['products'], 1);
        Cache::put(self::VERSION_KEYS['products'], $v + 1, 86400);
        foreach (['ar', 'en'] as $locale) {
            Cache::forget("shop_filters_{$locale}");
        }
        // Per-product keys include the version number (product_{id}_{locale}_v{N}),
        // so incrementing the version above makes all old keys unreachable without
        // needing to enumerate every product id.
        FrontendRevalidationService::revalidate(['shop', 'product', 'home']);
    }

    /**
     * Invalidate cache for a single product by bumping the global products version.
     */
    public static function invalidateProduct(int $id): void
    {
        self::invalidateProducts();
    }

    public static function invalidateCategories(): void
    {
        $v = (int) Cache::get(self::VERSION_KEYS['categories'], 1);
        Cache::put(self::VERSION_KEYS['categories'], $v + 1, 86400);
        foreach (['ar', 'en'] as $locale) {
            Cache::forget("shop_categories_{$locale}");
            Cache::forget("shop_category_tree_{$locale}");
        }
        FrontendRevalidationService::revalidate(['shop', 'home']);
    }

    public static function invalidateHome(): void
    {
        $sections = ['hero', 'about_us', 'featured_categories', 'new_arrivals', 'best_sellers', 'featured_products', 'blog_section', 'latest_blog', 'why_choose_us', 'customer_reviews', 'newsletter'];
        foreach (['ar', 'en'] as $locale) {
            foreach ($sections as $section) {
                Cache::forget("home_{$section}_{$locale}");
            }
        }
        FrontendRevalidationService::revalidate(['home']);
    }

    public static function invalidateBlog(): void
    {
        $v = (int) Cache::get(self::VERSION_KEYS['blog'], 1);
        Cache::put(self::VERSION_KEYS['blog'], $v + 1, 86400);
        foreach (['ar', 'en'] as $locale) {
            Cache::forget("blog_banner_{$locale}");
            for ($p = 1; $p <= 50; $p++) {
                Cache::forget("blog_posts_{$p}_3_{$locale}");
                Cache::forget("blog_posts_{$p}_6_{$locale}");
                Cache::forget("blog_posts_{$p}_9_{$locale}");
                Cache::forget("blog_posts_{$p}_12_{$locale}");
                Cache::forget("blog_posts_{$p}_15_{$locale}");
                Cache::forget("blog_posts_{$p}_20_{$locale}");
            }
            foreach (BlogPost::pluck('id') as $postId) {
                Cache::forget("blog_post_{$postId}_{$locale}");
            }
        }
        FrontendRevalidationService::revalidate(['blog']);
    }

    /**
     * Invalidate shop products cache (uses products version).
     */
    public static function invalidateShopProducts(): void
    {
        self::invalidateProducts();
    }

    /**
     * Invalidate About page cache (hero, team, testimonials, description, stats).
     */
    public static function invalidateAbout(): void
    {
        foreach (['ar', 'en'] as $locale) {
            Cache::forget("about_hero_{$locale}");
            Cache::forget("about_team_members_{$locale}");
            Cache::forget("about_testimonials_{$locale}");
            Cache::forget("about_description_{$locale}");
            Cache::forget("about_stats_{$locale}");
        }
        FrontendRevalidationService::revalidate(['about']);
    }

    /**
     * Invalidate Contact page cache (hero, map, details).
     */
    public static function invalidateContact(): void
    {
        foreach (['ar', 'en'] as $locale) {
            Cache::forget("contact_hero_{$locale}");
            Cache::forget("contact_map_{$locale}");
            Cache::forget("contact_details_{$locale}");
        }
        FrontendRevalidationService::revalidate(['contact']);
    }

    /**
     * Invalidate Shop page banner, categories, filters cache.
     */
    public static function invalidateShop(): void
    {
        $v = (int) Cache::get(self::VERSION_KEYS['shop'], 1);
        Cache::put(self::VERSION_KEYS['shop'], $v + 1, 86400);
        foreach (['ar', 'en'] as $locale) {
            Cache::forget("shop_banner_{$locale}");
            Cache::forget("shop_categories_{$locale}");
            Cache::forget("shop_category_tree_{$locale}");
            Cache::forget("shop_filters_{$locale}");
        }
        FrontendRevalidationService::revalidate(['shop']);
    }

    /**
     * Invalidate Stores page cache (banner, list).
     */
    public static function invalidateStores(): void
    {
        foreach (['ar', 'en'] as $locale) {
            Cache::forget("stores_banner_{$locale}");
            Cache::forget("stores_list_{$locale}");
        }
        FrontendRevalidationService::revalidate(['stores']);
    }

    /**
     * Invalidate blog post comments cache when a new comment is added.
     */
    public static function invalidateBlogPostComments(int $postId): void
    {
        foreach (['ar', 'en'] as $locale) {
            Cache::forget("blog_post_comments_{$postId}_{$locale}");
        }
        FrontendRevalidationService::revalidate(['blog', "blog-post-{$postId}"]);
    }
}
