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
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم اللون بالعربية');
            $table->string('name_en')->nullable()->comment('اسم اللون بالإنجليزية');
            $table->string('hex_code', 7)->comment('كود اللون HEX مثل #FF5733');
            $table->string('rgb_code')->nullable()->comment('كود RGB مثل 255,87,51');
            $table->string('category')->default('أساسي')->comment('فئة اللون: أساسي، ثانوي، محايد، إلخ');
            $table->boolean('is_active')->default(true)->comment('نشط/غير نشط');
            $table->integer('sort_order')->default(0)->comment('ترتيب العرض');
            $table->text('description')->nullable()->comment('وصف اللون');
            $table->timestamps();
            
            // فهارس
            $table->index(['is_active', 'sort_order']);
            $table->index('category');
            $table->unique('hex_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};
