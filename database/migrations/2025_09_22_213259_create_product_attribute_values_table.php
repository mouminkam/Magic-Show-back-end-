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
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained()->onDelete('cascade');
            $table->string('value'); // The actual attribute value
            $table->string('display_value')->nullable(); // Human-readable display value
            $table->string('color_code')->nullable(); // For color attributes (hex code)
            $table->string('image')->nullable(); // For image-based attributes
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['product_attribute_id']);
            $table->index(['value']);
            $table->index(['is_active']);
            $table->index(['sort_order']);
            
            // Unique constraint to prevent duplicate values for same attribute
            $table->unique(['product_attribute_id', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
    }
};
