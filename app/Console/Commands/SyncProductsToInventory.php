<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProductsToInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync-products 
                            {--force : Force sync even if inventory records exist}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products to inventory system by creating inventory records for products without inventory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting product to inventory sync...');
        
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No changes will be made');
        }
        
        // Get or create default locations
        $defaultBranch = $this->getOrCreateDefaultBranch($dryRun);
        $defaultWarehouse = $this->getOrCreateDefaultWarehouse($dryRun);
        
        if (!$defaultBranch || !$defaultWarehouse) {
            $this->error('❌ Failed to create default locations');
            return 1;
        }
        
        $this->info("📍 Using Branch: {$defaultBranch->name} (ID: {$defaultBranch->id})");
        $this->info("🏭 Using Warehouse: {$defaultWarehouse->name} (ID: {$defaultWarehouse->id})");
        
        // Get products that need inventory sync
        $productsQuery = Product::where('track_quantity', true)
            ->where('is_digital', false);
            
        if (!$force) {
            $productsQuery->whereDoesntHave('inventory');
        }
        
        $products = $productsQuery->get();
        
        if ($products->isEmpty()) {
            $this->info('✅ No products need inventory sync');
            return 0;
        }
        
        $this->info("📦 Found {$products->count()} products to sync");
        
        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->start();
        
        $syncedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        foreach ($products as $product) {
            try {
                if ($dryRun) {
                    $this->line("\n🧪 Would sync product: {$product->name} (SKU: {$product->sku})");
                    $syncedCount++;
                } else {
                    $result = $this->syncProductToInventory($product, $defaultBranch, $defaultWarehouse, $force);
                    
                    if ($result['success']) {
                        $syncedCount++;
                        Log::info('Product synced to inventory via command', [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'branch_quantity' => $result['branch_quantity'],
                            'warehouse_quantity' => $result['warehouse_quantity'],
                        ]);
                    } else {
                        $skippedCount++;
                        $this->error("\n❌ Failed to sync product: {$product->name} - {$result['error']}");
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("\n❌ Error syncing product {$product->name}: {$e->getMessage()}");
                Log::error('Error syncing product to inventory', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Summary
        $this->info('📊 Sync Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['✅ Synced', $syncedCount],
                ['⏭️ Skipped', $skippedCount],
                ['❌ Errors', $errorCount],
            ]
        );
        
        if ($dryRun) {
            $this->info('🧪 This was a dry run. Use --force to actually sync products.');
        } else {
            $this->info('🎉 Product to inventory sync completed!');
        }
        
        return 0;
    }
    
    /**
     * Sync a single product to inventory
     */
    private function syncProductToInventory(Product $product, Branch $branch, Warehouse $warehouse, bool $force = false): array
    {
        try {
            // Check if inventory already exists
            if (!$force) {
                $existingInventory = Inventory::where('product_id', $product->id)->exists();
                if ($existingInventory) {
                    return ['success' => false, 'error' => 'Inventory already exists'];
                }
            }
            
            // Create branch inventory
            $branchInventory = Inventory::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'location_type' => 'App\Models\Branch',
                    'location_id' => $branch->id,
                ],
                [
                    'quantity' => $product->quantity,
                    'reserved_quantity' => 0,
                    'available_quantity' => $product->quantity,
                    'min_quantity' => $product->min_quantity ?? 10,
                    'max_quantity' => null,
                    'cost_price' => $product->cost_price,
                    'batch_number' => 'BR-' . strtoupper(substr($product->sku ?? 'PROD', 0, 8)) . '-' . date('Ymd'),
                    'expiry_date' => null,
                    'rack_location' => 'A-01',
                    'shelf_location' => 'S-01',
                    'notes' => "Synced from product: {$product->name}",
                    'is_active' => $product->is_active,
                ]
            );
            
            // Create warehouse inventory (20% of branch quantity)
            $warehouseQuantity = max(1, intval($product->quantity * 0.2));
            $warehouseInventory = Inventory::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'location_type' => 'App\Models\Warehouse',
                    'location_id' => $warehouse->id,
                ],
                [
                    'quantity' => $warehouseQuantity,
                    'reserved_quantity' => 0,
                    'available_quantity' => $warehouseQuantity,
                    'min_quantity' => $product->min_quantity ?? 20,
                    'max_quantity' => null,
                    'cost_price' => $product->cost_price,
                    'batch_number' => 'WH-' . strtoupper(substr($product->sku ?? 'PROD', 0, 8)) . '-' . date('Ymd'),
                    'expiry_date' => null,
                    'rack_location' => 'W-01',
                    'shelf_location' => 'WS-01',
                    'notes' => "Warehouse stock for product: {$product->name}",
                    'is_active' => $product->is_active,
                ]
            );
            
            return [
                'success' => true,
                'branch_quantity' => $product->quantity,
                'warehouse_quantity' => $warehouseQuantity,
                'branch_inventory_id' => $branchInventory->id,
                'warehouse_inventory_id' => $warehouseInventory->id,
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get or create default branch
     */
    private function getOrCreateDefaultBranch(bool $dryRun = false): ?Branch
    {
        $branch = Branch::where('code', 'MAIN')->first();
        
        if (!$branch && !$dryRun) {
            $branch = Branch::create([
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
                'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            ]);
            
            $this->info("✅ Created default branch: {$branch->name}");
        } elseif (!$branch && $dryRun) {
            $this->info("🧪 Would create default branch: الفرع الرئيسي");
            return null; // Return null for dry run
        }
        
        return $branch;
    }
    
    /**
     * Get or create default warehouse
     */
    private function getOrCreateDefaultWarehouse(bool $dryRun = false): ?Warehouse
    {
        $warehouse = Warehouse::where('code', 'MAIN-WH')->first();
        
        if (!$warehouse && !$dryRun) {
            $warehouse = Warehouse::create([
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
            ]);
            
            $this->info("✅ Created default warehouse: {$warehouse->name}");
        } elseif (!$warehouse && $dryRun) {
            $this->info("🧪 Would create default warehouse: المستودع الرئيسي");
            return null; // Return null for dry run
        }
        
        return $warehouse;
    }
}