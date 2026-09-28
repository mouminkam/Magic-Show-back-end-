# Magic Shoe Authentication System

## Overview
This document describes the complete authentication system implemented for the Magic Shoe admin dashboard with role-based access control.

## User Roles
The system supports the following user roles:

1. **Super Admin** (`super_admin`)
   - Full system access
   - Can manage all aspects of the application

2. **Store Manager** (`store_manager`)
   - Store management privileges
   - Can manage store operations

3. **Product Manager** (`product_manager`)
   - Product management privileges
   - Can manage product catalog

4. **Analytics Team** (`analytics_team`)
   - Analytics and reporting access
   - Can view analytics dashboards

5. **Customer Service** (`customer_service`)
   - Customer support access
   - Can handle customer inquiries

## Default Users
The system comes with pre-configured users for each role:

- **Super Admin**: `admin@magicshoe.test` / `password`
- **Store Manager**: `manager@magicshoe.test` / `password`
- **Product Manager**: `product@magicshoe.test` / `password`
- **Analytics Team**: `analytics@magicshoe.test` / `password`
- **Customer Service**: `support@magicshoe.test` / `password`

## User Model Methods

### Role Checking Methods
```php
// Check specific role
$user->hasRole('super_admin');

// Check multiple roles
$user->hasAnyRole(['super_admin', 'store_manager']);

// Specific role checks
$user->isSuperAdmin();
$user->isStoreManager();
$user->isProductManager();
$user->isAnalyticsTeam();
$user->isCustomerService();

// Privilege level checks
$user->isAdmin(); // super_admin or store_manager
$user->isManager(); // super_admin, store_manager, or product_manager
```

### Role Constants
```php
User::ROLES // Array of all available roles
$user->role_display_name // Human-readable role name
```

## Middleware Usage

### CheckRole Middleware
```php
// Single role
Route::middleware(['role:super_admin'])->group(function () {
    // Super admin only routes
});

// Multiple roles
Route::middleware(['role:super_admin,store_manager'])->group(function () {
    // Admin routes
});
```

### CheckAdmin Middleware
```php
Route::middleware(['admin'])->group(function () {
    // Admin routes (super_admin or store_manager)
});
```

## Authentication Routes

### Standard Auth Routes
- `GET /login` - Login page
- `POST /login` - Process login
- `GET /register` - Registration page
- `POST /register` - Process registration
- `POST /logout` - Logout user
- `GET /home` - User dashboard

### Role-Based Test Routes
- `GET /admin/dashboard` - Super Admin only
- `GET /admin/management` - Admin (Super Admin or Store Manager)
- `GET /products/manage` - Product Manager or Super Admin
- `GET /analytics/dashboard` - Analytics Team or Super Admin
- `GET /customer/support` - Customer Service or Super Admin

## Database Structure

### Users Table
```sql
users
├── id (primary key)
├── name
├── email (unique)
├── email_verified_at
├── password
├── role (enum: super_admin, store_manager, product_manager, analytics_team, customer_service)
└── remember_token
```

## Installation Steps Completed

1. ✅ Laravel UI authentication scaffolding generated
2. ✅ Role column added to users table via migration
3. ✅ User model updated with role management methods
4. ✅ CheckRole and CheckAdmin middleware created
5. ✅ Middleware registered in bootstrap/app.php
6. ✅ RegisterController updated for role selection
7. ✅ Registration form updated with role dropdown
8. ✅ AdminUserSeeder created with default users
9. ✅ Test routes added for role-based access control

## Usage Examples

### In Controllers
```php
public function dashboard()
{
    $user = auth()->user();
    
    if ($user->isSuperAdmin()) {
        // Super admin dashboard logic
    } elseif ($user->isStoreManager()) {
        // Store manager dashboard logic
    }
}
```

### In Blade Templates
```php
@if(auth()->user()->isAdmin())
    <a href="/admin/management">Admin Panel</a>
@endif

@if(auth()->user()->hasRole('product_manager'))
    <a href="/products/manage">Manage Products</a>
@endif
```

## Security Features

- Role-based access control
- Middleware protection for routes
- Password hashing
- Email verification support
- Session management
- CSRF protection

## Testing

Run the test script to verify the system:
```bash
php test_auth.php
```

## Next Steps

1. Run database seeder to create default users
2. Test authentication flow
3. Customize views as needed
4. Implement additional role-based features
5. Add audit logging for admin actions
