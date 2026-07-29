<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (!Schema::hasColumn('product_images', 'thumb_path')) {
                $table->string('thumb_path')->nullable()->after('path');
            }
            if (!Schema::hasColumn('product_images', 'full_path')) {
                $table->string('full_path')->nullable()->after('thumb_path');
            }
        });

        // Backfill existing records for backward compatibility
        if (Schema::hasColumn('product_images', 'path')) {
            DB::table('product_images')
                ->whereNull('full_path')
                ->whereNotNull('path')
                ->update(['full_path' => DB::raw('path')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (Schema::hasColumn('product_images', 'full_path')) {
                $table->dropColumn('full_path');
            }
            if (Schema::hasColumn('product_images', 'thumb_path')) {
                $table->dropColumn('thumb_path');
            }
        });
    }
};
