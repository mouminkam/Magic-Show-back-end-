<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('colors', function (Blueprint $table) {
            $table->string('color_code', 7)->nullable()->after('hex_code');
        });

        $colors = DB::table('colors')
            ->select(['id', 'hex_code'])
            ->whereNull('color_code')
            ->get();

        foreach ($colors as $color) {
            $hex = strtoupper(ltrim((string) $color->hex_code, '#'));
            if (strlen($hex) === 6 && ctype_xdigit($hex)) {
                DB::table('colors')
                    ->where('id', $color->id)
                    ->update(['color_code' => '#' . $hex]);
            }
        }

        Schema::table('colors', function (Blueprint $table) {
            $table->unique('color_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('colors', function (Blueprint $table) {
            $table->dropUnique(['color_code']);
            $table->dropColumn('color_code');
        });
    }
};
