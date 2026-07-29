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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Permission name (e.g., "users.create", "products.edit")
            $table->string('display_name'); // Human-readable name
            $table->text('description')->nullable(); // Permission description
            $table->string('module'); // Module/group (e.g., "users", "products", "orders")
            $table->string('category')->default('general'); // Category within module
            $table->string('action'); // Action type (create, read, update, delete, manage)
            $table->string('resource')->nullable(); // Resource being acted upon
            $table->boolean('is_system')->default(false); // System permission (cannot be deleted)
            $table->boolean('is_active')->default(true); // Whether permission is active
            $table->integer('sort_order')->default(0); // Sort order for display
            $table->timestamps();

            // Indexes for better performance
            $table->index(['name']);
            $table->index(['module']);
            $table->index(['category']);
            $table->index(['action']);
            $table->index(['is_active']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
