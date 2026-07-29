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
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Color", "Size", "Material"
            $table->string('slug')->unique(); // e.g., "color", "size", "material"
            $table->text('description')->nullable();
            $table->enum('type', ['text', 'number', 'select', 'multiselect', 'boolean', 'date'])->default('text');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('options')->nullable(); // For select/multiselect options
            $table->string('validation_rules')->nullable(); // Laravel validation rules
            $table->timestamps();

            // Indexes for better performance
            $table->index(['slug']);
            $table->index(['type']);
            $table->index(['is_required']);
            $table->index(['is_filterable']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
