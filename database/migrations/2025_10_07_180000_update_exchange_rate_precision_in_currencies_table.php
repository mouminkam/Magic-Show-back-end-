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
        Schema::table('currencies', function (Blueprint $table) {
            // تغيير الدقة من (10, 6) إلى (15, 6)
            // هذا يسمح بـ 9 أرقام قبل الفاصلة و 6 بعدها
            // مثال: 999999999.999999 (أكثر من كافي لأي سعر صرف)
            $table->decimal('exchange_rate', 15, 6)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->decimal('exchange_rate', 10, 6)->change();
        });
    }
};

