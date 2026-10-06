<?php

namespace Tests\Feature;

use App\Models\{Application, Operator, Report, RolePermission, User, Vehicle};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function grant(string $role, array $permissions): void
    {
        foreach ($permissions as $permission) {
            RolePermission::create(['role' => $role, 'permission' => $permission]);
        }
    }

    private function owner(string $username): array
    {
        $user = User::factory()->create(['role' => 'vehicle_owner', 'username' => $username]);
        $operator = Operator::create([
            'user_id' => $user->id,
            'operator_code' => 'VO-'.$username,
            'first_name' => $user->name,
            'last_name' => '',
            'address' => $user->address,
            'contact_number' => $user->mobile_number,
            'email' => $user->email,
            'status' => 'active',
        ]);
        return [$user, $operator];
    }

    private function vehicle(Operator $operator, string $code): Vehicle
    {
        return Vehicle::create([
            'vehicle_code' => $code,
            'operator_id' => $operator->id,
            'plate_number' => 'PLATE-'.$code,
            'engine_number' => 'PRIVATE-'.$code,
            'chassis_number' => 'PRIVATE-CHASSIS-'.$code,
            'registration_number' => 'PRIVATE-REG-'.$code,
            'vehicle_type' => 'Tricycle',
            'status' => 'active',
        ]);
    }

    public function test_viewer_api_is_read_only_for_authorized_public_resources_and_reports_are_scoped(): void
    {
        $this->grant('viewer', [
            'view dashboard', 'view vehicles', 'view reports', 'view my reports',
            'view operators', 'view franchises', 'view permits', 'view renewals',
            'view violations', 'view notifications',
        ]);
        $viewer = User::factory()->create(['role' => 'viewer']);
        $operator = Operator::create([
            'operator_code' => 'PUBLIC-OP',
            'first_name' => 'Public',
            'last_name' => 'Operator',
            'address' => 'Public Road',
            'contact_number' => '09171234567',
            'status' => 'active',
        ]);
        $vehicle = $this->vehicle($operator, 'PUBLIC-VEHICLE');
        $ownReport = Report::create([
            'report_number' => 'RPT-OWN',
            'submitted_by' => $viewer->id,
            'report_type' => 'Road obstruction',
            'date_submitted' => today(),
            'location' => 'Own report location',
            'description' => 'Own report.',
            'status' => 'Submitted',
        ]);
        Report::create([
            'report_number' => 'RPT-OTHER',
            'report_type' => 'Road obstruction',
            'date_submitted' => today(),
            'location' => 'Another report location',
            'description' => 'Another report.',
            'status' => 'Submitted',
        ]);

        $this->actingAs($viewer)->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('stats.my_reports', 1)
            ->assertJsonMissingPath('stats.operators');
        $this->actingAs($viewer)->getJson('/api/vehicles')->assertOk()
            ->assertJsonFragment(['id' => $vehicle->id])
            ->assertJsonMissing(['engine_number' => $vehicle->engine_number])
            ->assertJsonMissing(['chassis_number' => $vehicle->chassis_number])
            ->assertJsonMissing(['registration_number' => $vehicle->registration_number]);
        $this->actingAs($viewer)->getJson('/api/reports')->assertOk()
            ->assertJsonFragment(['report_number' => $ownReport->report_number])
            ->assertJsonMissing(['report_number' => 'RPT-OTHER']);
        $this->actingAs($viewer)->getJson('/api/applications')->assertForbidden();
        $this->actingAs($viewer)->postJson('/api/applications', [])->assertForbidden();
    }

    public function test_vehicle_owner_api_records_and_dashboard_are_limited_to_their_own_operator_profile(): void
    {
        $this->grant('vehicle_owner', [
            'view dashboard', 'vehicle owner portal', 'vehicle owner vehicles',
            'vehicle owner applications', 'vehicle owner permits',
        ]);
        [$owner, $ownerOperator] = $this->owner('owner_api');
        [, $otherOperator] = $this->owner('other_api');
        $ownedVehicle = $this->vehicle($ownerOperator, 'OWNED');
        $otherVehicle = $this->vehicle($otherOperator, 'OTHER');
        $application = Application::create([
            'application_number' => 'APP-OWNER-OWN',
            'operator_id' => $ownerOperator->id,
            'vehicle_id' => $ownedVehicle->id,
            'application_type' => 'New Permit',
            'date_submitted' => today(),
            'status' => 'Pending',
        ]);
        $otherApplication = Application::create([
            'application_number' => 'APP-OWNER-OTHER',
            'operator_id' => $otherOperator->id,
            'vehicle_id' => $otherVehicle->id,
            'application_type' => 'New Permit',
            'date_submitted' => today(),
            'status' => 'Pending',
        ]);

        $this->actingAs($owner)->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('stats.vehicles', 1)
            ->assertJsonMissingPath('stats.operators');
        $this->actingAs($owner)->getJson('/api/vehicles')->assertOk()
            ->assertJsonFragment(['id' => $ownedVehicle->id])
            ->assertJsonMissing(['id' => $otherVehicle->id]);
        $this->actingAs($owner)->getJson('/api/vehicles/'.$otherVehicle->id)->assertNotFound();
        $this->actingAs($owner)->getJson('/api/applications')->assertOk()
            ->assertJsonFragment(['application_number' => $application->application_number])
            ->assertJsonMissing(['application_number' => $otherApplication->application_number]);
        $this->actingAs($owner)->getJson('/api/applications/'.$otherApplication->id)->assertNotFound();
        $this->actingAs($owner)->postJson('/api/applications', [
            'vehicle_id' => $ownedVehicle->id,
            'operator_id' => $otherOperator->id,
            'application_type' => 'Registration',
        ])->assertCreated()->assertJsonPath('operator_id', $ownerOperator->id);
        $this->actingAs($owner)->postJson('/api/applications', [
            'vehicle_id' => $otherVehicle->id,
            'application_type' => 'Registration',
        ])->assertForbidden();
    }

    public function test_staff_cannot_gain_permissions_outside_their_role_matrix_or_manage_role_assignments(): void
    {
        $this->grant('staff', ['view dashboard', 'manage users', 'vehicle owner vehicles']);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->assertFalse($staff->hasPermission('manage users'));
        $this->assertFalse($staff->hasPermission('vehicle owner vehicles'));
        $this->actingAs($staff)->get(route('users.index'))->assertForbidden();
        $this->actingAs($staff)->patch(route('users.update', $staff), [
            'role' => 'admin',
            'status' => 'active',
        ])->assertForbidden();
        $this->actingAs($staff)->put(route('permissions.update', 'viewer'), [
            'permissions' => ['manage users'],
        ])->assertForbidden();
    }

    public function test_administrator_cannot_grant_permissions_outside_a_role_allowlist(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)->get(route('permissions.index'))->assertOk();
        $this->actingAs($administrator)->put(route('permissions.update', 'viewer'), [
            'permissions' => ['manage users'],
        ])->assertSessionHasErrors('permissions.0');

        $this->assertDatabaseMissing('role_permissions', [
            'role' => 'viewer',
            'permission' => 'manage users',
        ]);
    }

    public function test_inactive_accounts_and_public_registration_cannot_use_admin_privileges(): void
    {
        $inactiveAdmin = User::factory()->create(['role' => 'admin', 'status' => 'suspended']);

        $this->actingAs($inactiveAdmin)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($inactiveAdmin)->get(route('profile.show'))->assertForbidden();
        $this->actingAs($inactiveAdmin)->getJson('/api/user')->assertForbidden();

        $this->postJson('/api/register', [
            'name' => 'Unauthorized Admin',
            'username' => 'unauthorized.admin',
            'email' => 'unauthorized.admin@example.test',
            'password' => 'SecurePass1',
            'address' => 'Municipal Road',
            'mobile_number' => '09171234567',
            'role' => 'admin',
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('users', ['username' => 'unauthorized.admin']);
    }
}
