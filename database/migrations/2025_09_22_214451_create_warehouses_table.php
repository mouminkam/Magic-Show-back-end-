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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Warehouse name
            $table->string('code')->unique(); // Unique warehouse code
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country');
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('manager_name')->nullable();
            $table->decimal('latitude', 10, 8)->nullable(); // GPS coordinates
            $table->decimal('longitude', 11, 8)->nullable(); // GPS coordinates
            $table->boolean('is_active')->default(true);
            $table->enum('type', ['main', 'distribution', 'retail', 'storage'])->default('storage');
            $table->decimal('capacity', 10, 2)->nullable(); // Storage capacity
            $table->string('capacity_unit')->default('sqft'); // Capacity unit (sqft, sqm, etc.)
            $table->timestamps();

            // Indexes for better performance
            $table->index(['code']);
            $table->index(['is_active']);
            $table->index(['type']);
            $table->index(['city']);
            $table->index(['country']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
