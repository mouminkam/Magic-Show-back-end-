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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('product_name'); // Product name at time of order (for historical records)
            $table->string('product_sku')->nullable(); // Product SKU at time of order
            $table->integer('quantity'); // Quantity ordered
            $table->decimal('unit_price', 10, 2); // Price per unit at time of order
            $table->decimal('total_price', 10, 2); // Total price for this item (quantity * unit_price)
            $table->decimal('discount_amount', 10, 2)->default(0); // Discount amount for this item
            $table->decimal('tax_amount', 10, 2)->default(0); // Tax amount for this item
            $table->json('product_attributes')->nullable(); // Product attributes/options at time of order
            $table->text('notes')->nullable(); // Item-specific notes
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['order_id']);
            $table->index(['product_id']);
            $table->index(['product_sku']);
            $table->index(['quantity']);
            $table->index(['unit_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
