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
        Schema::table('orders', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('customer_notes'); // ملاحظات الإدارة للتواصل مع العميل
            $table->enum('contact_status', ['pending', 'contacted', 'confirmed', 'failed'])->default('pending')->after('admin_notes'); // حالة التواصل
            $table->timestamp('contacted_at')->nullable()->after('contact_status'); // تاريخ التواصل
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['admin_notes', 'contact_status', 'contacted_at']);
        });
    }
};
