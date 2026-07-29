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
        Schema::create('product_product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products', 'id', 'fk_product_id')->onDelete('cascade');
            $table->foreignId('product_attribute_value_id')->constrained('product_attribute_values', 'id', 'fk_attr_value_id')->onDelete('cascade');
            $table->timestamps();
            
            // Ensure unique combination with custom name
            $table->unique(['product_id', 'product_attribute_value_id'], 'unique_product_attr_value');
            
            // Indexes for better performance with custom names
            $table->index(['product_id'], 'idx_product_id');
            $table->index(['product_attribute_value_id'], 'idx_attr_value_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_product_attribute_values');
    }
};
