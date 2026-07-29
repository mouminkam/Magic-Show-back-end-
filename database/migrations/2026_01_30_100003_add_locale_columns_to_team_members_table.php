<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->string('role_ar')->nullable()->after('role');
            $table->string('role_en')->nullable()->after('role_ar');
            $table->text('bio_ar')->nullable()->after('bio');
            $table->text('bio_en')->nullable()->after('bio_ar');
        });

        foreach (DB::table('team_members')->orderBy('id')->get() as $row) {
            $row = (object) $row;
            $updates = [];
            if (isset($row->name) && $row->name !== null) {
                $updates['name_ar'] = $row->name;
                $updates['name_en'] = $row->name;
            }
            if (isset($row->role) && $row->role !== null) {
                $updates['role_ar'] = $row->role;
                $updates['role_en'] = $row->role;
            }
            if (isset($row->bio) && $row->bio !== null) {
                $updates['bio_ar'] = $row->bio;
                $updates['bio_en'] = $row->bio;
            }
            if (!empty($updates)) {
                DB::table('team_members')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'name_en', 'role_ar', 'role_en', 'bio_ar', 'bio_en']);
        });
    }
};
