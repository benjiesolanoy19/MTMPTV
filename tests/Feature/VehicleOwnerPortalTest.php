<?php

namespace Tests\Feature;

use App\Models\{Application, Operator, RolePermission, User, Vehicle};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleOwnerPortalTest extends TestCase
{
    use RefreshDatabase;

    private function vehicleOwnerUser(string $username): array
    {
        foreach (['view dashboard', 'vehicle owner portal', 'vehicle owner vehicles', 'vehicle owner applications', 'vehicle owner permits', 'vehicle owner franchises', 'vehicle owner renewals', 'vehicle owner violations', 'vehicle owner notifications'] as $permission) {
            RolePermission::firstOrCreate(['role' => 'vehicle_owner', 'permission' => $permission]);
        }

        $user = User::factory()->create(['username' => $username, 'role' => 'vehicle_owner']);
        $owner = Operator::create([
            'user_id' => $user->id,
            'operator_code' => 'VO-'.$username,
            'first_name' => $user->name,
            'last_name' => 'Owner',
            'address' => 'Municipal Road',
            'contact_number' => $user->mobile_number,
            'email' => $user->email,
            'status' => 'active',
        ]);

        return [$user, $owner];
    }

    public function test_vehicle_owner_dashboard_and_owned_pages_load(): void
    {
        [$user, $owner] = $this->vehicleOwnerUser('owner_one');
        $vehicle = Vehicle::create([
            'vehicle_code' => 'VH-OWN-1',
            'operator_id' => $owner->id,
            'plate_number' => 'TRI-OWN-1',
            'vehicle_type' => 'Tricycle',
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('VEHICLE OWNER WORKSPACE');
        $this->actingAs($user)->get(route('vehicle-owner.vehicles.index'))->assertOk()->assertSee($vehicle->plate_number);
        $this->actingAs($user)->get(route('vehicle-owner.profile'))->assertOk();
        $this->actingAs($user)->get(route('vehicle-owner.vehicles.show', $vehicle))->assertOk();
    }

    public function test_vehicle_owner_cannot_access_another_owners_vehicle_or_application(): void
    {
        [$firstUser, $firstOwner] = $this->vehicleOwnerUser('owner_first');
        [, $secondOwner] = $this->vehicleOwnerUser('owner_second');
        $vehicle = Vehicle::create([
            'vehicle_code' => 'VH-OWN-2',
            'operator_id' => $secondOwner->id,
            'plate_number' => 'TRI-OWN-2',
            'vehicle_type' => 'Tricycle',
            'status' => 'active',
        ]);
        $application = Application::create([
            'application_number' => 'APP-OWN-2',
            'operator_id' => $secondOwner->id,
            'vehicle_id' => $vehicle->id,
            'application_type' => 'New Permit',
            'date_submitted' => today(),
            'status' => 'Pending',
        ]);

        $this->actingAs($firstUser)->get(route('vehicle-owner.vehicles.show', $vehicle))->assertNotFound();
        $this->actingAs($firstUser)->get(route('vehicle-owner.applications.show', $application))->assertNotFound();
        $this->actingAs($firstUser)->get(route('operators.index'))->assertForbidden();
    }
}
