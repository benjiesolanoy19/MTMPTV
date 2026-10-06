<?php

namespace Tests\Feature;

use App\Models\{Application, Franchise, Operator, Permit, Report, RolePermission, User, Vehicle, Violation};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_adds_permissions_without_demo_accounts_or_records(): void
    {
        Artisan::call('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--no-interaction' => true,
        ]);

        $permissionCount = RolePermission::count();
        $this->assertGreaterThan(0, $permissionCount);
        $this->assertSame(0, User::count());
        $this->assertSame(0, Operator::count());
        $this->assertSame(0, Vehicle::count());
        $this->assertSame(0, Application::count());
        $this->assertSame(0, Franchise::count());
        $this->assertSame(0, Permit::count());
        $this->assertSame(0, Violation::count());
        $this->assertSame(0, Report::count());

        Artisan::call('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--no-interaction' => true,
        ]);

        $this->assertSame($permissionCount, RolePermission::count());
        $this->assertSame(0, User::count());
        $this->assertSame(0, Report::count());
    }
}
