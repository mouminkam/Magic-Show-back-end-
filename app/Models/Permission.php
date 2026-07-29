<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Permission extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'category',
        'action',
        'resource',
        'is_system',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Permission action constants.
     */
    const ACTION_CREATE = 'create';
    const ACTION_READ = 'read';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_MANAGE = 'manage';
    const ACTION_EXPORT = 'export';
    const ACTION_IMPORT = 'import';
    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT = 'reject';

    /**
     * Available permission actions with display names.
     */
    public const ACTIONS = [
        self::ACTION_CREATE => 'Create',
        self::ACTION_READ => 'Read',
        self::ACTION_UPDATE => 'Update',
        self::ACTION_DELETE => 'Delete',
        self::ACTION_MANAGE => 'Manage',
        self::ACTION_EXPORT => 'Export',
        self::ACTION_IMPORT => 'Import',
        self::ACTION_APPROVE => 'Approve',
        self::ACTION_REJECT => 'Reject',
    ];

    /**
     * Permission module constants.
     */
    const MODULE_USERS = 'users';
    const MODULE_PRODUCTS = 'products';
    const MODULE_CATEGORIES = 'categories';
    const MODULE_ORDERS = 'orders';
    const MODULE_CUSTOMERS = 'customers';
    const MODULE_INVENTORY = 'inventory';
    const MODULE_BRANCHES = 'branches';
    const MODULE_WAREHOUSES = 'warehouses';
    const MODULE_COUPONS = 'coupons';
    const MODULE_SETTINGS = 'settings';
    const MODULE_CURRENCIES = 'currencies';
    const MODULE_PAGES = 'pages';
    const MODULE_BLOG = 'blog';
    const MODULE_ANALYTICS = 'analytics';
    const MODULE_REPORTS = 'reports';
    const MODULE_SYSTEM = 'system';

    /**
     * Available permission modules with display names.
     */
    public const MODULES = [
        self::MODULE_USERS => 'Users',
        self::MODULE_PRODUCTS => 'Products',
        self::MODULE_CATEGORIES => 'Categories',
        self::MODULE_ORDERS => 'Orders',
        self::MODULE_CUSTOMERS => 'Customers',
        self::MODULE_INVENTORY => 'Inventory',
        self::MODULE_BRANCHES => 'Branches',
        self::MODULE_WAREHOUSES => 'Warehouses',
        self::MODULE_COUPONS => 'Coupons',
        self::MODULE_SETTINGS => 'Settings',
        self::MODULE_CURRENCIES => 'Currencies',
        self::MODULE_PAGES => 'Pages',
        self::MODULE_BLOG => 'Blog',
        self::MODULE_ANALYTICS => 'Analytics',
        self::MODULE_REPORTS => 'Reports',
        self::MODULE_SYSTEM => 'System',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when permissions are updated
        static::saved(function ($permission) {
            Cache::forget('permissions');
            Cache::forget('permissions_by_module');
            Cache::forget('permissions_by_role');
        });

        static::deleted(function ($permission) {
            Cache::forget('permissions');
            Cache::forget('permissions_by_module');
            Cache::forget('permissions_by_role');
        });
    }

    /**
     * Get the roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'role_permissions',
            'permission_id',
            'role',
            'id',
            'role'
        )->withPivot(['is_granted', 'conditions'])->withTimestamps();
    }

    /**
     * Scope a query to only include active permissions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include system permissions.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to filter by module.
     */
    public function scopeInModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeInCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to filter by action.
     */
    public function scopeWithAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('display_name');
    }

    /**
     * Get the action display name.
     */
    public function getActionDisplayNameAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? 'Unknown';
    }

    /**
     * Get the module display name.
     */
    public function getModuleDisplayNameAttribute(): string
    {
        return self::MODULES[$this->module] ?? 'Unknown';
    }

    /**
     * Check if this permission is a system permission.
     */
    public function isSystem(): bool
    {
        return $this->is_system;
    }

    /**
     * Check if this permission is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get all permissions grouped by module.
     */
    public static function getPermissionsByModule(): array
    {
        return Cache::remember('permissions_by_module', 3600, function () {
            return static::active()
                ->ordered()
                ->get()
                ->groupBy('module')
                ->toArray();
        });
    }

    /**
     * Get permissions for a specific role.
     */
    public static function getPermissionsForRole(string $role): array
    {
        return Cache::remember("permissions_by_role_{$role}", 3600, function () use ($role) {
            return static::whereHas('roles', function ($query) use ($role) {
                $query->where('role', $role)->where('is_granted', true);
            })->pluck('name')->toArray();
        });
    }

    /**
     * Create a permission with standard naming convention.
     */
    public static function createPermission(
        string $module,
        string $action,
        string $resource = null,
        string $category = 'general',
        string $description = null
    ): self {
        $name = $resource ? "{$module}.{$resource}.{$action}" : "{$module}.{$action}";
        $displayName = $resource 
            ? ucfirst($resource) . ' ' . self::ACTIONS[$action] 
            : self::MODULES[$module] . ' ' . self::ACTIONS[$action];

        return static::create([
            'name' => $name,
            'display_name' => $displayName,
            'description' => $description,
            'module' => $module,
            'category' => $category,
            'action' => $action,
            'resource' => $resource,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Clear all permission cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('permissions');
        Cache::forget('permissions_by_module');
        Cache::forget('permissions_by_role');
    }
}
