<?php

namespace Tests\Feature;

use App\Models\{Application, Operator, RolePermission, User, Vehicle};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperatorPortalTest extends TestCase
{
    use RefreshDatabase;

    private function operatorUser(string $username): array
    {
        foreach (['view dashboard', 'operator portal', 'operator applications', 'operator permits', 'operator franchises', 'operator renewals', 'operator violations', 'operator notifications'] as $permission) {
            RolePermission::firstOrCreate(['role' => 'operator', 'permission' => $permission]);
        }
        $user = User::factory()->create(['username' => $username, 'role' => 'operator']);
        $operator = Operator::create(['user_id' => $user->id, 'operator_code' => 'OP-'.$username, 'first_name' => $user->name, 'last_name' => 'Driver', 'address' => 'Municipal Road', 'contact_number' => $user->mobile_number, 'email' => $user->email, 'status' => 'active']);
        return [$user, $operator];
    }

    public function test_operator_dashboard_and_owned_pages_load(): void
    {
        [$user, $operator] = $this->operatorUser('operator_one');
        $vehicle = Vehicle::create(['vehicle_code' => 'VH-OP1', 'operator_id' => $operator->id, 'plate_number' => 'TRI-OP1', 'vehicle_type' => 'Tricycle', 'status' => 'active']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('OPERATOR / DRIVER WORKSPACE');
        $this->actingAs($user)->get(route('operator.vehicle'))->assertOk()->assertSee($vehicle->plate_number);
        $this->actingAs($user)->get(route('operator.applications.index'))->assertOk();
        $this->actingAs($user)->get(route('operator.profile'))->assertOk();
    }

    public function test_operator_cannot_access_another_operators_application_or_vehicle(): void
    {
        [$firstUser, $firstOperator] = $this->operatorUser('operator_first');
        [, $secondOperator] = $this->operatorUser('operator_second');
        $vehicle = Vehicle::create(['vehicle_code' => 'VH-OP2', 'operator_id' => $secondOperator->id, 'plate_number' => 'TRI-OP2', 'vehicle_type' => 'Tricycle', 'status' => 'active']);
        $application = Application::create(['application_number' => 'APP-OP2', 'operator_id' => $secondOperator->id, 'vehicle_id' => $vehicle->id, 'application_type' => 'New Permit', 'date_submitted' => today(), 'status' => 'Pending']);

        $this->actingAs($firstUser)->get(route('operator.applications.show', $application))->assertNotFound();
        $this->actingAs($firstUser)->get(route('operator.permits.index'))->assertOk();
        $this->actingAs($firstUser)->get(route('operators.index'))->assertForbidden();
        $this->actingAs($firstUser)->get(route('users.index'))->assertForbidden();
    }

    public function test_operator_can_submit_only_an_owned_application_and_duplicates_are_rejected(): void
    {
        [$user, $operator] = $this->operatorUser('operator_submit');
        $vehicle = Vehicle::create(['vehicle_code' => 'VH-SUBMIT', 'operator_id' => $operator->id, 'plate_number' => 'TRI-SUBMIT', 'vehicle_type' => 'Tricycle', 'status' => 'active']);
        $data = ['vehicle_id' => $vehicle->id, 'application_type' => 'New Permit', 'date_submitted' => today()->toDateString(), 'remarks' => 'Permit request'];

        $this->actingAs($user)->post(route('operator.applications.store'), $data)->assertRedirect();
        $this->assertDatabaseHas('applications', ['operator_id' => $operator->id, 'vehicle_id' => $vehicle->id, 'application_type' => 'New Permit']);
        $this->actingAs($user)->from(route('operator.applications.create'))->post(route('operator.applications.store'), $data)->assertRedirect(route('operator.applications.create'))->assertSessionHasErrors('vehicle_id');
    }

    public function test_operator_cannot_submit_for_another_operators_vehicle(): void
    {
        [$user] = $this->operatorUser('operator_owner');
        [, $otherOperator] = $this->operatorUser('operator_other');
        $vehicle = Vehicle::create(['vehicle_code' => 'VH-OTHER', 'operator_id' => $otherOperator->id, 'plate_number' => 'TRI-OTHER', 'vehicle_type' => 'Tricycle', 'status' => 'active']);

        $this->actingAs($user)->post(route('operator.applications.store'), ['vehicle_id' => $vehicle->id, 'application_type' => 'New Permit', 'date_submitted' => today()->toDateString()])->assertForbidden();
    }

    public function test_operator_can_change_password_but_not_role(): void
    {
        [$user] = $this->operatorUser('operator_password');
        $this->actingAs($user)->put(route('operator.password.update'), ['current_password' => 'password', 'password' => 'NewSecure1', 'password_confirmation' => 'NewSecure1'])->assertRedirect();
        $this->assertTrue(Hash::check('NewSecure1', $user->fresh()->password));
        $this->actingAs($user)->put(route('profile.update'), ['name' => 'Attempted Admin', 'username' => $user->username, 'email' => $user->email, 'mobile_number' => $user->mobile_number, 'address' => $user->address, 'role' => 'admin'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'operator']);
    }
}