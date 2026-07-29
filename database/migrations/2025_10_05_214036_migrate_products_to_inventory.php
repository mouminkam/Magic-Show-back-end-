<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Warehouse;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migration will run silently
        
        // Get or create default locations
        $defaultBranch = $this->getOrCreateDefaultBranch();
        $defaultWarehouse = $this->getOrCreateDefaultWarehouse();
        
        // Get products with quantity > 0
        $products = Product::where('quantity', '>', 0)->get();
        
        if ($products->isEmpty()) {
            return;
        }
        
        $migratedCount = 0;
        $skippedCount = 0;
        
        foreach ($products as $product) {
            try {
                // Check if inventory already exists
                $existingInventory = DB::table('inventory')
                    ->where('product_id', $product->id)
                    ->where('location_type', 'App\Models\Branch')
                    ->where('location_id', $defaultBranch->id)
                    ->first();
                
                if ($existingInventory) {
                    $skippedCount++;
                    continue;
                }
                
                // Create inventory record for branch
                DB::table('inventory')->insert([
                    'product_id' => $product->id,
                    'location_type' => 'App\Models\Branch',
                    'location_id' => $defaultBranch->id,
                    'quantity' => $product->quantity,
                    'reserved_quantity' => 0,
                    'available_quantity' => $product->quantity,
                    'min_quantity' => $product->min_quantity ?? 10,
                    'max_quantity' => null,
                    'cost_price' => $product->cost_price,
                    'batch_number' => 'MIG-' . strtoupper(substr($product->sku ?? 'PROD', 0, 8)),
                    'expiry_date' => null,
                    'rack_location' => 'A-01',
                    'shelf_location' => 'S-01',
                    'notes' => "Migrated from products table - Original quantity: {$product->quantity}",
                    'is_active' => $product->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Create inventory record for warehouse (50% of branch quantity)
                $warehouseQuantity = max(1, intval($product->quantity * 0.5));
                DB::table('inventory')->insert([
                    'product_id' => $product->id,
                    'location_type' => 'App\Models\Warehouse',
                    'location_id' => $defaultWarehouse->id,
                    'quantity' => $warehouseQuantity,
                    'reserved_quantity' => 0,
                    'available_quantity' => $warehouseQuantity,
                    'min_quantity' => $product->min_quantity ?? 20,
                    'max_quantity' => null,
                    'cost_price' => $product->cost_price,
                    'batch_number' => 'WH-' . strtoupper(substr($product->sku ?? 'PROD', 0, 8)),
                    'expiry_date' => null,
                    'rack_location' => 'W-01',
                    'shelf_location' => 'WS-01',
                    'notes' => "Warehouse stock - Migrated from products table",
                    'is_active' => $product->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $migratedCount++;
                
            } catch (\Exception $e) {
                $skippedCount++;
            }
        }
        
        // Migration completed silently
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete inventory records created by this migration
        DB::table('inventory')
            ->where('notes', 'like', '%Migrated from products table%')
            ->delete();
        
        // Delete default locations if they were created
        DB::table('branches')->where('code', 'MAIN')->delete();
        DB::table('warehouses')->where('code', 'MAIN-WH')->delete();
    }
    
    /**
     * Get or create default branch
     */
    private function getOrCreateDefaultBranch(): object
    {
        $branch = DB::table('branches')->where('code', 'MAIN')->first();
        
        if (!$branch) {
            $branchId = DB::table('branches')->insertGetId([
                'name' => 'الفرع الرئيسي',
                'code' => 'MAIN',
                'description' => 'الفرع الرئيسي - تم إنشاؤه تلقائياً',
                'address' => 'الرياض، المملكة العربية السعودية',
                'city' => 'الرياض',
                'state' => 'منطقة الرياض',
                'country' => 'المملكة العربية السعودية',
                'postal_code' => '12345',
                'phone' => '+966-11-123-4567',
                'email' => 'main@magicshoe.com',
                'manager_name' => 'مدير الفرع الرئيسي',
                'is_active' => true,
                'opening_time' => '09:00:00',
                'closing_time' => '22:00:00',
                'working_days' => json_encode(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $branch = (object) ['id' => $branchId];
        }
        
        return $branch;
    }
    
    /**
     * Get or create default warehouse
     */
    private function getOrCreateDefaultWarehouse(): object
    {
        $warehouse = DB::table('warehouses')->where('code', 'MAIN-WH')->first();
        
        if (!$warehouse) {
            $warehouseId = DB::table('warehouses')->insertGetId([
                'name' => 'المستودع الرئيسي',
                'code' => 'MAIN-WH',
                'description' => 'المستودع الرئيسي - تم إنشاؤه تلقائياً',
                'address' => 'الرياض، المملكة العربية السعودية',
                'city' => 'الرياض',
                'state' => 'منطقة الرياض',
                'country' => 'المملكة العربية السعودية',
                'postal_code' => '12345',
                'phone' => '+966-11-987-6543',
                'email' => 'warehouse@magicshoe.com',
                'manager_name' => 'مدير المستودع',
                'is_active' => true,
                'type' => 'main',
                'capacity' => 10000,
                'capacity_unit' => 'sqft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $warehouse = (object) ['id' => $warehouseId];
        }
        
        return $warehouse;
    }
};