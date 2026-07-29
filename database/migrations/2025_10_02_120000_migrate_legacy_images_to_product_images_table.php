<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;
use App\Models\ProductImage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate legacy images from JSON field to product_images table
        $products = Product::whereNotNull('images')->get();
        
        foreach ($products as $product) {
            if (is_array($product->images)) {
                foreach ($product->images as $index => $imagePath) {
                    // Skip if image path is empty or invalid
                    if (empty($imagePath) || !is_string($imagePath)) {
                        continue;
                    }
                    
                    // Check if this image already exists in product_images table
                    $existingImage = ProductImage::where('product_id', $product->id)
                        ->where('path', $imagePath)
                        ->first();
                    
                    if (!$existingImage) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'filename' => basename($imagePath),
                            'path' => $imagePath,
                            'alt_text' => $product->name,
                            'is_primary' => $index === 0,
                            'order' => $index + 1,
                        ]);
                    }
                }
            }
        }
        
        // After migration, we can optionally remove the images JSON field
        // But we'll keep it for now as a backup
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible as we're converting data
        // We'll just log that this migration was reversed
        \Log::info('Legacy images migration was reversed - data conversion is not reversible');
    }
};
