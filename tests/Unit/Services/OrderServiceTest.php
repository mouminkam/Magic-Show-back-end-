<?php

namespace Tests\Unit\Services;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
    }

    // ─── placeOrder helpers ────────────────────────────────────────────────────

    private function placeOrderData(array $items, array $overrides = []): array
    {
        return array_merge([
            'cart_items' => $items,
            'shipping'   => [
                'address_line1' => '123 Main St',
                'city'          => 'Damascus',
                'country'       => 'Syria',
            ],
        ], $overrides);
    }

    // ─── placeOrder ────────────────────────────────────────────────────────────

    #[Test]
    public function place_order_creates_order_with_db_prices_not_client_prices(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create([
            'price'          => 1000,
            'sale_price'     => null,
            'quantity'       => 10,
            'track_quantity' => false,
        ]);

        $this->actingAs($customer, 'sanctum');

        // Intentionally pass a bogus price in cart_items – service must ignore it
        $data = $this->placeOrderData([[
            'product_id' => $product->id,
            'quantity'   => 2,
            'price'      => 1, // Client-supplied garbage price
        ]]);

        $order = $this->orderService->placeOrder($data);

        // Backend uses DB price (1000) × 2 = 2000, NOT client price
        $this->assertEquals(2000, (float) $order->subtotal);
        $this->assertEquals(2000, (float) $order->total_amount);
    }

    #[Test]
    public function place_order_uses_sale_price_when_available(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create([
            'price'          => 1000,
            'sale_price'     => 800,
            'quantity'       => 5,
            'track_quantity' => false,
        ]);

        $this->actingAs($customer, 'sanctum');

        $data  = $this->placeOrderData([['product_id' => $product->id, 'quantity' => 1]]);
        $order = $this->orderService->placeOrder($data);

        $this->assertEquals(800, (float) $order->subtotal);
    }

    #[Test]
    public function place_order_produces_zero_tax(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create([
            'price'          => 500,
            'quantity'       => 5,
            'track_quantity' => false,
        ]);

        $this->actingAs($customer, 'sanctum');

        $data  = $this->placeOrderData([['product_id' => $product->id, 'quantity' => 1]]);
        $order = $this->orderService->placeOrder($data);

        $this->assertEquals(0, (float) $order->tax_amount);
    }

    #[Test]
    public function place_order_sets_currency_to_syp(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create([
            'price'          => 500,
            'quantity'       => 5,
            'track_quantity' => false,
        ]);

        $this->actingAs($customer, 'sanctum');

        $data  = $this->placeOrderData([['product_id' => $product->id, 'quantity' => 1]]);
        $order = $this->orderService->placeOrder($data);

        $this->assertEquals('SYP', $order->currency);
    }

    #[Test]
    public function place_order_throws_when_product_not_found(): void
    {
        $customer = Customer::factory()->create();
        $this->actingAs($customer, 'sanctum');

        $data = $this->placeOrderData([['product_id' => 99999, 'quantity' => 1]]);

        $this->expectException(RuntimeException::class);

        $this->orderService->placeOrder($data);
    }

    #[Test]
    public function place_order_throws_for_insufficient_stock(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create([
            'price'          => 200,
            'quantity'       => 2,
            'track_quantity' => true,
            'allow_backorder' => false,
        ]);

        $this->actingAs($customer, 'sanctum');

        $data = $this->placeOrderData([['product_id' => $product->id, 'quantity' => 5]]);

        $this->expectException(RuntimeException::class);

        $this->orderService->placeOrder($data);
    }

    #[Test]
    public function place_order_applies_percentage_coupon(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create([
            'price'          => 1000,
            'quantity'       => 10,
            'track_quantity' => false,
        ]);
        Coupon::factory()->create([
            'code'        => 'SAVE10',
            'type'        => 'percentage',
            'value'       => 10,
            'is_active'   => true,
            'usage_limit' => null,
        ]);

        $this->actingAs($customer, 'sanctum');

        $data  = $this->placeOrderData(
            [['product_id' => $product->id, 'quantity' => 1]],
            ['coupon_code' => 'SAVE10']
        );
        $order = $this->orderService->placeOrder($data);

        $this->assertEquals(100, (float) $order->discount_amount);
        $this->assertEquals(900, (float) $order->total_amount);
        $this->assertEquals('SAVE10', $order->coupon_code);
    }

    #[Test]
    public function place_order_throws_for_invalid_coupon_code(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create([
            'price'          => 500,
            'quantity'       => 5,
            'track_quantity' => false,
        ]);

        $this->actingAs($customer, 'sanctum');

        $data = $this->placeOrderData(
            [['product_id' => $product->id, 'quantity' => 1]],
            ['coupon_code' => 'FAKE_CODE']
        );

        $this->expectException(RuntimeException::class);

        $this->orderService->placeOrder($data);
    }

    #[Test]
    public function place_order_generates_unique_order_number(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create([
            'price'          => 300,
            'quantity'       => 10,
            'track_quantity' => false,
        ]);

        $this->actingAs($customer, 'sanctum');

        $data   = $this->placeOrderData([['product_id' => $product->id, 'quantity' => 1]]);
        $order1 = $this->orderService->placeOrder($data);
        $order2 = $this->orderService->placeOrder($data);

        $this->assertNotEquals($order1->order_number, $order2->order_number);
        $this->assertStringStartsWith('ORD-', $order1->order_number);
    }

    #[Test]
    public function place_order_stores_product_attributes_size_and_color(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create([
            'price'          => 250,
            'quantity'       => 10,
            'track_quantity' => false,
        ]);

        $this->actingAs($customer, 'sanctum');

        $data = $this->placeOrderData([[
            'product_id' => $product->id,
            'quantity'   => 1,
            'size'       => '42',
            'color'      => 'Red',
        ]]);

        $order = $this->orderService->placeOrder($data);
        $item  = $order->orderItems()->first();

        $attrs = $item->product_attributes;
        $this->assertEquals('42', $attrs['size'] ?? null);
        $this->assertEquals('Red', $attrs['color'] ?? null);
    }

    // ─── listOrdersForCustomer ─────────────────────────────────────────────────

    #[Test]
    public function list_orders_for_customer_returns_only_own_orders(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $product   = Product::factory()->create([
            'price'          => 100,
            'quantity'       => 10,
            'track_quantity' => false,
        ]);

        $this->actingAs($customer1, 'sanctum');
        $this->orderService->placeOrder($this->placeOrderData([['product_id' => $product->id, 'quantity' => 1]]));

        $this->actingAs($customer2, 'sanctum');
        $this->orderService->placeOrder($this->placeOrderData([['product_id' => $product->id, 'quantity' => 1]]));

        $orders = $this->orderService->listOrdersForCustomer($customer1->id);

        $this->assertCount(1, $orders);
        $this->assertEquals($customer1->id, $orders->first()->customer_id);
    }
}
