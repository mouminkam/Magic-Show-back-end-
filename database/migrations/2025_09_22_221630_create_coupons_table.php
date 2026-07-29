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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Coupon code (e.g., "WELCOME10", "SAVE20")
            $table->string('name'); // Coupon name/description
            $table->text('description')->nullable(); // Detailed description
            $table->enum('type', ['percentage', 'fixed_amount', 'free_shipping'])->default('percentage'); // Discount type
            $table->decimal('value', 10, 2); // Discount value (percentage or fixed amount)
            $table->decimal('minimum_amount', 10, 2)->nullable(); // Minimum order amount to use coupon
            $table->decimal('maximum_discount', 10, 2)->nullable(); // Maximum discount amount (for percentage coupons)
            $table->integer('usage_limit')->nullable(); // Total usage limit (null = unlimited)
            $table->integer('usage_limit_per_customer')->default(1); // Usage limit per customer
            $table->integer('used_count')->default(0); // Current usage count
            $table->boolean('is_active')->default(true); // Whether coupon is active
            $table->boolean('is_public')->default(true); // Whether coupon is publicly available
            $table->timestamp('starts_at')->nullable(); // When coupon becomes valid
            $table->timestamp('expires_at')->nullable(); // When coupon expires
            $table->json('applicable_products')->nullable(); // Specific products this coupon applies to
            $table->json('applicable_categories')->nullable(); // Specific categories this coupon applies to
            $table->json('excluded_products')->nullable(); // Products excluded from this coupon
            $table->json('excluded_categories')->nullable(); // Categories excluded from this coupon
            $table->json('customer_groups')->nullable(); // Customer groups this coupon applies to
            $table->text('terms_and_conditions')->nullable(); // Terms and conditions
            $table->timestamps();

            // Indexes for better performance
            $table->index(['code']);
            $table->index(['is_active']);
            $table->index(['is_public']);
            $table->index(['type']);
            $table->index(['starts_at']);
            $table->index(['expires_at']);
            $table->index(['used_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
