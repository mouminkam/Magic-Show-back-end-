<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Legacy base columns (pre-_ar/_en) remain for backward compatibility but must be
 * nullable when admins save only localized fields (MySQL 1364 under strict mode).
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            return;
        }

        $mods = [
            'about_stats' => [
                'title' => 'VARCHAR(255) NULL',
            ],
            'testimonials' => [
                'customer_name' => 'VARCHAR(255) NULL',
                'text' => 'TEXT NULL',
            ],
            'team_members' => [
                'name' => 'VARCHAR(255) NULL',
                'role' => 'VARCHAR(255) NULL',
            ],
            'products' => [
                'name' => 'VARCHAR(255) NULL',
            ],
            'categories' => [
                'name' => 'VARCHAR(255) NULL',
            ],
            'branches' => [
                'name' => 'VARCHAR(255) NULL',
                'address' => 'VARCHAR(255) NULL',
            ],
            'blog_posts' => [
                'title' => 'VARCHAR(255) NULL',
                'content' => 'TEXT NULL',
            ],
        ];

        foreach ($mods as $table => $columns) {
            foreach ($columns as $column => $sqlType) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$sqlType}");
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            return;
        }

        // Restore NOT NULL where original migrations required it (may fail if NULL rows exist).
        $mods = [
            'about_stats' => ['title' => 'VARCHAR(255) NOT NULL'],
            'testimonials' => [
                'customer_name' => 'VARCHAR(255) NOT NULL',
                'text' => 'TEXT NOT NULL',
            ],
            'team_members' => [
                'name' => 'VARCHAR(255) NOT NULL',
                'role' => 'VARCHAR(255) NOT NULL',
            ],
            'products' => ['name' => 'VARCHAR(255) NOT NULL'],
            'categories' => ['name' => 'VARCHAR(255) NOT NULL'],
            'branches' => [
                'name' => 'VARCHAR(255) NOT NULL',
                'address' => 'VARCHAR(255) NOT NULL',
            ],
            'blog_posts' => [
                'title' => 'VARCHAR(255) NOT NULL',
                'content' => 'TEXT NOT NULL',
            ],
        ];

        foreach ($mods as $table => $columns) {
            foreach ($columns as $column => $sqlType) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$sqlType}");
            }
        }
    }
};
