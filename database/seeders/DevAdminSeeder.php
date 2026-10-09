<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The known-credential admin for LOCAL development only (test@example.com /
 * password), so every `migrate:fresh --seed` has a working /admin login.
 * It refuses to run anywhere else: staging and production create their admin
 * with `php artisan make:filament-user` (see SETUP "First run").
 */
class DevAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        User::updateOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password', 'email_verified_at' => now()],
        );
    }
}
