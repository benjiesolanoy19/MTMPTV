<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use App\Support\RolePermissionMatrix;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RolePermissionMatrix::PERMISSIONS as $role => $rolePermissions) {
            foreach ($rolePermissions as $permission) {
                RolePermission::updateOrCreate(['role' => $role, 'permission' => $permission]);
            }
        }

        $this->call(InitialAdministratorSeeder::class);
    }
}
