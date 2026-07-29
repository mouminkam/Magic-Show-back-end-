<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Size;
use App\Models\Season;

class SizesAndSeasonsSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = collect(['35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46']);

        foreach ($sizes as $i => $name) {
            Size::firstOrCreate(
                ['name' => (string) $name],
                ['sort_order' => $i, 'is_active' => true]
            );
        }

        // Seed seasons
        $seasons = [
            ['name_ar' => 'ربيع',  'name_en' => 'Spring', 'value' => 'Spring', 'sort_order' => 0],
            ['name_ar' => 'صيف',   'name_en' => 'Summer', 'value' => 'Summer', 'sort_order' => 1],
            ['name_ar' => 'خريف',  'name_en' => 'Autumn', 'value' => 'Autumn', 'sort_order' => 2],
            ['name_ar' => 'شتاء',  'name_en' => 'Winter', 'value' => 'Winter', 'sort_order' => 3],
        ];

        foreach ($seasons as $season) {
            Season::firstOrCreate(
                ['value' => $season['value']],
                $season + ['is_active' => true]
            );
        }

        $this->command->info('Sizes seeded: ' . Size::count());
        $this->command->info('Seasons seeded: ' . Season::count());
    }
}
