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
        Schema::create('coupon_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->decimal('discount_amount', 10, 2); // Actual discount amount applied
            $table->decimal('order_total', 10, 2); // Order total before discount
            $table->decimal('order_total_after_discount', 10, 2); // Order total after discount
            $table->string('coupon_code'); // Coupon code used (for historical reference)
            $table->timestamp('used_at'); // When the coupon was used
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['coupon_id']);
            $table->index(['customer_id']);
            $table->index(['order_id']);
            $table->index(['coupon_code']);
            $table->index(['used_at']);
            
            // Ensure unique combination (customer can't use same coupon multiple times on same order)
            $table->unique(['coupon_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_usage');
    }
};
