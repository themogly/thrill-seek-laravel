<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DevAdminSeeder;
use Filament\Facades\Filament;
use Tests\TestCase;
use Tests\Unit\Architecture\SourceFiles;

/**
 * Seeding a server must never create a login with published credentials. The
 * known dev admin (test@example.com / password) exists only on a local machine;
 * staging and production get their admin from `php artisan make:filament-user`.
 */
class NoSeededAdminOnServersTest extends TestCase
{
    public function test_seeding_production_creates_no_users(): void
    {
        app()->detectEnvironment(fn (): string => 'production');

        // As a deploy would run it: production asks for confirmation without --force.
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertSame(0, User::count(), 'db:seed on a server created a user with known credentials.');
    }

    public function test_seeding_staging_creates_no_users(): void
    {
        app()->detectEnvironment(fn (): string => 'staging');

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::count());
    }

    public function test_the_dev_admin_seeder_refuses_to_run_off_local(): void
    {
        app()->detectEnvironment(fn (): string => 'production');

        $this->artisan('db:seed', ['--class' => DevAdminSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertSame(0, User::count());
    }

    public function test_local_seeding_gives_the_known_dev_admin_who_can_reach_the_panel(): void
    {
        app()->detectEnvironment(fn (): string => 'local');

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class); // idempotent: a second run doesn't duplicate

        $admin = User::sole();
        $this->assertSame('test@example.com', $admin->email);
        $this->assertTrue(password_verify('password', $admin->password));
        $this->assertTrue($admin->canAccessPanel(Filament::getPanel('admin')));
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_the_documented_server_path_creates_a_working_admin(): void
    {
        $this->artisan('make:filament-user', [
            '--name' => 'Owner',
            '--email' => 'owner@example.test',
            '--password' => 'a-long-unique-passphrase',
            '--panel' => 'admin',
        ])->assertSuccessful();

        $owner = User::where('email', 'owner@example.test')->sole();
        $this->assertTrue(password_verify('a-long-unique-passphrase', $owner->password));
        $this->actingAs($owner)->get('/admin')->assertOk();
    }

    /**
     * canAccessPanel() returns true for every User, which is only safe while every
     * User is staff. Nothing in the app may create a User: customers are the
     * separate Customer model, newsletter subscribers and inbound mail have their
     * own tables. Staff are created with `make:filament-user` (vendor) or, locally,
     * the DevAdminSeeder.
     */
    public function test_nothing_in_the_app_creates_user_rows(): void
    {
        $offenders = [];
        foreach ([...SourceFiles::under('app', '.php'), ...SourceFiles::under('routes', '.php')] as $path) {
            $source = (string) file_get_contents($path);
            if (preg_match('/\bUser::(create|forceCreate|firstOrCreate|updateOrCreate|factory|query\(\)->create|insert)\b|new\s+User\s*\(/', $source)) {
                $offenders[] = SourceFiles::relative($path);
            }
        }

        $this->assertSame([], $offenders, 'Only make:filament-user / DevAdminSeeder may create staff users — canAccessPanel() trusts every User:');
    }
}
