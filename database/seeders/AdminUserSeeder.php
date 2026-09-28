<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Admin User Seeding...');

        // Define admin users with comprehensive data
        $adminUsers = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@magicshoe.test',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
                'role' => User::ROLE_SUPER_ADMIN,
                'email_verified_at' => now(),
                'description' => 'System Super Administrator with full access'
            ],
            [
                'name' => 'Store Manager',
                'email' => 'manager@magicshoe.test',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
                'role' => User::ROLE_STORE_MANAGER,
                'email_verified_at' => now(),
                'description' => 'Store Manager with inventory and order management access'
            ],
            [
                'name' => 'Product Manager',
                'email' => 'product@magicshoe.test',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
                'role' => User::ROLE_PRODUCT_MANAGER,
                'email_verified_at' => now(),
                'description' => 'Product Manager with catalog management access'
            ],
            [
                'name' => 'Analytics Team Lead',
                'email' => 'analytics@magicshoe.test',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
                'role' => User::ROLE_ANALYTICS_TEAM,
                'email_verified_at' => now(),
                'description' => 'Analytics Team with reporting and insights access'
            ],
            [
                'name' => 'Customer Service Lead',
                'email' => 'support@magicshoe.test',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
                'role' => User::ROLE_CUSTOMER_SERVICE,
                'email_verified_at' => now(),
                'description' => 'Customer Service with customer and order support access'
            ],
            [
                'name' => 'Demo Admin',
                'email' => 'demo@magicshoe.test',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
                'role' => User::ROLE_SUPER_ADMIN,
                'email_verified_at' => now(),
                'description' => 'Demo Admin account for testing purposes'
            ]
        ];

        // Create admin users
        foreach ($adminUsers as $userData) {
            try {
                // Check if user already exists
                $existingUser = User::where('email', $userData['email'])->first();
                
                if ($existingUser) {
                    $this->command->warn("⚠️  User already exists: {$userData['email']}");
                    
                    // Update existing user if needed
                    if ($existingUser->role !== $userData['role']) {
                        $existingUser->update(['role' => $userData['role']]);
                        $this->command->info("✅ Updated role for: {$userData['email']}");
                    }
                    continue;
                }

                // Create new user
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                    'role' => $userData['role'],
                    'email_verified_at' => $userData['email_verified_at'],
                ]);

                $this->command->info("✅ Created admin user: {$user->name} ({$user->email})");
                $this->command->line("   Role: {$user->role}");
                $this->command->line("   Description: {$userData['description']}");
                
                // Log the creation
                Log::info("Admin user created", [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'seeder' => 'AdminUserSeeder'
                ]);

            } catch (\Exception $e) {
                $this->command->error("❌ Failed to create user: {$userData['email']}");
                $this->command->error("   Error: {$e->getMessage()}");
                
                // Log the error
                Log::error("Failed to create admin user", [
                    'email' => $userData['email'],
                    'error' => $e->getMessage(),
                    'seeder' => 'AdminUserSeeder'
                ]);
            }
        }

        // Display summary
        $totalUsers = User::count();
        $adminUsers = User::whereIn('role', [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_STORE_MANAGER,
            User::ROLE_PRODUCT_MANAGER,
            User::ROLE_ANALYTICS_TEAM,
            User::ROLE_CUSTOMER_SERVICE
        ])->count();

        $this->command->info("📊 Seeding Summary:");
        $this->command->line("   Total Users: {$totalUsers}");
        $this->command->line("   Admin Users: {$adminUsers}");
        $this->command->info("🎉 Admin User Seeding Completed!");

        // Display login credentials for development
        if (app()->environment(['local', 'development', 'testing'])) {
            $this->displayLoginCredentials();
        }
    }

    /**
     * Display login credentials for development environment
     */
    private function displayLoginCredentials(): void
    {
        $this->command->info("\n🔐 Development Login Credentials:");
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Super Admin', 'admin@magicshoe.test', 'SEED_ADMIN_PASSWORD (default: password)'],
                ['Store Manager', 'manager@magicshoe.test', 'SEED_ADMIN_PASSWORD (default: password)'],
                ['Product Manager', 'product@magicshoe.test', 'SEED_ADMIN_PASSWORD (default: password)'],
                ['Analytics Team', 'analytics@magicshoe.test', 'SEED_ADMIN_PASSWORD (default: password)'],
                ['Customer Service', 'support@magicshoe.test', 'SEED_ADMIN_PASSWORD (default: password)'],
                ['Demo Admin', 'demo@magicshoe.test', 'SEED_ADMIN_PASSWORD (default: password)'],
            ]
        );
        
        $this->command->warn("⚠️  These credentials are for development only!");
        $this->command->warn("⚠️  Change passwords in production environment!");
    }

    /**
     * Create a specific admin user if needed
     */
    public function createAdminUser(string $name, string $email, string $role, string $password = null): User
    {
        $password = $password ?: env('SEED_ADMIN_PASSWORD', 'password');
        
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => $role,
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * Reset all admin passwords (for security)
     */
    public function resetAdminPasswords(): void
    {
        $adminUsers = User::whereIn('role', [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_STORE_MANAGER,
            User::ROLE_PRODUCT_MANAGER,
            User::ROLE_ANALYTICS_TEAM,
            User::ROLE_CUSTOMER_SERVICE
        ])->get();

        foreach ($adminUsers as $user) {
            $newPassword = 'Reset' . now()->format('Ymd') . '@' . rand(1000, 9999);
            $user->update(['password' => Hash::make($newPassword)]);
            
            $this->command->info("🔑 Reset password for: {$user->email}");
            $this->command->line("   New password: {$newPassword}");
        }
    }
}
