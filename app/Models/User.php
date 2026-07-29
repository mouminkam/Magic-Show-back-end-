<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // HasApiTokens: the admin API routes (api/v1/admin/*) authenticate with
    // `auth:sanctum` + the `admin.api` middleware, which requires a User-owned
    // personal access token. Without this trait User::createToken() does not
    // exist and no admin token could ever be minted.
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Role Constants
     */
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_STORE_MANAGER = 'store_manager';
    const ROLE_PRODUCT_MANAGER = 'product_manager';
    const ROLE_ANALYTICS_TEAM = 'analytics_team';
    const ROLE_CUSTOMER_SERVICE = 'customer_service';

    /**
     * Available user roles with display names
     */
    public const ROLES = [
        self::ROLE_SUPER_ADMIN => 'Super Admin',
        self::ROLE_STORE_MANAGER => 'Store Manager',
        self::ROLE_PRODUCT_MANAGER => 'Product Manager',
        self::ROLE_ANALYTICS_TEAM => 'Analytics Team',
        self::ROLE_CUSTOMER_SERVICE => 'Customer Service',
    ];

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user is store manager
     */
    public function isStoreManager(): bool
    {
        return $this->role === self::ROLE_STORE_MANAGER;
    }

    /**
     * Check if user is product manager
     */
    public function isProductManager(): bool
    {
        return $this->role === self::ROLE_PRODUCT_MANAGER;
    }

    /**
     * Check if user is analytics team member
     */
    public function isAnalyticsTeam(): bool
    {
        return $this->role === self::ROLE_ANALYTICS_TEAM;
    }

    /**
     * Check if user is customer service
     */
    public function isCustomerService(): bool
    {
        return $this->role === self::ROLE_CUSTOMER_SERVICE;
    }

    /**
     * Get user role display name
     */
    public function getRoleDisplayNameAttribute(): string
    {
        return self::ROLES[$this->role] ?? 'Unknown Role';
    }

    /**
     * Check if user has admin privileges (super admin or store manager)
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole([self::ROLE_SUPER_ADMIN, self::ROLE_STORE_MANAGER]);
    }

    /**
     * Check if user has management privileges
     */
    public function isManager(): bool
    {
        return $this->hasAnyRole([self::ROLE_SUPER_ADMIN, self::ROLE_STORE_MANAGER, self::ROLE_PRODUCT_MANAGER]);
    }

    /**
     * Get the permissions for this user's role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role',
            'permission_id',
            'role',
            'id'
        )->withPivot(['is_granted', 'conditions'])->withTimestamps();
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check cached permissions first
        $userPermissions = $this->getCachedPermissions();
        
        return in_array($permission, $userPermissions);
    }

    /**
     * Check if user has any of the specified permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        $userPermissions = $this->getCachedPermissions();
        
        return !empty(array_intersect($permissions, $userPermissions));
    }

    /**
     * Check if user has all of the specified permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        $userPermissions = $this->getCachedPermissions();
        
        return empty(array_diff($permissions, $userPermissions));
    }

    /**
     * Check if user can perform an action on a module.
     */
    public function canPerform(string $action, string $module, string $resource = null): bool
    {
        $permission = $resource ? "{$module}.{$resource}.{$action}" : "{$module}.{$action}";
        
        return $this->hasPermission($permission);
    }

    /**
     * Check if user can create in a module.
     */
    public function canCreate(string $module, string $resource = null): bool
    {
        return $this->canPerform(Permission::ACTION_CREATE, $module, $resource);
    }

    /**
     * Check if user can read in a module.
     */
    public function canRead(string $module, string $resource = null): bool
    {
        return $this->canPerform(Permission::ACTION_READ, $module, $resource);
    }

    /**
     * Check if user can update in a module.
     */
    public function canUpdate(string $module, string $resource = null): bool
    {
        return $this->canPerform(Permission::ACTION_UPDATE, $module, $resource);
    }

    /**
     * Check if user can delete in a module.
     */
    public function canDelete(string $module, string $resource = null): bool
    {
        return $this->canPerform(Permission::ACTION_DELETE, $module, $resource);
    }

    /**
     * Check if user can manage a module.
     */
    public function canManage(string $module, string $resource = null): bool
    {
        return $this->canPerform(Permission::ACTION_MANAGE, $module, $resource);
    }

    /**
     * Get all permissions for this user's role (cached).
     */
    public function getCachedPermissions(): array
    {
        $cacheKey = "user_permissions_{$this->id}_{$this->role}";
        
        return Cache::remember($cacheKey, 3600, function () {
            return $this->getPermissions();
        });
    }

    /**
     * Get all permissions for this user's role (uncached).
     */
    public function getPermissions(): array
    {
        return Permission::getPermissionsForRole($this->role);
    }

    /**
     * Get permissions grouped by module for this user's role.
     */
    public function getPermissionsByModule(): array
    {
        $cacheKey = "user_permissions_by_module_{$this->id}_{$this->role}";
        
        return Cache::remember($cacheKey, 3600, function () {
            $permissions = Permission::whereHas('roles', function ($query) {
                $query->where('role', $this->role)->where('is_granted', true);
            })->active()->ordered()->get();

            return $permissions->groupBy('module')->toArray();
        });
    }

    /**
     * Clear permission cache for this user.
     */
    public function clearPermissionCache(): void
    {
        Cache::forget("user_permissions_{$this->id}_{$this->role}");
        Cache::forget("user_permissions_by_module_{$this->id}_{$this->role}");
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear permission cache when user role is updated
        static::saved(function ($user) {
            if ($user->isDirty('role')) {
                $user->clearPermissionCache();
            }
        });
    }

    /**
     * Get user's accessible modules based on permissions.
     */
    public function getAccessibleModules(): array
    {
        $permissions = $this->getPermissionsByModule();
        
        return array_keys($permissions);
    }

    /**
     * Check if user has access to a specific module.
     */
    public function hasModuleAccess(string $module): bool
    {
        return in_array($module, $this->getAccessibleModules());
    }

    /**
     * Get user's permission summary for display.
     */
    public function getPermissionSummary(): array
    {
        $permissions = $this->getPermissionsByModule();
        $summary = [];

        foreach ($permissions as $module => $modulePermissions) {
            $summary[$module] = [
                'module' => $module,
                'display_name' => Permission::MODULES[$module] ?? ucfirst($module),
                'permissions' => count($modulePermissions),
                'actions' => array_unique(array_column($modulePermissions, 'action')),
            ];
        }

        return $summary;
    }
}
