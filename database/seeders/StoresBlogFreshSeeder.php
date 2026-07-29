<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Comment;
use App\Models\BlogPost;
use App\Models\BlogPageSetting;
use App\Models\StorePageSetting;
use Illuminate\Database\Seeder;

/**
 * حذف كل بيانات Stores و Blog ثم إعادة تعبئتها بمحتوى عربي وإنجليزي.
 */
class StoresBlogFreshSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🗑️ حذف بيانات Blog و Stores...');

        Comment::query()->delete();
        BlogPost::query()->delete();
        BlogPageSetting::query()->delete();
        StorePageSetting::query()->delete();
        Branch::query()->delete();

        $this->command->info('✅ تم الحذف. إعادة التعبيئة...');

        $this->call([
            StorePageSettingSeeder::class,
            BranchSeeder::class,
            BlogPageSettingSeeder::class,
            BlogPostSeeder::class,
        ]);

        $this->command->info('✅ تم تعبئة Stores و Blog بمحتوى عربي وإنجليزي.');
    }
}
