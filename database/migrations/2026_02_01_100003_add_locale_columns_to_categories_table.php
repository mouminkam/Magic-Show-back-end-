<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ar');
        });

        foreach (DB::table('categories')->orderBy('id')->get() as $row) {
            $row = (object) $row;
            $updates = [];
            if (isset($row->name) && $row->name !== null) {
                $updates['name_ar'] = $row->name;
                $updates['name_en'] = $row->name;
            }
            if (isset($row->description) && $row->description !== null) {
                $updates['description_ar'] = $row->description;
                $updates['description_en'] = $row->description;
            }
            if (!empty($updates)) {
                DB::table('categories')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'name_ar', 'name_en', 'description_ar', 'description_en',
            ]);
        });
    }
};
