<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_page_settings', function (Blueprint $table) {
            $table->id();
            $table->string('hero_title_ar')->nullable();
            $table->string('hero_title_en')->nullable();
            $table->string('hero_subtitle_ar')->nullable();
            $table->string('hero_subtitle_en')->nullable();
            $table->string('hero_background_image')->nullable();
            $table->string('hero_left_badge_ar')->nullable();
            $table->string('hero_left_badge_en')->nullable();
            $table->string('hero_right_badge_ar')->nullable();
            $table->string('hero_right_badge_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_page_settings');
    }
};
