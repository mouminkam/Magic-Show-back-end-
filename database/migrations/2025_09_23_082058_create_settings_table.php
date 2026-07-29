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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Setting key (e.g., "site_name", "default_currency")
            $table->text('value')->nullable(); // Setting value
            $table->string('type')->default('string'); // Data type (string, integer, boolean, json, text)
            $table->string('group')->default('general'); // Setting group (general, email, payment, etc.)
            $table->string('label')->nullable(); // Human-readable label
            $table->text('description')->nullable(); // Setting description
            $table->json('options')->nullable(); // Available options for select/radio settings
            $table->boolean('is_public')->default(false); // Whether setting is publicly accessible
            $table->boolean('is_required')->default(false); // Whether setting is required
            $table->integer('sort_order')->default(0); // Sort order for display
            $table->timestamps();

            // Indexes for better performance
            $table->index(['key']);
            $table->index(['group']);
            $table->index(['type']);
            $table->index(['is_public']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
