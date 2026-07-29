<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('about_sections', function (Blueprint $table) {
            $table->string('title_ar')->nullable()->after('title');
            $table->string('title_en')->nullable()->after('title_ar');
            $table->string('subtitle_ar')->nullable()->after('subtitle');
            $table->string('subtitle_en')->nullable()->after('subtitle_ar');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ar');
            $table->string('left_badge_ar')->nullable()->after('left_badge');
            $table->string('left_badge_en')->nullable()->after('left_badge_ar');
            $table->string('right_badge_ar')->nullable()->after('right_badge');
            $table->string('right_badge_en')->nullable()->after('right_badge_ar');
            $table->string('button_text_ar')->nullable()->after('button_text');
            $table->string('button_text_en')->nullable()->after('button_text_ar');
            $table->json('features_ar')->nullable()->after('features');
            $table->json('features_en')->nullable()->after('features_ar');
        });

        foreach (DB::table('about_sections')->orderBy('id')->get() as $row) {
            $row = (object) $row;
            $updates = [];
            if (isset($row->title) && $row->title !== null) {
                $updates['title_ar'] = $row->title;
                $updates['title_en'] = $row->title;
            }
            if (isset($row->subtitle) && $row->subtitle !== null) {
                $updates['subtitle_ar'] = $row->subtitle;
                $updates['subtitle_en'] = $row->subtitle;
            }
            if (isset($row->description) && $row->description !== null) {
                $updates['description_ar'] = $row->description;
                $updates['description_en'] = $row->description;
            }
            if (isset($row->left_badge) && $row->left_badge !== null) {
                $updates['left_badge_ar'] = $row->left_badge;
                $updates['left_badge_en'] = $row->left_badge;
            }
            if (isset($row->right_badge) && $row->right_badge !== null) {
                $updates['right_badge_ar'] = $row->right_badge;
                $updates['right_badge_en'] = $row->right_badge;
            }
            if (isset($row->button_text) && $row->button_text !== null) {
                $updates['button_text_ar'] = $row->button_text;
                $updates['button_text_en'] = $row->button_text;
            }
            if (isset($row->features) && $row->features !== null) {
                $updates['features_ar'] = $row->features;
                $updates['features_en'] = $row->features;
            }
            if (!empty($updates)) {
                DB::table('about_sections')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('about_sections', function (Blueprint $table) {
            $table->dropColumn([
                'title_ar', 'title_en', 'subtitle_ar', 'subtitle_en',
                'description_ar', 'description_en', 'left_badge_ar', 'left_badge_en',
                'right_badge_ar', 'right_badge_en', 'button_text_ar', 'button_text_en',
                'features_ar', 'features_en',
            ]);
        });
    }
};
