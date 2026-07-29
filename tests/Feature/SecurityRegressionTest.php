<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Regression coverage for the security defects found during the API audit.
 * Each test names the specific issue it locks down.
 */
class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(array $attributes = []): Customer
    {
        return Customer::create(array_merge([
            'first_name' => 'Reg',
            'last_name' => 'Test',
            'email' => 'reg@example.com',
            'phone' => '+1234567890',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Authenticate the next request as $customer.
     *
     * forgetGuards() is required because all requests in a test share one
     * application instance, and Sanctum's RequestGuard memoises the resolved
     * user. Without it a second request in the same test silently reuses the
     * first caller's identity, which makes ownership assertions meaningless.
     * (Production is unaffected: one request per process.)
     */
    private function asCustomer(Customer $customer): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader(
            'Authorization',
            'Bearer ' . $customer->createToken('test-token')->plainTextToken
        );
    }

    private function asUser(User $user): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader(
            'Authorization',
            'Bearer ' . $user->createToken('test-token')->plainTextToken
        );
    }

    // ---------------------------------------------------------------------
    // Auth
    // ---------------------------------------------------------------------

    /**
     * LoginRequest only accepted `identifier`, so the documented
     * {"email": ..., "password": ...} payload returned 422 instead of 200/401.
     */
    public function test_login_accepts_both_identifier_and_email_payloads(): void
    {
        $this->makeCustomer(['email' => 'login@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'login@example.com',
            'password' => 'password123',
        ])->assertStatus(200);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ])->assertStatus(200);
    }

    /** Phone login must keep working through the `identifier` alias handling. */
    public function test_login_accepts_phone_identifier(): void
    {
        $this->makeCustomer(['phone' => '+9611234567']);

        $this->postJson('/api/v1/auth/login', [
            'identifier' => '+9611234567',
            'password' => 'password123',
        ])->assertStatus(200);
    }

    /** A wholly missing identifier is still a validation error, not a 500. */
    public function test_login_without_identifier_is_a_validation_error(): void
    {
        $this->postJson('/api/v1/auth/login', ['password' => 'password123'])
            ->assertStatus(422);
    }

    /**
     * Password reset used to leave previously issued Sanctum tokens valid, so a
     * stolen token survived the victim resetting their password.
     */
    public function test_password_reset_revokes_all_existing_tokens(): void
    {
        $customer = $this->makeCustomer();
        $customer->createToken('stolen-token');

        $this->assertSame(1, $customer->tokens()->count());

        $token = Password::broker('customers')->createToken($customer);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'BrandNewPassw0rd!',
            'password_confirmation' => 'BrandNewPassw0rd!',
        ])->assertStatus(200);

        $this->assertSame(0, $customer->fresh()->tokens()->count());
    }

    /**
     * forgot-password returned 400 for unknown addresses and 200 for known ones,
     * which let an attacker enumerate registered customers.
     */
    public function test_forgot_password_does_not_disclose_account_existence(): void
    {
        Notification::fake();
        $this->makeCustomer(['email' => 'known@example.com']);

        $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'known@example.com']);
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com']);

        $known->assertStatus(200);
        $unknown->assertStatus(200);
        $this->assertSame($known->json('message'), $unknown->json('message'));
    }

    /** The password broker messages must resolve, not echo the raw lang key. */
    public function test_password_messages_are_translated(): void
    {
        Notification::fake();
        $this->makeCustomer(['email' => 'known@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'known@example.com']);

        $this->assertNotSame('passwords.sent', $response->json('message'));
    }

    /**
     * ProfileUpdateRequest's unique rule did not ignore the current customer, so
     * re-submitting an unchanged phone number blocked every profile update.
     */
    public function test_profile_update_allows_resubmitting_the_same_phone(): void
    {
        $customer = $this->makeCustomer(['phone' => '+9611111111']);

        $this->asCustomer($customer)->putJson('/api/v1/auth/profile', [
            'first_name' => 'Renamed',
            'phone' => '+9611111111',
        ])->assertStatus(200);

        $this->assertSame('Renamed', $customer->fresh()->first_name);
    }

    /** ...while a phone already taken by someone else is still rejected. */
    public function test_profile_update_still_rejects_another_customers_phone(): void
    {
        $this->makeCustomer(['email' => 'other@example.com', 'phone' => '+9612222222']);
        $customer = $this->makeCustomer(['email' => 'me@example.com', 'phone' => '+9613333333']);

        $this->asCustomer($customer)->putJson('/api/v1/auth/profile', [
            'phone' => '+9612222222',
        ])->assertStatus(422);
    }

    /** A Customer model must never serialise its password hash. */
    public function test_customer_model_hides_password_hash(): void
    {
        $customer = $this->makeCustomer();

        $this->assertArrayNotHasKey('password', $customer->toArray());
        $this->assertArrayNotHasKey('remember_token', $customer->toArray());
    }

    // ---------------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------------

    /** Customer tokens must not reach the staff-only admin order endpoints. */
    public function test_customer_token_cannot_access_admin_orders(): void
    {
        $customer = $this->makeCustomer();

        $this->asCustomer($customer)->getJson('/api/v1/admin/orders')->assertStatus(403);
    }

    /** Staff without an allowed role are rejected too. */
    public function test_staff_without_allowed_role_cannot_access_admin_orders(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER_SERVICE]);

        $this->asUser($user)->getJson('/api/v1/admin/orders')->assertStatus(403);
    }

    /** Store managers are allowed. Also proves User::createToken() exists. */
    public function test_store_manager_can_access_admin_orders(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STORE_MANAGER]);

        $this->asUser($user)->getJson('/api/v1/admin/orders')->assertStatus(200);
    }

    /** Anonymous callers get 401, not 200. */
    public function test_admin_orders_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/orders')->assertStatus(401);
    }

    /** One customer must not be able to read another customer's order. */
    public function test_customer_cannot_read_another_customers_order(): void
    {
        $owner = $this->makeCustomer(['email' => 'owner@example.com', 'phone' => '+9614444444']);
        $intruder = $this->makeCustomer(['email' => 'intruder@example.com', 'phone' => '+9615555555']);

        $product = Product::factory()->create(['price' => 100, 'track_quantity' => false]);

        $this->asCustomer($owner)->postJson('/api/v1/orders', [
            'cart_items' => [['product_id' => $product->id, 'quantity' => 1]],
            'shipping' => ['address_line1' => '1 St', 'city' => 'Damascus', 'country' => 'SY'],
        ])->assertStatus(201);

        $orderId = \App\Models\Order::where('customer_id', $owner->id)->value('id');

        $this->asCustomer($intruder)->getJson('/api/v1/orders/' . $orderId)
            ->assertStatus(403);
    }

    /** Wishlist deletes are scoped to the caller. */
    public function test_wishlist_delete_is_scoped_to_the_owner(): void
    {
        $owner = $this->makeCustomer(['email' => 'w1@example.com', 'phone' => '+9616666666']);
        $intruder = $this->makeCustomer(['email' => 'w2@example.com', 'phone' => '+9617777777']);
        $product = Product::factory()->create();

        $this->asCustomer($owner)->postJson('/api/v1/wishlist', ['product_id' => $product->id])
            ->assertStatus(201);

        // The intruder's delete must not touch the owner's row.
        $this->asCustomer($intruder)->deleteJson('/api/v1/wishlist/' . $product->id)
            ->assertStatus(404);

        $this->assertDatabaseHas('wishlists', [
            'customer_id' => $owner->id,
            'product_id' => $product->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // Denial of service / input hardening
    // ---------------------------------------------------------------------

    /**
     * Cache keys were md5($request->all()), letting any client mint unlimited
     * distinct keys and flood the (database-backed) cache store.
     */
    public function test_unknown_query_parameters_do_not_expand_the_cache_key_space(): void
    {
        $fingerprintA = \App\Services\CacheService::requestFingerprint(
            \Illuminate\Http\Request::create('/x', 'GET', ['featured' => '1', 'junk' => 'a']),
            ['featured']
        );
        $fingerprintB = \App\Services\CacheService::requestFingerprint(
            \Illuminate\Http\Request::create('/x', 'GET', ['featured' => '1', 'junk' => 'b']),
            ['featured']
        );
        $fingerprintC = \App\Services\CacheService::requestFingerprint(
            \Illuminate\Http\Request::create('/x', 'GET', ['featured' => '0']),
            ['featured']
        );

        $this->assertSame($fingerprintA, $fingerprintB, 'unlisted params must not affect the key');
        $this->assertNotSame($fingerprintA, $fingerprintC, 'listed params must still affect the key');
    }

    /** `?limit=0` divided by zero in ShopController::fetchProducts. */
    public function test_shop_products_survives_zero_limit(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);

        $this->getJson('/api/v1/shop/products?limit=0')->assertStatus(200);
    }

    /** `?limit=999999` used to return the entire catalogue in one response. */
    public function test_shop_products_clamps_an_oversized_limit(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);

        $this->getJson('/api/v1/shop/products?limit=999999')
            ->assertStatus(200)
            ->assertJsonPath('meta.pagination.limit', 60);
    }

    /** A negative page produced a negative SQL OFFSET. */
    public function test_shop_products_survives_a_negative_page(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);

        $this->getJson('/api/v1/shop/products?page=-5')->assertStatus(200);
    }

    /** An array-valued search term reached the query builder and 500'd. */
    public function test_products_index_survives_array_valued_search(): void
    {
        Product::factory()->create(['is_active' => true]);

        $this->getJson('/api/v1/products?search[]=a&search[]=b')->assertStatus(200);
    }

    /** An invalid sort direction made orderBy() throw. */
    public function test_products_index_survives_invalid_sort_order(): void
    {
        Product::factory()->create(['is_active' => true]);

        $this->getJson('/api/v1/products?sort_by=price&sort_order=; DROP TABLE products')
            ->assertStatus(200);

        $this->assertDatabaseCount('products', 1);
    }

    /** `?per_page=0` returned every row. */
    public function test_products_index_clamps_per_page(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);

        $this->getJson('/api/v1/products?per_page=0')
            ->assertStatus(200)
            ->assertJsonPath('meta.pagination.limit', 15);

        $this->getJson('/api/v1/products?per_page=99999')
            ->assertStatus(200)
            ->assertJsonPath('meta.pagination.limit', 100);
    }

    /** Review listing must clamp `limit` rather than dumping the table. */
    public function test_reviews_index_clamps_limit(): void
    {
        $this->getJson('/api/v1/reviews?limit=999999')->assertStatus(200);
    }
}
