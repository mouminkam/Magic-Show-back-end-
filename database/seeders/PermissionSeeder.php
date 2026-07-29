<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Starting Permission System Seeding...');
        Log::info('Permission seeding started');

        try {
            // Clear existing permissions and role assignments
            $this->clearExistingData();

            // Create all permissions
            $this->createPermissions();

            // Assign permissions to roles
            $this->assignPermissionsToRoles();

            $this->command->info('✅ Permission System Seeding Completed Successfully!');
            Log::info('Permission seeding completed successfully');
        } catch (\Exception $e) {
            $this->command->error('❌ Permission Seeding Failed!');
            Log::error('Permission seeding failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Clear existing permission data.
     */
    private function clearExistingData(): void
    {
        $this->command->info('🧹 Clearing existing permission data...');
        
        // تعطيل قيود المفاتيح الأجنبية مؤقتًا
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // حذف البيانات من الجداول بالترتيب الصحيح
        DB::table('role_permissions')->truncate();
        DB::table('permissions')->truncate();
        
        // إعادة تفعيل قيود المفاتيح الأجنبية
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('✅ Existing permission data cleared');
    }

    /**
     * Create all system permissions.
     */
    private function createPermissions(): void
    {
        $this->command->info('📋 Creating system permissions...');

        $permissions = $this->getPermissionDefinitions();
        $createdCount = 0;

        foreach ($permissions as $permissionData) {
            try {
                Permission::create($permissionData);
                $createdCount++;
                $this->command->line("  ✓ Created: {$permissionData['name']}");
            } catch (\Exception $e) {
                $this->command->warn("  ⚠️  Failed to create: {$permissionData['name']} - {$e->getMessage()}");
                Log::warning("Failed to create permission", [
                    'permission' => $permissionData['name'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->command->info("✅ Created {$createdCount} permissions");
    }

    /**
     * Assign permissions to roles.
     */
    private function assignPermissionsToRoles(): void
    {
        $this->command->info('🔗 Assigning permissions to roles...');

        $rolePermissions = $this->getRolePermissionAssignments();
        $assignedCount = 0;

        foreach ($rolePermissions as $role => $permissions) {
            foreach ($permissions as $permissionName) {
                try {
                    $permission = Permission::where('name', $permissionName)->first();
                    
                    if ($permission) {
                        DB::table('role_permissions')->insert([
                            'role' => $role,
                            'permission_id' => $permission->id,
                            'is_granted' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $assignedCount++;
                    } else {
                        $this->command->warn("  ⚠️  Permission not found: {$permissionName}");
                    }
                } catch (\Exception $e) {
                    $this->command->warn("  ⚠️  Failed to assign: {$permissionName} to {$role}");
                    Log::warning("Failed to assign permission", [
                        'role' => $role,
                        'permission' => $permissionName,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        $this->command->info("✅ Assigned {$assignedCount} permission-role combinations");
    }

    /**
     * Get all permission definitions.
     */
    private function getPermissionDefinitions(): array
    {
        return [
            // User Management Permissions
            [
                'name' => 'users.create',
                'display_name' => 'Create Users',
                'description' => 'Create new user accounts',
                'module' => Permission::MODULE_USERS,
                'category' => 'management',
                'action' => Permission::ACTION_CREATE,
                'resource' => 'users',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'users.read',
                'display_name' => 'View Users',
                'description' => 'View user accounts and profiles',
                'module' => Permission::MODULE_USERS,
                'category' => 'management',
                'action' => Permission::ACTION_READ,
                'resource' => 'users',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'users.update',
                'display_name' => 'Update Users',
                'description' => 'Update user account information',
                'module' => Permission::MODULE_USERS,
                'category' => 'management',
                'action' => Permission::ACTION_UPDATE,
                'resource' => 'users',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'users.delete',
                'display_name' => 'Delete Users',
                'description' => 'Delete user accounts',
                'module' => Permission::MODULE_USERS,
                'category' => 'management',
                'action' => Permission::ACTION_DELETE,
                'resource' => 'users',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'users.manage',
                'display_name' => 'Manage Users',
                'description' => 'Full user management capabilities',
                'module' => Permission::MODULE_USERS,
                'category' => 'management',
                'action' => Permission::ACTION_MANAGE,
                'resource' => 'users',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 5,
            ],

            // Product Management Permissions
            [
                'name' => 'products.create',
                'display_name' => 'Create Products',
                'description' => 'Create new products',
                'module' => Permission::MODULE_PRODUCTS,
                'category' => 'management',
                'action' => Permission::ACTION_CREATE,
                'resource' => 'products',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'products.read',
                'display_name' => 'View Products',
                'description' => 'View product information',
                'module' => Permission::MODULE_PRODUCTS,
                'category' => 'management',
                'action' => Permission::ACTION_READ,
                'resource' => 'products',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'products.update',
                'display_name' => 'Update Products',
                'description' => 'Update product information',
                'module' => Permission::MODULE_PRODUCTS,
                'category' => 'management',
                'action' => Permission::ACTION_UPDATE,
                'resource' => 'products',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'products.delete',
                'display_name' => 'Delete Products',
                'description' => 'Delete products',
                'module' => Permission::MODULE_PRODUCTS,
                'category' => 'management',
                'action' => Permission::ACTION_DELETE,
                'resource' => 'products',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 13,
            ],
            [
                'name' => 'products.manage',
                'display_name' => 'Manage Products',
                'description' => 'Full product management capabilities',
                'module' => Permission::MODULE_PRODUCTS,
                'category' => 'management',
                'action' => Permission::ACTION_MANAGE,
                'resource' => 'products',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 14,
            ],

            // Category Management Permissions
            [
                'name' => 'categories.create',
                'display_name' => 'Create Categories',
                'description' => 'Create new product categories',
                'module' => Permission::MODULE_CATEGORIES,
                'category' => 'management',
                'action' => Permission::ACTION_CREATE,
                'resource' => 'categories',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'categories.read',
                'display_name' => 'View Categories',
                'description' => 'View product categories',
                'module' => Permission::MODULE_CATEGORIES,
                'category' => 'management',
                'action' => Permission::ACTION_READ,
                'resource' => 'categories',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 21,
            ],
            [
                'name' => 'categories.update',
                'display_name' => 'Update Categories',
                'description' => 'Update category information',
                'module' => Permission::MODULE_CATEGORIES,
                'category' => 'management',
                'action' => Permission::ACTION_UPDATE,
                'resource' => 'categories',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 22,
            ],
            [
                'name' => 'categories.delete',
                'display_name' => 'Delete Categories',
                'description' => 'Delete product categories',
                'module' => Permission::MODULE_CATEGORIES,
                'category' => 'management',
                'action' => Permission::ACTION_DELETE,
                'resource' => 'categories',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 23,
            ],

            // Order Management Permissions
            [
                'name' => 'orders.create',
                'display_name' => 'Create Orders',
                'description' => 'Create new orders',
                'module' => Permission::MODULE_ORDERS,
                'category' => 'management',
                'action' => Permission::ACTION_CREATE,
                'resource' => 'orders',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'name' => 'orders.read',
                'display_name' => 'View Orders',
                'description' => 'View order information',
                'module' => Permission::MODULE_ORDERS,
                'category' => 'management',
                'action' => Permission::ACTION_READ,
                'resource' => 'orders',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 31,
            ],
            [
                'name' => 'orders.update',
                'display_name' => 'Update Orders',
                'description' => 'Update order information',
                'module' => Permission::MODULE_ORDERS,
                'category' => 'management',
                'action' => Permission::ACTION_UPDATE,
                'resource' => 'orders',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 32,
            ],
            [
                'name' => 'orders.delete',
                'display_name' => 'Delete Orders',
                'description' => 'Delete orders',
                'module' => Permission::MODULE_ORDERS,
                'category' => 'management',
                'action' => Permission::ACTION_DELETE,
                'resource' => 'orders',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 33,
            ],
            [
                'name' => 'orders.manage',
                'display_name' => 'Manage Orders',
                'description' => 'Full order management capabilities',
                'module' => Permission::MODULE_ORDERS,
                'category' => 'management',
                'action' => Permission::ACTION_MANAGE,
                'resource' => 'orders',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 34,
            ],

            // Customer Management Permissions
            [
                'name' => 'customers.read',
                'display_name' => 'View Customers',
                'description' => 'View customer information',
                'module' => Permission::MODULE_CUSTOMERS,
                'category' => 'management',
                'action' => Permission::ACTION_READ,
                'resource' => 'customers',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'name' => 'customers.update',
                'display_name' => 'Update Customers',
                'description' => 'Update customer information',
                'module' => Permission::MODULE_CUSTOMERS,
                'category' => 'management',
                'action' => Permission::ACTION_UPDATE,
                'resource' => 'customers',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 41,
            ],

            // Inventory Management Permissions
            [
                'name' => 'inventory.read',
                'display_name' => 'View Inventory',
                'description' => 'View inventory levels and stock',
                'module' => Permission::MODULE_INVENTORY,
                'category' => 'management',
                'action' => Permission::ACTION_READ,
                'resource' => 'inventory',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'name' => 'inventory.update',
                'display_name' => 'Update Inventory',
                'description' => 'Update inventory levels and stock',
                'module' => Permission::MODULE_INVENTORY,
                'category' => 'management',
                'action' => Permission::ACTION_UPDATE,
                'resource' => 'inventory',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 51,
            ],
            [
                'name' => 'inventory.manage',
                'display_name' => 'Manage Inventory',
                'description' => 'Full inventory management capabilities',
                'module' => Permission::MODULE_INVENTORY,
                'category' => 'management',
                'action' => Permission::ACTION_MANAGE,
                'resource' => 'inventory',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 52,
            ],

            // Branch Management Permissions
            [
                'name' => 'branches.create',
                'display_name' => 'Create Branches',
                'description' => 'Create new branches',
                'module' => Permission::MODULE_BRANCHES,
                'category' => 'management',
                'action' => Permission::ACTION_CREATE,
                'resource' => 'branches',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 60,
            ],
            [
                'name' => 'branches.read',
                'display_name' => 'View Branches',
                'description' => 'View branch information',
                'module' => Permission::MODULE_BRANCHES,
                'category' => 'management',
                'action' => Permission::ACTION_READ,
                'resource' => 'branches',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 61,
            ],
            [
                'name' => 'branches.update',
                'display_name' => 'Update Branches',
                'description' => 'Update branch information',
                'module' => Permission::MODULE_BRANCHES,
                'category' => 'management',
                'action' => Permission::ACTION_UPDATE,
                'resource' => 'branches',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 62,
            ],
            [
                'name' => 'branches.delete',
                'display_name' => 'Delete Branches',
                'description' => 'Delete branches',
                'module' => Permission::MODULE_BRANCHES,
                'category' => 'management',
                'action' => Permission::ACTION_DELETE,
                'resource' => 'branches',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 63,
            ],

            // Warehouse Management Permissions
            [
                'name' => 'warehouses.create',
                'display_name' => 'Create Warehouses',
                'description' => 'Create new warehouses',
                'module' => Permission::MODULE_WAREHOUSES,
                'category' => 'management',
                'action' => Permission::ACTION_CREATE,
                'resource' => 'warehouses',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 70,
            ],
            [
                'name' => 'warehouses.read',
                'display_name' => 'View Warehouses',
                'description' => 'View warehouse information',
                'module' => Permission::MODULE_WAREHOUSES,
                'category' => 'management',
                'action' => Permission::ACTION_READ,
                'resource' => 'warehouses',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 71,
            ],
            [
                'name' => 'warehouses.update',
                'display_name' => 'Update Warehouses',
                'description' => 'Update warehouse information',
                'module' => Permission::MODULE_WAREHOUSES,
                'category' => 'management',
                'action' => Permission::ACTION_UPDATE,
                'resource' => 'warehouses',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 72,
            ],
            [
                'name' => 'warehouses.delete',
                'display_name' => 'Delete Warehouses',
                'description' => 'Delete warehouses',
                'module' => Permission::MODULE_WAREHOUSES,
                'category' => 'management',
                'action' => Permission::ACTION_DELETE,
                'resource' => 'warehouses',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 73,
            ],

            // Coupon Management Permissions
            [
                'name' => 'coupons.create',
                'display_name' => 'Create Coupons',
                'description' => 'Create new coupons',
                'module' => Permission::MODULE_COUPONS,
                'category' => 'management',
                'action' => Permission::ACTION_CREATE,
                'resource' => 'coupons',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 80,
            ],
            [
                'name' => 'coupons.read',
                'display_name' => 'View Coupons',
                'description' => 'View coupon information',
                'module' => Permission::MODULE_COUPONS,
                'category' => 'management',
                'action' => Permission::ACTION_READ,
                'resource' => 'coupons',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 81,
            ],
            [
                'name' => 'coupons.update',
                'display_name' => 'Update Coupons',
                'description' => 'Update coupon information',
                'module' => Permission::MODULE_COUPONS,
                'category' => 'management',
                'action' => Permission::ACTION_UPDATE,
                'resource' => 'coupons',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 82,
            ],
            [
                'name' => 'coupons.delete',
                'display_name' => 'Delete Coupons',
                'description' => 'Delete coupons',
                'module' => Permission::MODULE_COUPONS,
                'category' => 'management',
                'action' => Permission::ACTION_DELETE,
                'resource' => 'coupons',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 83,
            ],

            // Settings Management Permissions
            [
                'name' => 'settings.read',
                'display_name' => 'View Settings',
                'description' => 'View system settings',
                'module' => Permission::MODULE_SETTINGS,
                'category' => 'system',
                'action' => Permission::ACTION_READ,
                'resource' => 'settings',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 90,
            ],
            [
                'name' => 'settings.update',
                'display_name' => 'Update Settings',
                'description' => 'Update system settings',
                'module' => Permission::MODULE_SETTINGS,
                'category' => 'system',
                'action' => Permission::ACTION_UPDATE,
                'resource' => 'settings',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 91,
            ],

            // Currency Management Permissions
            [
                'name' => 'currencies.create',
                'display_name' => 'Create Currencies',
                'description' => 'Create new currencies',
                'module' => Permission::MODULE_CURRENCIES,
                'category' => 'system',
                'action' => Permission::ACTION_CREATE,
                'resource' => 'currencies',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 100,
            ],
            [
                'name' => 'currencies.read',
                'display_name' => 'View Currencies',
                'description' => 'View currency information',
                'module' => Permission::MODULE_CURRENCIES,
                'category' => 'system',
                'action' => Permission::ACTION_READ,
                'resource' => 'currencies',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 101,
            ],
            [
                'name' => 'currencies.update',
                'display_name' => 'Update Currencies',
                'description' => 'Update currency information',
                'module' => Permission::MODULE_CURRENCIES,
                'category' => 'system',
                'action' => Permission::ACTION_UPDATE,
                'resource' => 'currencies',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 102,
            ],
            [
                'name' => 'currencies.delete',
                'display_name' => 'Delete Currencies',
                'description' => 'Delete currencies',
                'module' => Permission::MODULE_CURRENCIES,
                'category' => 'system',
                'action' => Permission::ACTION_DELETE,
                'resource' => 'currencies',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 103,
            ],

            // Page Management Permissions
            [
                'name' => 'pages.create',
                'display_name' => 'Create Pages',
                'description' => 'Create new pages',
                'module' => Permission::MODULE_PAGES,
                'category' => 'content',
                'action' => Permission::ACTION_CREATE,
                'resource' => 'pages',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 110,
            ],
            [
                'name' => 'pages.read',
                'display_name' => 'View Pages',
                'description' => 'View page information',
                'module' => Permission::MODULE_PAGES,
                'category' => 'content',
                'action' => Permission::ACTION_READ,
                'resource' => 'pages',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 111,
            ],
            [
                'name' => 'pages.update',
                'display_name' => 'Update Pages',
                'description' => 'Update page information',
                'module' => Permission::MODULE_PAGES,
                'category' => 'content',
                'action' => Permission::ACTION_UPDATE,
                'resource' => 'pages',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 112,
            ],
            [
                'name' => 'pages.delete',
                'display_name' => 'Delete Pages',
                'description' => 'Delete pages',
                'module' => Permission::MODULE_PAGES,
                'category' => 'content',
                'action' => Permission::ACTION_DELETE,
                'resource' => 'pages',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 113,
            ],

            // Blog Management Permissions
            [
                'name' => 'blog.create',
                'display_name' => 'Create Blog Posts',
                'description' => 'Create new blog posts',
                'module' => Permission::MODULE_BLOG,
                'category' => 'content',
                'action' => Permission::ACTION_CREATE,
                'resource' => 'posts',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 120,
            ],
            [
                'name' => 'blog.read',
                'display_name' => 'View Blog Posts',
                'description' => 'View blog post information',
                'module' => Permission::MODULE_BLOG,
                'category' => 'content',
                'action' => Permission::ACTION_READ,
                'resource' => 'posts',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 121,
            ],
            [
                'name' => 'blog.update',
                'display_name' => 'Update Blog Posts',
                'description' => 'Update blog post information',
                'module' => Permission::MODULE_BLOG,
                'category' => 'content',
                'action' => Permission::ACTION_UPDATE,
                'resource' => 'posts',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 122,
            ],
            [
                'name' => 'blog.delete',
                'display_name' => 'Delete Blog Posts',
                'description' => 'Delete blog posts',
                'module' => Permission::MODULE_BLOG,
                'category' => 'content',
                'action' => Permission::ACTION_DELETE,
                'resource' => 'posts',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 123,
            ],

            // Analytics Permissions
            [
                'name' => 'analytics.read',
                'display_name' => 'View Analytics',
                'description' => 'View analytics and reports',
                'module' => Permission::MODULE_ANALYTICS,
                'category' => 'reports',
                'action' => Permission::ACTION_READ,
                'resource' => 'analytics',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 130,
            ],
            [
                'name' => 'analytics.export',
                'display_name' => 'Export Analytics',
                'description' => 'Export analytics data',
                'module' => Permission::MODULE_ANALYTICS,
                'category' => 'reports',
                'action' => Permission::ACTION_EXPORT,
                'resource' => 'analytics',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 131,
            ],

            // Reports Permissions
            [
                'name' => 'reports.read',
                'display_name' => 'View Reports',
                'description' => 'View system reports',
                'module' => Permission::MODULE_REPORTS,
                'category' => 'reports',
                'action' => Permission::ACTION_READ,
                'resource' => 'reports',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 140,
            ],
            [
                'name' => 'reports.export',
                'display_name' => 'Export Reports',
                'description' => 'Export system reports',
                'module' => Permission::MODULE_REPORTS,
                'category' => 'reports',
                'action' => Permission::ACTION_EXPORT,
                'resource' => 'reports',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 141,
            ],

            // System Permissions
            [
                'name' => 'system.manage',
                'display_name' => 'System Management',
                'description' => 'Full system management capabilities',
                'module' => Permission::MODULE_SYSTEM,
                'category' => 'system',
                'action' => Permission::ACTION_MANAGE,
                'resource' => 'system',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 150,
            ],
        ];
    }

    /**
     * Get role permission assignments.
     */
    private function getRolePermissionAssignments(): array
    {
        return [
            // Super Admin - All permissions
            User::ROLE_SUPER_ADMIN => [
                'users.create', 'users.read', 'users.update', 'users.delete', 'users.manage',
                'products.create', 'products.read', 'products.update', 'products.delete', 'products.manage',
                'categories.create', 'categories.read', 'categories.update', 'categories.delete',
                'orders.create', 'orders.read', 'orders.update', 'orders.delete', 'orders.manage',
                'customers.read', 'customers.update',
                'inventory.read', 'inventory.update', 'inventory.manage',
                'branches.create', 'branches.read', 'branches.update', 'branches.delete',
                'warehouses.create', 'warehouses.read', 'warehouses.update', 'warehouses.delete',
                'coupons.create', 'coupons.read', 'coupons.update', 'coupons.delete',
                'settings.read', 'settings.update',
                'currencies.create', 'currencies.read', 'currencies.update', 'currencies.delete',
                'pages.create', 'pages.read', 'pages.update', 'pages.delete',
                'blog.create', 'blog.read', 'blog.update', 'blog.delete',
                'analytics.read', 'analytics.export',
                'reports.read', 'reports.export',
                'system.manage',
            ],

            // Store Manager - Management permissions
            User::ROLE_STORE_MANAGER => [
                'users.read', 'users.update',
                'products.create', 'products.read', 'products.update', 'products.delete',
                'categories.create', 'categories.read', 'categories.update', 'categories.delete',
                'orders.create', 'orders.read', 'orders.update', 'orders.delete',
                'customers.read', 'customers.update',
                'inventory.read', 'inventory.update', 'inventory.manage',
                'branches.read', 'branches.update',
                'warehouses.read', 'warehouses.update',
                'coupons.create', 'coupons.read', 'coupons.update', 'coupons.delete',
                'settings.read',
                'currencies.read',
                'pages.create', 'pages.read', 'pages.update', 'pages.delete',
                'blog.create', 'blog.read', 'blog.update', 'blog.delete',
                'analytics.read', 'analytics.export',
                'reports.read', 'reports.export',
            ],

            // Product Manager - Product and inventory focus
            User::ROLE_PRODUCT_MANAGER => [
                'products.create', 'products.read', 'products.update', 'products.delete',
                'categories.create', 'categories.read', 'categories.update', 'categories.delete',
                'orders.read',
                'customers.read',
                'inventory.read', 'inventory.update',
                'branches.read',
                'warehouses.read',
                'coupons.read',
                'analytics.read',
                'reports.read',
            ],

            // Analytics Team - Read and export permissions
            User::ROLE_ANALYTICS_TEAM => [
                'products.read',
                'categories.read',
                'orders.read',
                'customers.read',
                'inventory.read',
                'branches.read',
                'warehouses.read',
                'coupons.read',
                'analytics.read', 'analytics.export',
                'reports.read', 'reports.export',
            ],

            // Customer Service - Customer and order focus
            User::ROLE_CUSTOMER_SERVICE => [
                'orders.read', 'orders.update',
                'customers.read', 'customers.update',
                'products.read',
                'categories.read',
                'branches.read',
                'warehouses.read',
                'coupons.read',
            ],
        ];
    }
}