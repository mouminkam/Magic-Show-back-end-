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
        Schema::table('products', function (Blueprint $table) {
            // Remove old brand and material text fields
            $table->dropColumn(['brand', 'material']);
            
            // Add new foreign key relationships
            $table->unsignedBigInteger('brand_id')->nullable()->after('sku');
            $table->unsignedBigInteger('material_id')->nullable()->after('brand_id');
            
            // Add foreign key constraints
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('set null');
            
            // Add indexes for better performance
            $table->index('brand_id');
            $table->index('material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['material_id']);
            
            // Drop indexes
            $table->dropIndex(['brand_id']);
            $table->dropIndex(['material_id']);
            
            // Drop columns
            $table->dropColumn(['brand_id', 'material_id']);
            
            // Restore old columns
            $table->string('brand')->nullable();
            $table->string('material')->nullable();
        });
    }
};
