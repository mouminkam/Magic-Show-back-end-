<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryService
{
    /**
     * Get paginated inventory with filters
     */
    public function getInventory(Request $request): LengthAwarePaginator
    {
        $totalInventory = Inventory::count();
        $totalProducts = Product::where('track_quantity', true)->where('is_digital', false)->count();
        
        // If no inventory records exist, return products as inventory
        if ($totalInventory === 0) {
            \Log::info('No inventory records found, falling back to products table', [
                'total_products' => $totalProducts,
                'request_params' => $request->all()
            ]);
            return $this->getProductBasedInventory($request);
        }
        
        // Check if there are products without inventory records
        $productsWithoutInventory = Product::where('track_quantity', true)
            ->where('is_digital', false)
            ->whereDoesntHave('inventory')
            ->count();
            
        if ($productsWithoutInventory > 0) {
            \Log::info('Found products without inventory records', [
                'products_without_inventory' => $productsWithoutInventory,
                'total_inventory_records' => $totalInventory,
                'total_products' => $totalProducts
            ]);
        }
        
        $query = Inventory::with(['product'])
            ->select('inventory.*');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('location_type')) {
            $query->where('location_type', $request->get('location_type'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->get('location_id'));
        }

        if ($request->filled('stock_status')) {
            $status = $request->get('stock_status');
            switch ($status) {
                case 'in_stock':
                    $query->where('available_quantity', '>', 0);
                    break;
                case 'low_stock':
                    $query->whereColumn('available_quantity', '<=', 'min_quantity');
                    break;
                case 'out_of_stock':
                    $query->where('available_quantity', '=', 0);
                    break;
            }
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'updated_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($request->get('per_page', 15));
    }
    
    /**
     * Get inventory data from products table (fallback)
     */
    private function getProductBasedInventory(Request $request): LengthAwarePaginator
    {
        $query = Product::select('products.*');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        // Apply stock status filter
        if ($request->filled('stock_status')) {
            $status = $request->get('stock_status');
            switch ($status) {
                case 'in_stock':
                    $query->where('quantity', '>', 0);
                    break;
                case 'low_stock':
                    $query->where('quantity', '>', 0)
                          ->whereColumn('quantity', '<=', 'min_quantity');
                    break;
                case 'out_of_stock':
                    $query->where('quantity', '=', 0);
                    break;
            }
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'updated_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Map inventory fields to product fields
        $sortField = match($sortBy) {
            'available_quantity' => 'quantity',
            'quantity' => 'quantity',
            'min_quantity' => 'min_quantity',
            'updated_at' => 'updated_at',
            'created_at' => 'created_at',
            default => 'updated_at'
        };
        
        $query->orderBy($sortField, $sortOrder);

        $inventoryPaginator = $query->paginate($request->get('per_page', 15));
        
        // If there are products without inventory, append them to the results
        if ($productsWithoutInventory > 0) {
            $productsWithoutInventoryData = $this->getProductsWithoutInventory($request);
            $inventoryPaginator = $this->mergeInventoryWithProducts($inventoryPaginator, $productsWithoutInventoryData);
        }
        
        return $inventoryPaginator;
    }

    /**
     * Create new inventory record
     */
    public function createInventory(array $data): Inventory
    {
        return DB::transaction(function () use ($data) {
            // Validate location exists
            $this->validateLocation($data['location_type'], $data['location_id']);

            // Check if inventory already exists for this product and location
            $existingInventory = Inventory::where('product_id', $data['product_id'])
                ->where('location_type', $data['location_type'])
                ->where('location_id', $data['location_id'])
                ->first();

            if ($existingInventory) {
                throw new \Exception('المخزون موجود بالفعل لهذا المنتج في هذا الموقع');
            }

            $inventory = Inventory::create([
                'product_id' => $data['product_id'],
                'location_type' => $data['location_type'],
                'location_id' => $data['location_id'],
                'quantity' => $data['quantity'] ?? 0,
                'reserved_quantity' => $data['reserved_quantity'] ?? 0,
                'min_quantity' => $data['min_quantity'] ?? 0,
                'max_quantity' => $data['max_quantity'] ?? null,
                'batch_number' => $data['batch_number'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'is_active' => $data['status'] === 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            // Log the creation
            Log::info('تم إنشاء مخزون جديد', [
                'inventory_id' => $inventory->id,
                'product_id' => $inventory->product_id,
                'location_type' => $inventory->location_type,
                'location_id' => $inventory->location_id,
            ]);

            return $inventory->load(['product', 'location']);
        });
    }

    /**
     * Update inventory record
     */
    public function updateInventory(Inventory $inventory, array $data): Inventory
    {
        return DB::transaction(function () use ($inventory, $data) {
            // Validate location if changed
            if (isset($data['location_type']) || isset($data['location_id'])) {
                $locationType = $data['location_type'] ?? $inventory->location_type;
                $locationId = $data['location_id'] ?? $inventory->location_id;
                $this->validateLocation($locationType, $locationId);
            }

            $inventory->update($data);

            // Log the update
            Log::info('تم تحديث المخزون', [
                'inventory_id' => $inventory->id,
                'changes' => $data,
            ]);

            return $inventory->load(['product', 'location']);
        });
    }

    /**
     * Delete inventory record
     */
    public function deleteInventory(Inventory $inventory): bool
    {
        return DB::transaction(function () use ($inventory) {
            // Check if inventory has reserved quantity
            if ($inventory->reserved_quantity > 0) {
                throw new \Exception('لا يمكن حذف المخزون الذي يحتوي على كمية محجوزة');
            }

            // Log the deletion
            Log::info('تم حذف المخزون', [
                'inventory_id' => $inventory->id,
                'product_id' => $inventory->product_id,
                'location_type' => $inventory->location_type,
                'location_id' => $inventory->location_id,
            ]);

            return $inventory->delete();
        });
    }

    /**
     * Bulk update inventory status
     */
    public function bulkUpdateStatus(array $inventoryIds, string $status): int
    {
        return DB::transaction(function () use ($inventoryIds, $status) {
            $count = Inventory::whereIn('id', $inventoryIds)->update(['is_active' => $status === 'active']);

            Log::info('تم تحديث حالة المخزون بالجملة', [
                'inventory_ids' => $inventoryIds,
                'status' => $status,
                'count' => $count,
            ]);

            return $count;
        });
    }

    /**
     * Bulk delete inventory records
     */
    public function bulkDelete(array $inventoryIds): int
    {
        return DB::transaction(function () use ($inventoryIds) {
            // Check for reserved quantities
            $reservedInventory = Inventory::whereIn('id', $inventoryIds)
                ->where('reserved_quantity', '>', 0)
                ->count();

            if ($reservedInventory > 0) {
                throw new \Exception('لا يمكن حذف المخزون الذي يحتوي على كمية محجوزة');
            }

            $count = Inventory::whereIn('id', $inventoryIds)->delete();

            Log::info('تم حذف المخزون بالجملة', [
                'inventory_ids' => $inventoryIds,
                'count' => $count,
            ]);

            return $count;
        });
    }

    /**
     * Transfer inventory between locations
     */
    public function transferInventory(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $fromInventory = Inventory::findOrFail($data['from_inventory_id']);
            $quantity = $data['quantity'];

            // Validate transfer quantity
            if ($quantity > $fromInventory->available_quantity) {
                throw new \Exception('الكمية المطلوب تحويلها أكبر من الكمية المتاحة');
            }

            // Validate destination location
            $this->validateLocation($data['to_location_type'], $data['to_location_id']);

            // Check if destination inventory exists
            $toInventory = Inventory::where('product_id', $fromInventory->product_id)
                ->where('location_type', $data['to_location_type'])
                ->where('location_id', $data['to_location_id'])
                ->first();

            if (!$toInventory) {
                // Create new inventory at destination
                $toInventory = Inventory::create([
                    'product_id' => $fromInventory->product_id,
                    'location_type' => $data['to_location_type'],
                    'location_id' => $data['to_location_id'],
                    'quantity' => $quantity,
                    'reserved_quantity' => 0,
                    'min_quantity' => $fromInventory->min_quantity,
                    'max_quantity' => $fromInventory->max_quantity,
                    'is_active' => true,
                ]);
            } else {
                // Update existing inventory
                $toInventory->increment('quantity', $quantity);
            }

            // Update source inventory
            $fromInventory->decrement('quantity', $quantity);

            // Log the transfer
            Log::info('تم تحويل المخزون', [
                'from_inventory_id' => $fromInventory->id,
                'to_inventory_id' => $toInventory->id,
                'quantity' => $quantity,
                'from_location' => $fromInventory->location_type . ':' . $fromInventory->location_id,
                'to_location' => $data['to_location_type'] . ':' . $data['to_location_id'],
            ]);

            return [
                'from_inventory' => $fromInventory->fresh(),
                'to_inventory' => $toInventory->fresh(),
                'quantity' => $quantity,
            ];
        });
    }

    /**
     * Get inventory statistics
     */
    public function getInventoryStats(): array
    {
        $totalInventory = Inventory::count();
        
        // If no inventory records exist, fallback to products
        if ($totalInventory === 0) {
            return $this->getProductBasedStats();
        }
        
        $activeInventory = Inventory::where('is_active', true)->count();
        $lowStockCount = Inventory::whereColumn('available_quantity', '<=', 'min_quantity')->count();
        $outOfStockCount = Inventory::where('available_quantity', 0)->count();

        $totalValue = Inventory::with('product')
            ->get()
            ->sum(function ($inventory) {
                return $inventory->available_quantity * ($inventory->product->price ?? 0);
            });

        $locationStats = Inventory::selectRaw('location_type, COUNT(*) as count')
            ->groupBy('location_type')
            ->get()
            ->pluck('count', 'location_type');

        return [
            'total_inventory' => $totalInventory,
            'active_inventory' => $activeInventory,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'total_value' => $totalValue,
            'location_stats' => $locationStats,
            'data_source' => 'inventory_table',
        ];
    }
    
    /**
     * Get stats based on products table (fallback when inventory is empty)
     */
    private function getProductBasedStats(): array
    {
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $lowStockCount = Product::where('quantity', '>', 0)
            ->whereColumn('quantity', '<=', 'min_quantity')
            ->count();
        $outOfStockCount = Product::where('quantity', 0)->count();
        
        $totalValue = Product::sum(function ($product) {
            return $product->quantity * $product->price;
        });

        return [
            'total_inventory' => $totalProducts,
            'active_inventory' => $activeProducts,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'total_value' => $totalValue,
            'location_stats' => ['products_table' => $totalProducts],
            'data_source' => 'products_table',
        ];
    }

    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts(): array
    {
        return Inventory::with(['product'])
            ->whereColumn('available_quantity', '<=', 'min_quantity')
            ->where('is_active', true)
            ->orderBy('available_quantity', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Search inventory
     */
    public function searchInventory(string $query): array
    {
        return Inventory::with(['product'])
            ->whereHas('product', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            })
            ->orWhere('batch_number', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Deduct inventory for an order. Uses lockForUpdate to prevent race conditions.
     * Call this inside a DB transaction.
     *
     * @param Product $product
     * @param int $quantity
     * @return void
     * @throws \RuntimeException when insufficient stock
     */
    public function deductForOrder(Product $product, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        // Skip if product doesn't track quantity or allows backorder
        if (!$product->track_quantity || $product->allow_backorder) {
            return;
        }

        if ($product->inventory()->exists()) {
            // Deduct from inventory table with lockForUpdate
            $remaining = $quantity;
            $records = Inventory::where('product_id', $product->id)
                ->where('available_quantity', '>', 0)
                ->orderBy('available_quantity', 'desc')
                ->lockForUpdate()
                ->get();

            foreach ($records as $record) {
                if ($remaining <= 0) {
                    break;
                }
                $toDeduct = min($record->available_quantity, $remaining);
                if ($toDeduct > 0 && $record->removeQuantity($toDeduct)) {
                    $remaining -= $toDeduct;
                }
            }

            if ($remaining > 0) {
                throw new \RuntimeException(
                    __('errors.insufficient_stock', ['name' => $product->name, 'available' => $product->fresh()->total_inventory_quantity])
                );
            }
        } else {
            // Fallback: deduct from product.quantity
            $updated = Product::where('id', $product->id)
                ->where('quantity', '>=', $quantity)
                ->lockForUpdate()
                ->decrement('quantity', $quantity);

            if (!$updated) {
                $available = $product->fresh()->quantity ?? 0;
                throw new \RuntimeException(
                    __('errors.insufficient_stock', ['name' => $product->name, 'available' => $available])
                );
            }
        }
    }

    /**
     * Return stock to inventory — the inverse of deductForOrder().
     *
     * Called when an order is cancelled. Without this, cancelling an order left
     * its stock permanently deducted and the catalogue slowly drifted towards
     * showing everything as out of stock.
     */
    public function restoreForOrder(Product $product, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        if (!$product->track_quantity || $product->allow_backorder) {
            return;
        }

        $record = Inventory::where('product_id', $product->id)
            ->orderByDesc('available_quantity')
            ->lockForUpdate()
            ->first();

        if ($record) {
            $record->addQuantity($quantity);

            return;
        }

        // Fallback: products not migrated to the inventory table.
        Product::where('id', $product->id)->lockForUpdate()->increment('quantity', $quantity);
    }

    /**
     * Validate location exists
     */
    private function validateLocation(string $locationType, int $locationId): void
    {
        if ($locationType === 'branch') {
            Branch::findOrFail($locationId);
        } elseif ($locationType === 'warehouse') {
            Warehouse::findOrFail($locationId);
        } else {
            throw new \Exception('نوع الموقع غير صحيح');
        }
    }

    /**
     * Get available locations for transfer
     */
    public function getAvailableLocations(int $productId, string $excludeLocationType = null, int $excludeLocationId = null): array
    {
        $locations = [];

        // Get branches
        if ($excludeLocationType !== 'branch' || $excludeLocationId === null) {
            $branches = Branch::where('is_active', true)->get();
            foreach ($branches as $branch) {
                if ($excludeLocationType === 'branch' && $excludeLocationId === $branch->id) {
                    continue;
                }
                $locations[] = [
                    'type' => 'branch',
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'type_label' => 'فرع',
                ];
            }
        }

        // Get warehouses
        if ($excludeLocationType !== 'warehouse' || $excludeLocationId === null) {
            $warehouses = Warehouse::where('is_active', true)->get();
            foreach ($warehouses as $warehouse) {
                if ($excludeLocationType === 'warehouse' && $excludeLocationId === $warehouse->id) {
                    continue;
                }
                $locations[] = [
                    'type' => 'warehouse',
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'type_label' => 'مخزن',
                ];
            }
        }

        return $locations;
    }

    /**
     * Get products without inventory records
     */
    private function getProductsWithoutInventory(Request $request): \Illuminate\Support\Collection
    {
        $query = Product::where('track_quantity', true)
            ->where('is_digital', false)
            ->whereDoesntHave('inventory');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        // Apply stock status filter
        if ($request->filled('stock_status')) {
            $status = $request->get('stock_status');
            switch ($status) {
                case 'in_stock':
                    $query->where('quantity', '>', 0);
                    break;
                case 'low_stock':
                    $query->where('quantity', '>', 0)
                          ->whereColumn('quantity', '<=', 'min_quantity');
                    break;
                case 'out_of_stock':
                    $query->where('quantity', '=', 0);
                    break;
            }
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        return $query->get()->map(function ($product) {
            return \App\Transformers\InventoryTransformer::transformProductToInventory($product);
        });
    }

    /**
     * Merge inventory records with products without inventory
     */
    private function mergeInventoryWithProducts(LengthAwarePaginator $inventoryPaginator, \Illuminate\Support\Collection $productsWithoutInventory): LengthAwarePaginator
    {
        $mergedItems = $inventoryPaginator->getCollection()->concat($productsWithoutInventory);
        
        return new LengthAwarePaginator(
            $mergedItems,
            $inventoryPaginator->total() + $productsWithoutInventory->count(),
            $inventoryPaginator->perPage(),
            $inventoryPaginator->currentPage(),
            ['path' => $inventoryPaginator->path()]
        );
    }
}
