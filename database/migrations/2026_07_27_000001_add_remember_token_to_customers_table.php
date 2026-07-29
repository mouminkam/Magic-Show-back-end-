<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * App\Models\Customer implements Illuminate\Contracts\Auth\Authenticatable via
 * the Authenticatable trait, whose getRememberTokenName() is 'remember_token'.
 * The customers table never had that column, so
 * AuthController::resetPassword() -> $customer->setRememberToken(...)->save()
 * produced:
 *
 *   SQLSTATE[HY000]: General error: 1 no such column: remember_token
 *
 * i.e. POST /api/v1/auth/reset-password returned HTTP 500 for every request and
 * the password reset feature was completely non-functional.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('customers', 'remember_token')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->rememberToken()->after('password');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('customers', 'remember_token')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }
};
