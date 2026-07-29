<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_settings', function (Blueprint $table) {
            $table->string('hero_title_ar')->nullable()->after('hero_title');
            $table->string('hero_title_en')->nullable()->after('hero_title_ar');
            $table->string('hero_subtitle_ar')->nullable()->after('hero_subtitle');
            $table->string('hero_subtitle_en')->nullable()->after('hero_subtitle_ar');
            $table->string('hero_left_badge_ar')->nullable()->after('hero_left_badge');
            $table->string('hero_left_badge_en')->nullable()->after('hero_left_badge_ar');
            $table->string('hero_right_badge_ar')->nullable()->after('hero_right_badge');
            $table->string('hero_right_badge_en')->nullable()->after('hero_right_badge_ar');
            $table->string('details_title_ar')->nullable()->after('details_title');
            $table->string('details_title_en')->nullable()->after('details_title_ar');
            $table->text('address_ar')->nullable()->after('address');
            $table->text('address_en')->nullable()->after('address_ar');
            $table->string('about_title_ar')->nullable()->after('about_title');
            $table->string('about_title_en')->nullable()->after('about_title_ar');
            $table->text('about_text_ar')->nullable()->after('about_text');
            $table->text('about_text_en')->nullable()->after('about_text_ar');
        });

        foreach (DB::table('contact_settings')->orderBy('id')->get() as $row) {
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
            if (isset($row->details_title) && $row->details_title !== null) {
                $updates['details_title_ar'] = $row->details_title;
                $updates['details_title_en'] = $row->details_title;
            }
            if (isset($row->address) && $row->address !== null) {
                $updates['address_ar'] = $row->address;
                $updates['address_en'] = $row->address;
            }
            if (isset($row->about_title) && $row->about_title !== null) {
                $updates['about_title_ar'] = $row->about_title;
                $updates['about_title_en'] = $row->about_title;
            }
            if (isset($row->about_text) && $row->about_text !== null) {
                $updates['about_text_ar'] = $row->about_text;
                $updates['about_text_en'] = $row->about_text;
            }
            if (!empty($updates)) {
                DB::table('contact_settings')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('contact_settings', function (Blueprint $table) {
            $table->dropColumn([
                'hero_title_ar', 'hero_title_en', 'hero_subtitle_ar', 'hero_subtitle_en',
                'hero_left_badge_ar', 'hero_left_badge_en', 'hero_right_badge_ar', 'hero_right_badge_en',
                'details_title_ar', 'details_title_en', 'address_ar', 'address_en',
                'about_title_ar', 'about_title_en', 'about_text_ar', 'about_text_en',
            ]);
        });
    }
};
