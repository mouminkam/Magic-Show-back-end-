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
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role'); // Role name (e.g., "super_admin", "store_manager")
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->boolean('is_granted')->default(true); // Whether permission is granted or denied
            $table->json('conditions')->nullable(); // Additional conditions for permission
            $table->timestamps();

            // Indexes for better performance
            $table->index(['role']);
            $table->index(['permission_id']);
            $table->index(['is_granted']);
            
            // Ensure unique role-permission combination
            $table->unique(['role', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
