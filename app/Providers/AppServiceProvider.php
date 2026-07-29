<?php

namespace App\Providers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\HomePageSection;
use App\Models\Order;
use App\Models\Product;
use App\Observers\BlogPostObserver;
use App\Observers\CategoryObserver;
use App\Observers\HomePageSectionObserver;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Product::observe(ProductObserver::class);
        Category::observe(CategoryObserver::class);
        Order::observe(OrderObserver::class);
        BlogPost::observe(BlogPostObserver::class);
        HomePageSection::observe(HomePageSectionObserver::class);

        RateLimiter::for('api', function (Request $request) {
            $limit = app()->environment('local') ? 300 : 120;
            return Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('contact', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        RateLimiter::for('newsletter', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        // Public, unauthenticated write endpoint (blog comments).
        RateLimiter::for('comments', function (Request $request) {
            return Limit::perHour(10)->by($request->ip());
        });
    }
}
