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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // Currency code (e.g., "USD", "SAR", "EUR")
            $table->string('name'); // Currency name (e.g., "US Dollar", "Saudi Riyal")
            $table->string('symbol'); // Currency symbol (e.g., "$", "ر.س", "€")
            $table->string('symbol_position')->default('before'); // Symbol position (before, after)
            $table->integer('decimal_places')->default(2); // Number of decimal places
            $table->string('decimal_separator')->default('.'); // Decimal separator
            $table->string('thousands_separator')->default(','); // Thousands separator
            $table->decimal('exchange_rate', 10, 6)->default(1.000000); // Exchange rate to base currency
            $table->boolean('is_base')->default(false); // Whether this is the base currency
            $table->boolean('is_active')->default(true); // Whether currency is active
            $table->boolean('is_default')->default(false); // Whether this is the default currency
            $table->integer('sort_order')->default(0); // Sort order for display
            $table->timestamps();

            // Indexes for better performance
            $table->index(['code']);
            $table->index(['is_active']);
            $table->index(['is_base']);
            $table->index(['is_default']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
