<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('customer_name_ar')->nullable()->after('customer_name');
            $table->string('customer_name_en')->nullable()->after('customer_name_ar');
            $table->text('text_ar')->nullable()->after('text');
            $table->text('text_en')->nullable()->after('text_ar');
        });

        foreach (DB::table('testimonials')->orderBy('id')->get() as $row) {
            $row = (object) $row;
            $updates = [];
            if (isset($row->customer_name) && $row->customer_name !== null) {
                $updates['customer_name_ar'] = $row->customer_name;
                $updates['customer_name_en'] = $row->customer_name;
            }
            if (isset($row->text) && $row->text !== null) {
                $updates['text_ar'] = $row->text;
                $updates['text_en'] = $row->text;
            }
            if (!empty($updates)) {
                DB::table('testimonials')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['customer_name_ar', 'customer_name_en', 'text_ar', 'text_en']);
        });
    }
};
