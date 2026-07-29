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
        Schema::create('product_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('color_id')->constrained('colors')->onDelete('cascade');
            $table->boolean('is_primary')->default(false)->comment('اللون الأساسي للمنتج');
            $table->integer('sort_order')->default(0)->comment('ترتيب العرض');
            $table->timestamps();
            
            // فهارس
            $table->unique(['product_id', 'color_id']);
            $table->index(['product_id', 'is_primary']);
            $table->index(['color_id']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_colors');
    }
};
