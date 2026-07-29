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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'featured_image_thumb')) {
                $table->string('featured_image_thumb')->nullable()->after('featured_image');
            }
            if (!Schema::hasColumn('products', 'featured_image_full')) {
                $table->string('featured_image_full')->nullable()->after('featured_image_thumb');
            }
        });

        // Backfill existing products so current featured_image continues to work
        if (Schema::hasColumn('products', 'featured_image')) {
            DB::table('products')
                ->whereNull('featured_image_full')
                ->whereNotNull('featured_image')
                ->update(['featured_image_full' => DB::raw('featured_image')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'featured_image_full')) {
                $table->dropColumn('featured_image_full');
            }
            if (Schema::hasColumn('products', 'featured_image_thumb')) {
                $table->dropColumn('featured_image_thumb');
            }
        });
    }
};
