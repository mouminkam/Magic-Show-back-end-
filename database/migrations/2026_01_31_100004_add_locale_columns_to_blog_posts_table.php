<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('title_ar')->nullable()->after('title');
            $table->string('title_en')->nullable()->after('title_ar');
            $table->text('excerpt_ar')->nullable()->after('excerpt');
            $table->text('excerpt_en')->nullable()->after('excerpt_ar');
            $table->text('content_ar')->nullable()->after('content');
            $table->text('content_en')->nullable()->after('content_ar');
        });

        foreach (DB::table('blog_posts')->orderBy('id')->get() as $row) {
            $row = (object) $row;
            $updates = [];
            if (isset($row->title) && $row->title !== null) {
                $updates['title_ar'] = $row->title;
                $updates['title_en'] = $row->title;
            }
            if (isset($row->excerpt) && $row->excerpt !== null) {
                $updates['excerpt_ar'] = $row->excerpt;
                $updates['excerpt_en'] = $row->excerpt;
            }
            if (isset($row->content) && $row->content !== null) {
                $updates['content_ar'] = $row->content;
                $updates['content_en'] = $row->content;
            }
            if (!empty($updates)) {
                DB::table('blog_posts')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn([
                'title_ar', 'title_en', 'excerpt_ar', 'excerpt_en', 'content_ar', 'content_en',
            ]);
        });
    }
};
