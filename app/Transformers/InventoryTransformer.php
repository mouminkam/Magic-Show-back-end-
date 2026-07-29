<?php

namespace App\Transformers;

use App\Models\Product;
use App\Models\Inventory;

class InventoryTransformer
{
    /**
     * Transform a Product model to Inventory-like format
     */
    public static function productToInventory(Product $product): object
    {
        return (object) [
            'id' => $product->id,
            'product_id' => $product->id,
            'product' => $product,
            'quantity' => $product->quantity,
            'available_quantity' => $product->quantity,
            'reserved_quantity' => 0,
            'min_quantity' => $product->min_quantity ?? 10,
            'max_quantity' => null,
            'cost_price' => $product->cost_price,
            'batch_number' => 'PROD-' . strtoupper(substr($product->sku ?? 'ITEM', 0, 8)),
            'expiry_date' => null,
            'rack_location' => 'PRODUCT',
            'shelf_location' => 'MAIN',
            'notes' => 'Product inventory - managed via products table',
            'is_active' => $product->is_active,
            'location_type' => 'App\Models\Product',
            'location_id' => $product->id,
            'location_name' => 'Product Stock',
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
            
            // Add methods that the view expects
            'getStockStatusAttribute' => function() use ($product) {
                return $product->stock_status;
            },
            'getStockStatusLabelAttribute' => function() use ($product) {
                return $product->stock_status_label;
            },
            'getPhysicalLocationAttribute' => function() {
                return 'Product Stock - Main Location';
            },
            'getFormattedCostPriceAttribute' => function() use ($product) {
                return $product->cost_price ? number_format($product->cost_price, 2) . ' SAR' : null;
            },
        ];
    }

    /**
     * Transform a collection of Products to Inventory-like format
     */
    public static function productsToInventoryCollection($products)
    {
        return $products->map(function ($product) {
            return self::productToInventory($product);
        });
    }

    /**
     * Ensure inventory item has all required attributes for view
     */
    public static function ensureInventoryAttributes($item): object
    {
        // If it's already an Inventory model, return as is
        if ($item instanceof Inventory) {
            return $item;
        }

        // If it's a Product, transform it
        if ($item instanceof Product) {
            return self::productToInventory($item);
        }

        // If it's an object (from helper), ensure it has all methods
        if (is_object($item)) {
            return self::ensureObjectMethods($item);
        }

        return $item;
    }

    /**
     * Ensure object has all required methods for view compatibility
     */
    private static function ensureObjectMethods($item): object
    {
        // Add missing methods if they don't exist
        if (!isset($item->getStockStatusAttribute)) {
            $item->getStockStatusAttribute = function() use ($item) {
                if ($item->available_quantity > $item->min_quantity) {
                    return 'in_stock';
                } elseif ($item->available_quantity > 0) {
                    return 'low_stock';
                } else {
                    return 'out_of_stock';
                }
            };
        }

        if (!isset($item->getStockStatusLabelAttribute)) {
            $item->getStockStatusLabelAttribute = function() use ($item) {
                $status = $item->getStockStatusAttribute();
                return match($status) {
                    'in_stock' => 'متوفر',
                    'low_stock' => 'مخزون منخفض',
                    'out_of_stock' => 'نفد المخزون',
                    default => 'غير محدد'
                };
            };
        }

        if (!isset($item->getPhysicalLocationAttribute)) {
            $item->getPhysicalLocationAttribute = function() use ($item) {
                return $item->rack_location . ', ' . $item->shelf_location;
            };
        }

        if (!isset($item->getFormattedCostPriceAttribute)) {
            $item->getFormattedCostPriceAttribute = function() use ($item) {
                return $item->cost_price ? number_format($item->cost_price, 2) . ' SAR' : null;
            };
        }

        return $item;
    }

    /**
     * Get unified inventory display data
     */
    public static function getDisplayData($item): array
    {
        $item = self::ensureInventoryAttributes($item);

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'product_name' => $item->product->name ?? 'منتج غير محدد',
            'product_sku' => $item->product->sku ?? 'غير محدد',
            'quantity' => $item->quantity,
            'available_quantity' => $item->available_quantity,
            'reserved_quantity' => $item->reserved_quantity ?? 0,
            'min_quantity' => $item->min_quantity,
            'cost_price' => $item->cost_price,
            'formatted_cost_price' => $item->getFormattedCostPriceAttribute(),
            'batch_number' => $item->batch_number,
            'expiry_date' => $item->expiry_date,
            'physical_location' => $item->getPhysicalLocationAttribute(),
            'stock_status' => $item->getStockStatusAttribute(),
            'stock_status_label' => $item->getStockStatusLabelAttribute(),
            'location_name' => $item->location_name ?? 'موقع غير محدد',
            'is_active' => $item->is_active,
            'notes' => $item->notes,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'is_from_products_table' => $item->location_type === 'App\Models\Product',
        ];
    }

    /**
     * Transform paginated data for view
     */
    public static function transformPaginatedData($paginatedData): object
    {
        if ($paginatedData->isEmpty()) {
            return $paginatedData;
        }

        // Check if items are from products table
        $firstItem = $paginatedData->first();
        
        if ($firstItem instanceof Product || 
            (is_object($firstItem) && isset($firstItem->location_type) && $firstItem->location_type === 'App\Models\Product')) {
            
            // Transform products to inventory-like format
            $transformedItems = $paginatedData->getCollection()->map(function ($item) {
                return self::ensureInventoryAttributes($item);
            });

            // Create new paginator with transformed items
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $transformedItems,
                $paginatedData->total(),
                $paginatedData->perPage(),
                $paginatedData->currentPage(),
                [
                    'path' => $paginatedData->path(),
                    'pageName' => $paginatedData->getPageName(),
                ]
            );
        }

        // Already inventory data, ensure attributes are present
        $transformedItems = $paginatedData->getCollection()->map(function ($item) {
            return self::ensureInventoryAttributes($item);
        });

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $transformedItems,
            $paginatedData->total(),
            $paginatedData->perPage(),
            $paginatedData->currentPage(),
            [
                'path' => $paginatedData->path(),
                'pageName' => $paginatedData->getPageName(),
            ]
        );
    }
}
