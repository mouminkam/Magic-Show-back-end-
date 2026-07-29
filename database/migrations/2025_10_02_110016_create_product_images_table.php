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
        if (!Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('filename');
                $table->string('path');
                $table->string('alt_text')->nullable();
                $table->integer('size')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->integer('order')->default(0);
                $table->timestamps();
                
                $table->index(['product_id', 'is_primary']);
                $table->index(['product_id', 'order']);
            });
        } else {
            // Table exists, just ensure it has the correct structure
            Schema::table('product_images', function (Blueprint $table) {
                if (!Schema::hasColumn('product_images', 'is_primary')) {
                    $table->boolean('is_primary')->default(false)->after('size');
                }
                if (!Schema::hasColumn('product_images', 'order')) {
                    $table->integer('order')->default(0)->after('is_primary');
                }
                if (!Schema::hasColumn('product_images', 'alt_text')) {
                    $table->string('alt_text')->nullable()->after('path');
                }
                if (!Schema::hasColumn('product_images', 'size')) {
                    $table->integer('size')->nullable()->after('alt_text');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
