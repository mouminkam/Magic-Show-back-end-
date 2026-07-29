<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('about_stats', function (Blueprint $table) {
            $table->string('title_ar')->nullable()->after('title');
            $table->string('title_en')->nullable()->after('title_ar');
            $table->string('suffix_ar')->nullable()->after('suffix');
            $table->string('suffix_en')->nullable()->after('suffix_ar');
        });

        foreach (DB::table('about_stats')->orderBy('id')->get() as $row) {
            $row = (object) $row;
            $updates = [];
            if (isset($row->title) && $row->title !== null) {
                $updates['title_ar'] = $row->title;
                $updates['title_en'] = $row->title;
            }
            if (isset($row->suffix) && $row->suffix !== null) {
                $updates['suffix_ar'] = $row->suffix;
                $updates['suffix_en'] = $row->suffix;
            }
            if (!empty($updates)) {
                DB::table('about_stats')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('about_stats', function (Blueprint $table) {
            $table->dropColumn(['title_ar', 'title_en', 'suffix_ar', 'suffix_en']);
        });
    }
};
