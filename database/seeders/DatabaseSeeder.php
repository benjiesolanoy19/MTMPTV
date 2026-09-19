<?php

namespace Database\Seeders;

use App\Models\{Application, Franchise, Operator, Permit, RolePermission, Vehicle, Violation};
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'admin' => [],
            'staff' => ['view dashboard', 'view operators', 'manage operators', 'view vehicles', 'manage vehicles', 'view applications', 'create applications', 'manage applications', 'view franchises', 'manage franchises', 'view permits', 'manage permits', 'view renewals', 'manage renewals', 'view violations', 'manage violations', 'view reports', 'view my reports', 'view notifications'],
            'viewer' => ['view dashboard', 'view operators', 'view vehicles', 'view franchises', 'view permits', 'view renewals', 'view violations', 'view reports', 'view my reports', 'view notifications'],
            'operator' => ['view dashboard', 'operator portal', 'operator applications', 'operator permits', 'operator franchises', 'operator renewals', 'operator violations', 'operator notifications'],
            'vehicle_owner' => ['view dashboard', 'vehicle owner portal', 'vehicle owner vehicles', 'vehicle owner applications', 'vehicle owner permits', 'vehicle owner franchises', 'vehicle owner renewals', 'vehicle owner violations', 'vehicle owner notifications'],
        ];
        foreach ($permissions as $role => $rolePermissions) {
            foreach ($rolePermissions as $permission) RolePermission::create(['role' => $role, 'permission' => $permission]);
        }

        $admin = User::create(['name' => 'System Administrator', 'username' => 'admin', 'email' => 'admin@transitdesk.test', 'mobile_number' => '09170000001', 'address' => 'Municipal Hall, Poblacion', 'password' => Hash::make('password'), 'role' => 'admin']);
        $staff = User::create(['name' => 'Operations Staff', 'username' => 'staff', 'email' => 'staff@transitdesk.test', 'mobile_number' => '09170000002', 'address' => 'Municipal Hall, Poblacion', 'password' => Hash::make('password'), 'role' => 'staff']);
        User::create(['name' => 'Records Viewer', 'username' => 'viewer', 'email' => 'viewer@transitdesk.test', 'mobile_number' => '09170000003', 'address' => 'Municipal Hall, Poblacion', 'password' => Hash::make('password'), 'role' => 'viewer']);
        $operators = collect(range(1, 12))->map(fn($number) => Operator::create(['operator_code' => 'OP-'.str_pad($number, 4, '0', STR_PAD_LEFT), 'first_name' => ['Ramon','Lina','Joel','Marites','Carlos','Nena'][$number % 6], 'middle_name' => 'Santos', 'last_name' => ['Dela Cruz','Garcia','Reyes','Mendoza','Navarro','Villanueva'][$number % 6], 'address' => $number.' Municipal Road, Poblacion', 'contact_number' => '09'.str_pad((string)(170000000 + $number), 9, '0', STR_PAD_LEFT), 'email' => 'operator'.$number.'@example.test', 'valid_id_type' => 'Driver License', 'valid_id_number' => 'DL-'.str_pad($number, 6, '0', STR_PAD_LEFT), 'status' => 'active']));
        $vehicles = $operators->map(function ($operator, $index) { return Vehicle::create(['vehicle_code' => 'VH-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT), 'operator_id' => $operator->id, 'plate_number' => 'TRI-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT), 'engine_number' => 'ENG-'.str_pad($index + 1, 5, '0', STR_PAD_LEFT), 'chassis_number' => 'CHS-'.str_pad($index + 1, 5, '0', STR_PAD_LEFT), 'vehicle_type' => 'Tricycle', 'make' => 'Honda', 'model' => 'TMX 125', 'color' => $index % 2 ? 'Blue' : 'Red', 'year_model' => 2020 + ($index % 5), 'registration_number' => 'REG-'.str_pad($index + 1, 5, '0', STR_PAD_LEFT), 'registration_expiry' => now()->addMonths(2 + $index), 'status' => 'active']); });
        $vehicles->take(8)->each(function ($vehicle, $index) use ($staff) { $application = Application::create(['application_number' => 'APP-2026-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT), 'operator_id' => $vehicle->operator_id, 'vehicle_id' => $vehicle->id, 'application_type' => $index % 2 ? 'New Permit' : 'New Franchise', 'date_submitted' => now()->subDays($index + 1), 'status' => ['Pending','Under Review','Approved','Rejected'][$index % 4], 'reviewed_by' => $index % 4 > 1 ? $staff->id : null, 'reviewed_at' => $index % 4 > 1 ? now()->subDays($index) : null]); if ($application->status === 'Approved') { Franchise::create(['franchise_number' => 'FR-2026-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT), 'operator_id' => $vehicle->operator_id, 'vehicle_id' => $vehicle->id, 'application_id' => $application->id, 'issue_date' => now()->subMonths(3), 'expiry_date' => now()->addDays(15 + $index * 40), 'status' => 'Active']); Permit::create(['permit_number' => 'PT-2026-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT), 'operator_id' => $vehicle->operator_id, 'vehicle_id' => $vehicle->id, 'application_id' => $application->id, 'issue_date' => now()->subMonths(2), 'expiry_date' => now()->addMonths(8), 'status' => 'Active']); } });
        $vehicles->take(5)->each(fn($vehicle, $index) => Violation::create(['violation_number' => 'VIO-2026-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT), 'operator_id' => $vehicle->operator_id, 'vehicle_id' => $vehicle->id, 'violation_type' => ['Overloading','No valid permit','Improper parking'][$index % 3], 'violation_date' => now()->subDays($index + 3), 'location' => 'Public Market Road', 'description' => 'Recorded during routine enforcement inspection.', 'penalty_amount' => 500 + ($index * 250), 'payment_status' => $index % 3 === 0 ? 'Paid' : 'Unpaid', 'payment_date' => $index % 3 === 0 ? now()->subDays(1) : null, 'recorded_by' => $admin->id]));
    }
}
