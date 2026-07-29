<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ar');
            $table->string('address_ar')->nullable()->after('address');
            $table->string('address_en')->nullable()->after('address_ar');
        });

        foreach (DB::table('branches')->orderBy('id')->get() as $row) {
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
            if (isset($row->address) && $row->address !== null) {
                $updates['address_ar'] = $row->address;
                $updates['address_en'] = $row->address;
            }
            if (!empty($updates)) {
                DB::table('branches')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'name_ar', 'name_en', 'description_ar', 'description_en', 'address_ar', 'address_en',
            ]);
        });
    }
};
