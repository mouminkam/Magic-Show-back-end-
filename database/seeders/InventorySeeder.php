<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Warehouse;

class InventorySeeder extends Seeder
{
    public function run()
    {
        Inventory::truncate();
        
        $products = Product::all();
        $branches = Branch::all();
        $warehouses = Warehouse::all();
        
        foreach ($products as $product) {
            // توزيع عشوائي على الفروع
            foreach ($branches as $branch) {
                $quantity = rand(5, 25); // كمية عشوائية بين 5 و 25
                Inventory::create([
                    'product_id' => $product->id,
                    'location_type' => Branch::class,
                    'location_id' => $branch->id,
                    'quantity' => $quantity,
                    'available_quantity' => $quantity,
                    'reserved_quantity' => 0,
                    'min_quantity' => 5,
                    'max_quantity' => 100,
                    'cost_price' => $product->cost_price,
                    'batch_number' => 'BATCH-' . date('Y') . '-' . str_pad($product->id, 3, '0', STR_PAD_LEFT),
                    'expiry_date' => null,
                    'rack_location' => 'R' . rand(1, 10) . '-' . rand(1, 20),
                    'shelf_location' => 'S' . rand(1, 5) . '-' . rand(1, 10),
                    'notes' => 'مخزون تلقائي',
                    'is_active' => true,
                ]);
            }
            
            // توزيع عشوائي على المخازن
            foreach ($warehouses as $warehouse) {
                $quantity = rand(10, 50); // كمية أكبر في المخازن
                Inventory::create([
                    'product_id' => $product->id,
                    'location_type' => Warehouse::class,
                    'location_id' => $warehouse->id,
                    'quantity' => $quantity,
                    'available_quantity' => $quantity,
                    'reserved_quantity' => 0,
                    'min_quantity' => 10,
                    'max_quantity' => 200,
                    'cost_price' => $product->cost_price,
                    'batch_number' => 'BATCH-' . date('Y') . '-' . str_pad($product->id, 3, '0', STR_PAD_LEFT),
                    'expiry_date' => null,
                    'rack_location' => 'R' . rand(1, 15) . '-' . rand(1, 25),
                    'shelf_location' => 'S' . rand(1, 8) . '-' . rand(1, 15),
                    'notes' => 'مخزون تلقائي',
                    'is_active' => true,
                ]);
            }
        }
        
        $this->command->info('✅ تم إنشاء بيانات المخزون بنجاح');
    }
}