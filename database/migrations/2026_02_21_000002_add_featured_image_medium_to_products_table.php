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
            if (!Schema::hasColumn('products', 'featured_image_medium')) {
                $table->string('featured_image_medium')->nullable()->after('featured_image_full');
            }
        });

        // Backfill for backward compatibility: if full exists and medium is missing, set medium to full.
        if (Schema::hasColumn('products', 'featured_image_full')) {
            DB::table('products')
                ->whereNull('featured_image_medium')
                ->whereNotNull('featured_image_full')
                ->update(['featured_image_medium' => DB::raw('featured_image_full')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'featured_image_medium')) {
                $table->dropColumn('featured_image_medium');
            }
        });
    }
};
