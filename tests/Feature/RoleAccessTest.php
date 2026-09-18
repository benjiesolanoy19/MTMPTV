<?php

namespace Tests\Feature;

use App\Models\{Operator, RolePermission, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function grant(string $role, array $permissions): void
    {
        foreach ($permissions as $permission) RolePermission::create(['role' => $role, 'permission' => $permission]);
    }

    private function operatorUser(string $username): User
    {
        $user = User::factory()->create(['username' => $username, 'role' => 'operator']);
        Operator::create(['user_id' => $user->id, 'operator_code' => 'OP-'.$username, 'first_name' => $user->name, 'last_name' => 'Driver', 'address' => 'Municipal Road', 'contact_number' => $user->mobile_number, 'email' => $user->email, 'status' => 'active']);
        return $user;
    }

    public function test_operator_sees_only_allowed_sidebar_items_and_cannot_open_operator_records(): void
    {
        $this->grant('operator', ['view dashboard', 'operator portal', 'operator applications']);
        $user = $this->operatorUser('legacy_operator');

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Applications')->assertDontSee('Operators')->assertDontSee('User management');
        $this->actingAs($user)->get(route('operators.index'))->assertForbidden();
        $this->actingAs($user)->get(route('vehicles.index'))->assertForbidden();
        $this->actingAs($user)->get(route('profile.show'))->assertOk();
    }

    public function test_vehicle_owner_sees_vehicle_access_but_not_operator_management(): void
    {
        $this->grant('vehicle_owner', ['view dashboard', 'view vehicles', 'view applications', 'create applications', 'view permits', 'view renewals']);
        $user = User::factory()->create(['role' => 'vehicle_owner']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Vehicles')->assertSee('Applications')->assertDontSee('Operators');
        $this->actingAs($user)->get(route('vehicles.index'))->assertOk();
        $this->actingAs($user)->get(route('operators.index'))->assertForbidden();
    }

    public function test_staff_can_manage_operations_but_cannot_manage_users(): void
    {
        $this->grant('staff', ['view dashboard', 'view operators', 'manage operators', 'view vehicles', 'manage vehicles', 'view applications', 'create applications', 'manage applications']);
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($user)->get(route('operators.index'))->assertOk();
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    }

    public function test_administrator_can_access_all_existing_modules(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Operators')->assertSee('Vehicles')->assertSee('Applications')->assertSee('User management');
        $this->actingAs($user)->get(route('operators.index'))->assertOk();
        $this->actingAs($user)->get(route('vehicles.index'))->assertOk();
        $this->actingAs($user)->get(route('users.index'))->assertOk();
    }

    public function test_permissions_are_evaluated_from_database_on_each_request(): void
    {
        $this->grant('operator', ['view dashboard', 'operator portal']);
        $user = $this->operatorUser('dynamic_operator');
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('Applications');

        RolePermission::create(['role' => 'operator', 'permission' => 'operator applications']);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Applications');
    }
}