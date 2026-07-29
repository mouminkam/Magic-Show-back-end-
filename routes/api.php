<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    HomeController,
    CategoryController,
    ProductController,
    ReviewController,
    ShopController,
    BlogController,
    AboutController,
    StoreController,
    ContactController,
    CartController,
    OrderController,
    NewsletterController,
    WishlistController
};
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;


/*
|--------------------------------------------------------------------------
| API Routes - Magic Show E-Commerce
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware(['set.language', 'throttle:api'])->group(function () {
    
    // Health check
    Route::get('/health', fn() => response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString()
    ]));
    
    // Authentication (throttled: 5 attempts per minute)
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/user', [AuthController::class, 'user']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });
    
    // Home
    Route::prefix('home')->group(function () {
        Route::get('/hero', [HomeController::class, 'hero']);
        Route::get('/about-us', [HomeController::class, 'aboutUs']);
        Route::get('/featured-categories', [HomeController::class, 'featuredCategories']);
        Route::get('/new-arrivals', [HomeController::class, 'newArrivals']);
        Route::get('/best-sellers', [HomeController::class, 'bestSellers']);
        Route::get('/featured-products', [HomeController::class, 'featuredProducts']);
        Route::get('/blog-section', [HomeController::class, 'blogSection']);
        Route::get('/latest-blog', [HomeController::class, 'latestBlog']);
        Route::get('/why-choose-us', [HomeController::class, 'whyChooseUs']);
        Route::get('/customer-reviews', [HomeController::class, 'customerReviews']);
        Route::get('/newsletter', [HomeController::class, 'newsletter']);
    });

    // Newsletter (throttled: 3 per hour)
    Route::prefix('newsletter')->middleware('throttle:newsletter')->group(function () {
        Route::post('/subscribe', [NewsletterController::class, 'subscribe']);
        Route::post('/unsubscribe', [NewsletterController::class, 'unsubscribe']);
    });
    
    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    
    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    
    // Reviews
    Route::get('/reviews', [ReviewController::class, 'index']);
    
    // Shop
    Route::prefix('shop')->group(function () {
        Route::get('/banner', [ShopController::class, 'banner']);
        Route::get('/products', [ShopController::class, 'products']);
        Route::get('/categories', [ShopController::class, 'categories']);
        Route::get('/category-tree', [ShopController::class, 'categoryTree']);
        Route::get('/filters', [ShopController::class, 'filters']);
        Route::get('/products/{id}/related', [ProductController::class, 'related']);
    });
    
    // Blog
    Route::prefix('blog')->group(function () {
        Route::get('/banner', [BlogController::class, 'banner']);
        Route::get('/posts', [BlogController::class, 'posts']);
        Route::get('/posts/{id}', [BlogController::class, 'show']);
        Route::get('/posts/{id}/comments', [BlogController::class, 'comments']);
        // Unauthenticated write endpoint: throttle it like the other public
        // write endpoints (contact / newsletter) to blunt comment spam.
        Route::post('/posts/{id}/comments', [BlogController::class, 'storeComment'])
            ->middleware('throttle:comments');
    });
    
    // About
    Route::prefix('about')->group(function () {
        Route::get('/hero', [AboutController::class, 'hero']);
        Route::get('/team-members', [AboutController::class, 'teamMembers']);
        Route::get('/testimonials', [AboutController::class, 'testimonials']);
        Route::get('/description', [AboutController::class, 'description']);
        Route::get('/stats', [AboutController::class, 'stats']);
    });
    
    // Stores
    Route::prefix('stores')->group(function () {
        Route::get('/banner', [StoreController::class, 'banner']);
        Route::get('/', [StoreController::class, 'index']);
    });
    
    // Contact (throttled: 3 per hour for send-message)
    Route::prefix('contact')->group(function () {
        Route::get('/hero', [ContactController::class, 'hero']);
        Route::get('/map', [ContactController::class, 'map']);
        Route::get('/details', [ContactController::class, 'details']);
        Route::post('/send-message', [ContactController::class, 'sendMessage'])->middleware('throttle:contact');
    });
    
    // Cart (works for both guest and authenticated users).
    //
    // Guest carts are keyed on the session id (see CartService::getOrCreateCart).
    // Laravel's `api` middleware group is stateless and does NOT start a
    // session, so without these three middleware every guest cart request threw
    // "Session store not set on request." and returned HTTP 500.
    Route::prefix('cart')->middleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
    ])->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('/{itemId}', [CartController::class, 'update']);
        Route::delete('/{itemId}', [CartController::class, 'destroy']);
        Route::delete('/', [CartController::class, 'clear']);
        Route::post('/validate-coupon', [CartController::class, 'validateCoupon']);
        Route::post('/checkout', [CartController::class, 'checkout'])->middleware('auth:sanctum');
    });
    
    // Orders (protected - customer)
    Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{order}', [OrderController::class, 'show']);
    });

    // Admin orders (protected - admin API: requires User token with super_admin or store_manager role)
    Route::middleware(['auth:sanctum', 'admin.api'])->prefix('admin/orders')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index']);
        Route::get('/{order}', [AdminOrderController::class, 'show']);
        Route::patch('/{order}', [AdminOrderController::class, 'updateStatus']);
    });

    // Wishlist (protected - requires login)
    Route::middleware('auth:sanctum')->prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/', [WishlistController::class, 'store']);
        Route::get('/check', [WishlistController::class, 'check']);
        Route::delete('/{productId}', [WishlistController::class, 'destroy']);
    });
});
