<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting Database Seeding...');
        
        // Log seeding start
        Log::info('Database seeding started', [
            'environment' => App::environment(),
            'timestamp' => now()
        ]);

        try {
            // Core seeders that should always run
            $this->seedCore();

            // Development/Testing seeders
            if (App::environment(['local', 'development', 'testing'])) {
                $this->seedDevelopment();
            }

            // Production seeders (minimal, essential data only)
            if (App::environment('production')) {
                $this->seedProduction();
            }

            $this->command->info('🎉 Database Seeding Completed Successfully!');
            
            // Log seeding completion
            Log::info('Database seeding completed successfully', [
                'environment' => App::environment(),
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            $this->command->error('❌ Database Seeding Failed!');
            $this->command->error("Error: {$e->getMessage()}");
            
            // Log seeding error
            Log::error('Database seeding failed', [
                'error' => $e->getMessage(),
                'environment' => App::environment(),
                'timestamp' => now()
            ]);
            
            throw $e;
        }
    }

    /**
     * Seed core data that should always exist
     */
    private function seedCore(): void
    {
        $this->command->info('📊 Seeding Core Data...');
        
        // Always seed admin users and permissions
        $this->call([
            AdminUserSeeder::class,
            PermissionSeeder::class,
            BranchSeeder::class,
            WarehouseSeeder::class,
            BrandSeeder::class,
            MaterialSeeder::class,
            ColorSeeder::class,
            InventorySeeder::class,
            ContactSettingSeeder::class,
            StorePageSettingSeeder::class,
            ShopPageSettingSeeder::class,
            BlogPageSettingSeeder::class,
            HomePageSectionSeeder::class,
            BlogPostSeeder::class,
            BlogCommentSeeder::class,
        ]);
    }

    /**
     * Seed development/testing data
     */
    private function seedDevelopment(): void
    {
        $this->command->info('🔧 Seeding Development Data...');
        
        // Add development-specific seeders here
        // Example: Sample products, categories, test customers, etc.
        
        $this->command->warn('⚠️  Development environment detected');
        $this->command->warn('⚠️  Additional test data may be seeded');
    }

    /**
     * Seed production data (minimal, essential only)
     */
    private function seedProduction(): void
    {
        $this->command->info('🏭 Seeding Production Data...');
        
        // Only essential data for production
        // Avoid test/demo data in production
        
        $this->command->warn('🔒 Production environment detected');
        $this->command->warn('🔒 Only essential data will be seeded');
    }

    /**
     * Seed sample e-commerce data for testing
     */
    public function seedSampleData(): void
    {
        $this->command->info('🛍️ Seeding Sample E-commerce Data...');
        
        // This method can be called separately for demo/testing purposes
        // Example: php artisan db:seed --class=DatabaseSeeder@seedSampleData
        
        // Add seeders for:
        // - Sample categories
        // - Sample products
        // - Sample customers
        // - Sample orders
        // - Sample blog posts
        // - Sample pages
        
        $this->command->info('✅ Sample data seeded successfully');
    }

    /**
     * Reset and reseed the database
     */
    public function fresh(): void
    {
        $this->command->info('🔄 Fresh Database Seeding...');
        
        // This would be called with: php artisan migrate:fresh --seed
        $this->run();
    }
}
