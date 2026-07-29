<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function validCheckoutPayload(array $items, array $overrides = []): array
    {
        return array_merge([
            'items' => $items,
            'shipping_address' => [
                'first_name' => 'Test',
                'last_name' => 'User',
                'address' => '123 St',
                'city' => 'Riyadh',
                'country' => 'Saudi Arabia',
                'phone' => '+1234567890',
            ],
            'payment_method' => 'cash',
        ], $overrides);
    }

    private function asCustomer(Customer $customer): self
    {
        $token = $customer->createToken('test-token')->plainTextToken;
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function test_checkout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/cart/checkout', $this->validCheckoutPayload([
            ['id' => 1, 'quantity' => 1, 'price' => 100],
        ]));

        $response->assertStatus(401);
    }

    public function test_checkout_creates_order_for_authenticated_customer(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'price' => 100,
            'quantity' => 10,
            'track_quantity' => false,
        ]);

        $response = $this->asCustomer($customer)->postJson('/api/v1/cart/checkout', $this->validCheckoutPayload([
            ['id' => $product->id, 'quantity' => 1, 'price' => 100],
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'order_id',
                    'total',
                    'subtotal',
                    'tax',
                    'discount',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('orders', ['customer_id' => $customer->id]);
    }

    public function test_checkout_deducts_inventory_when_track_quantity(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'price' => 50,
            'quantity' => 10,
            'track_quantity' => true,
        ]);

        $response = $this->asCustomer($customer)->postJson('/api/v1/cart/checkout', $this->validCheckoutPayload([
            ['id' => $product->id, 'quantity' => 3, 'price' => 50],
        ]));

        $response->assertStatus(200);
        $this->assertEquals(7, $product->fresh()->quantity);
    }

    public function test_checkout_fails_when_insufficient_stock(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'price' => 50,
            'quantity' => 2,
            'track_quantity' => true,
        ]);

        $response = $this->asCustomer($customer)->postJson('/api/v1/cart/checkout', $this->validCheckoutPayload([
            ['id' => $product->id, 'quantity' => 5, 'price' => 50],
        ]));

        $response->assertStatus(422);
        $this->assertEquals(2, $product->fresh()->quantity);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_applies_coupon_discount(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'quantity' => 10, 'track_quantity' => false]);
        $coupon = Coupon::factory()->percentage(20)->create(['code' => 'SAVE20']);

        $response = $this->asCustomer($customer)->postJson('/api/v1/cart/checkout', $this->validCheckoutPayload(
            [['id' => $product->id, 'quantity' => 1, 'price' => 100]],
            ['coupon_code' => 'SAVE20']
        ));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(20, $data['discount']);
    }

    public function test_checkout_fails_for_invalid_coupon(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'quantity' => 10, 'track_quantity' => false]);

        $response = $this->asCustomer($customer)->postJson('/api/v1/cart/checkout', $this->validCheckoutPayload(
            [['id' => $product->id, 'quantity' => 1, 'price' => 100]],
            ['coupon_code' => 'INVALID99']
        ));

        $response->assertStatus(422);
    }

    public function test_checkout_fails_for_expired_coupon(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'quantity' => 10, 'track_quantity' => false]);
        Coupon::factory()->create(['code' => 'EXPIRED', 'usage_limit' => 1, 'used_count' => 1]);

        $response = $this->asCustomer($customer)->postJson('/api/v1/cart/checkout', $this->validCheckoutPayload(
            [['id' => $product->id, 'quantity' => 1, 'price' => 100]],
            ['coupon_code' => 'EXPIRED']
        ));

        $response->assertStatus(422);
    }

    public function test_checkout_clears_cart_after_success(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'quantity' => 10, 'track_quantity' => false]);

        $cart = Cart::create(['customer_id' => $customer->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
        ]);

        $response = $this->asCustomer($customer)->postJson('/api/v1/cart/checkout', $this->validCheckoutPayload([
            ['id' => $product->id, 'quantity' => 1, 'price' => 100],
        ]));

        $response->assertStatus(200);
        $this->assertEquals(0, $cart->fresh()->items()->count());
    }

    public function test_checkout_fails_when_coupon_already_used_by_customer(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'quantity' => 10, 'track_quantity' => false]);
        $coupon = Coupon::factory()->create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'usage_limit_per_customer' => 1,
        ]);

        $order = \App\Models\Order::factory()->create(['customer_id' => $customer->id]);
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'discount_amount' => 10,
            'order_total' => 100,
            'order_total_after_discount' => 90,
            'coupon_code' => $coupon->code,
        ]);

        $response = $this->asCustomer($customer)->postJson('/api/v1/cart/checkout', $this->validCheckoutPayload(
            [['id' => $product->id, 'quantity' => 1, 'price' => 100]],
            ['coupon_code' => 'WELCOME10']
        ));

        $response->assertStatus(422);
    }

    public function test_second_checkout_fails_when_stock_exhausted(): void
    {
        $product = Product::factory()->create([
            'price' => 50,
            'quantity' => 1,
            'track_quantity' => true,
        ]);

        $customer1 = Customer::factory()->create();
        $response1 = $this->asCustomer($customer1)->postJson('/api/v1/cart/checkout', $this->validCheckoutPayload([
            ['id' => $product->id, 'quantity' => 1, 'price' => 50],
        ]));
        $response1->assertStatus(200);

        $customer2 = Customer::factory()->create();
        $response2 = $this->asCustomer($customer2)->postJson('/api/v1/cart/checkout', $this->validCheckoutPayload([
            ['id' => $product->id, 'quantity' => 1, 'price' => 50],
        ]));

        $response2->assertStatus(422);
        $this->assertEquals(0, $product->fresh()->quantity);
        $this->assertDatabaseCount('orders', 1);
    }
}
