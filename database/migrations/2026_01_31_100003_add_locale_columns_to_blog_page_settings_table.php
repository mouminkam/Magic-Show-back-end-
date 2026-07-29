<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_page_settings', function (Blueprint $table) {
            $table->string('hero_title_ar')->nullable()->after('hero_title');
            $table->string('hero_title_en')->nullable()->after('hero_title_ar');
            $table->string('hero_subtitle_ar')->nullable()->after('hero_subtitle');
            $table->string('hero_subtitle_en')->nullable()->after('hero_subtitle_ar');
            $table->string('hero_left_badge_ar')->nullable()->after('hero_left_badge');
            $table->string('hero_left_badge_en')->nullable()->after('hero_left_badge_ar');
            $table->string('hero_right_badge_ar')->nullable()->after('hero_right_badge');
            $table->string('hero_right_badge_en')->nullable()->after('hero_right_badge_ar');
        });

        foreach (DB::table('blog_page_settings')->orderBy('id')->get() as $row) {
            $row = (object) $row;
            $updates = [];
            if (isset($row->hero_title) && $row->hero_title !== null) {
                $updates['hero_title_ar'] = $row->hero_title;
                $updates['hero_title_en'] = $row->hero_title;
            }
            if (isset($row->hero_subtitle) && $row->hero_subtitle !== null) {
                $updates['hero_subtitle_ar'] = $row->hero_subtitle;
                $updates['hero_subtitle_en'] = $row->hero_subtitle;
            }
            if (isset($row->hero_left_badge) && $row->hero_left_badge !== null) {
                $updates['hero_left_badge_ar'] = $row->hero_left_badge;
                $updates['hero_left_badge_en'] = $row->hero_left_badge;
            }
            if (isset($row->hero_right_badge) && $row->hero_right_badge !== null) {
                $updates['hero_right_badge_ar'] = $row->hero_right_badge;
                $updates['hero_right_badge_en'] = $row->hero_right_badge;
            }
            if (!empty($updates)) {
                DB::table('blog_page_settings')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('blog_page_settings', function (Blueprint $table) {
            $table->dropColumn([
                'hero_title_ar', 'hero_title_en', 'hero_subtitle_ar', 'hero_subtitle_en',
                'hero_left_badge_ar', 'hero_left_badge_en', 'hero_right_badge_ar', 'hero_right_badge_en',
            ]);
        });
    }
};
