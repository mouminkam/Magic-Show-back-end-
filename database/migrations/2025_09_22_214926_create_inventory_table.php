<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->morphs('location'); // location_type and location_id for polymorphic relationship
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0); // Quantity reserved for orders
            $table->integer('available_quantity')->default(0); // quantity - reserved_quantity
            $table->integer('min_quantity')->default(0); // Minimum stock level
            $table->integer('max_quantity')->nullable(); // Maximum stock level
            $table->decimal('cost_price', 10, 2)->nullable(); // Cost price for this inventory item
            $table->string('batch_number')->nullable(); // Batch or lot number
            $table->date('expiry_date')->nullable(); // Expiry date for perishable items
            $table->string('rack_location')->nullable(); // Physical location in warehouse/branch
            $table->string('shelf_location')->nullable(); // Shelf or bin location
            $table->text('notes')->nullable(); // Additional notes
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['product_id']);
            $table->index(['quantity']);
            $table->index(['available_quantity']);
            $table->index(['is_active']);
            $table->index(['batch_number']);
            $table->index(['expiry_date']);
            
            // Unique constraint to prevent duplicate inventory entries for same product and location
            $table->unique(['product_id', 'location_type', 'location_id'], 'unique_product_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
