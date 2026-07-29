<?php

namespace Tests\Unit\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = app(InventoryService::class);
    }

    #[Test]
    public function deduct_for_order_decrements_product_quantity_when_no_inventory(): void
    {
        $product = Product::factory()->create([
            'quantity' => 10,
            'track_quantity' => true,
        ]);

        DB::transaction(function () use ($product) {
            $this->inventoryService->deductForOrder($product, 3);
        });

        $this->assertEquals(7, $product->fresh()->quantity);
    }

    #[Test]
    public function deduct_for_order_decrements_inventory_when_exists(): void
    {
        $product = Product::factory()->create(['quantity' => 100, 'track_quantity' => true]);
        $warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
            'code' => 'WH1',
            'address' => '123 Test St',
            'city' => 'Test City',
            'country' => 'Test Country',
            'is_active' => true,
        ]);
        $inventory = Inventory::create([
            'product_id' => $product->id,
            'location_type' => Warehouse::class,
            'location_id' => $warehouse->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'available_quantity' => 10,
            'min_quantity' => 0,
            'is_active' => true,
        ]);

        DB::transaction(function () use ($product) {
            $this->inventoryService->deductForOrder($product, 3);
        });

        $this->assertEquals(7, $inventory->fresh()->quantity);
        $this->assertEquals(100, $product->fresh()->quantity);
    }

    #[Test]
    public function deduct_for_order_throws_when_insufficient_stock(): void
    {
        $product = Product::factory()->create([
            'quantity' => 2,
            'track_quantity' => true,
        ]);

        $this->expectException(\RuntimeException::class);

        DB::transaction(function () use ($product) {
            $this->inventoryService->deductForOrder($product, 5);
        });
    }

    #[Test]
    public function deduct_for_order_skips_when_track_quantity_false(): void
    {
        $product = Product::factory()->create([
            'quantity' => 10,
            'track_quantity' => false,
        ]);

        DB::transaction(function () use ($product) {
            $this->inventoryService->deductForOrder($product, 5);
        });

        $this->assertEquals(10, $product->fresh()->quantity);
    }

    #[Test]
    public function deduct_for_order_skips_when_allow_backorder(): void
    {
        $product = Product::factory()->create([
            'quantity' => 2,
            'track_quantity' => true,
            'allow_backorder' => true,
        ]);

        DB::transaction(function () use ($product) {
            $this->inventoryService->deductForOrder($product, 5);
        });

        $this->assertEquals(2, $product->fresh()->quantity);
    }
}
