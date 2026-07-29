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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // Unique order number
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded', 'partially_refunded'])->default('pending');
            $table->enum('payment_method', ['cash', 'credit_card', 'debit_card', 'bank_transfer', 'paypal', 'apple_pay', 'google_pay'])->nullable();
            $table->decimal('subtotal', 10, 2); // Subtotal before tax and shipping
            $table->decimal('tax_amount', 10, 2)->default(0); // Tax amount
            $table->decimal('shipping_amount', 10, 2)->default(0); // Shipping cost
            $table->decimal('discount_amount', 10, 2)->default(0); // Discount amount
            $table->decimal('total_amount', 10, 2); // Final total amount
            $table->string('currency', 3)->default('SYP'); // Currency code
            $table->text('shipping_address'); // Shipping address JSON
            $table->text('billing_address')->nullable(); // Billing address JSON
            $table->string('shipping_method')->nullable(); // Shipping method
            $table->text('notes')->nullable(); // Order notes
            $table->text('customer_notes')->nullable(); // Customer notes
            $table->timestamp('confirmed_at')->nullable(); // When order was confirmed
            $table->timestamp('shipped_at')->nullable(); // When order was shipped
            $table->timestamp('delivered_at')->nullable(); // When order was delivered
            $table->timestamp('cancelled_at')->nullable(); // When order was cancelled
            $table->string('tracking_number')->nullable(); // Shipping tracking number
            $table->string('coupon_code')->nullable(); // Applied coupon code
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['order_number']);
            $table->index(['customer_id']);
            $table->index(['status']);
            $table->index(['payment_status']);
            $table->index(['created_at']);
            $table->index(['confirmed_at']);
            $table->index(['shipped_at']);
            $table->index(['delivered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
