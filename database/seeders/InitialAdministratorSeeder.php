<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialAdministratorSeeder extends Seeder
{
    public function run(): void
    {
        $username = config('admin.initial_username');
        $password = config('admin.initial_password');

        $existingAdministrator = User::where('username', $username)->first();
        if ($existingAdministrator) {
            if ($existingAdministrator->role !== 'admin' || $existingAdministrator->status !== 'active') {
                $this->command?->warn('The initial administrator username already belongs to an existing account; no role, status, or password was changed.');
            }

            return;
        }

        if (! is_string($password) || $password === '') {
            $this->command?->warn('Initial administrator was not created; configure INITIAL_ADMIN_PASSWORD in the trusted environment before seeding.');

            return;
        }

        $email = config('admin.initial_email');
        if (User::where('email', $email)->exists()) {
            $this->command?->error('Initial administrator was not created; INITIAL_ADMIN_EMAIL is already assigned to another account.');

            return;
        }

        User::create([
            'name' => 'System Administrator',
            'username' => $username,
            'email' => $email,
            'mobile_number' => 'Not provided',
            'address' => 'Not provided',
            'password' => Hash::make($password),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }
}
