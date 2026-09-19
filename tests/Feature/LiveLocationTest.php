<?php

namespace Tests\Feature;

use App\Models\{Operator, RolePermission, User, Vehicle, VehicleLocation};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveLocationTest extends TestCase
{
    use RefreshDatabase;

    private function makeOperatorUser(string $username): array
    {
        $user = User::factory()->create(['username' => $username, 'role' => 'operator']);
        $operator = Operator::create([
            'user_id' => $user->id,
            'operator_code' => 'OP-'.$username,
            'first_name' => $user->name,
            'last_name' => 'Driver',
            'address' => 'Municipal Road',
            'contact_number' => $user->mobile_number,
            'email' => $user->email,
            'status' => 'active',
        ]);

        return [$user, $operator];
    }

    public function test_municipal_user_can_access_live_map(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get(route('live-map.index'))->assertOk();
    }

    public function test_operator_can_post_live_location_for_assigned_vehicle(): void
    {
        [$user, $operator] = $this->makeOperatorUser('gps_driver');
        $vehicle = Vehicle::create([
            'vehicle_code' => 'VH-GPS-1',
            'operator_id' => $operator->id,
            'plate_number' => 'TRC-GPS-1',
            'vehicle_type' => 'Tricycle',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('operator.live-location.store'), [
            'vehicle_id' => $vehicle->id,
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'speed' => 12.5,
            'heading' => 90,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('vehicle_locations', ['vehicle_id' => $vehicle->id, 'operator_id' => $operator->id]);
    }

    public function test_vehicle_owner_can_view_only_own_vehicle_location(): void
    {
        $ownerUser = User::factory()->create(['role' => 'vehicle_owner']);
        $ownerOperator = Operator::create([
            'user_id' => $ownerUser->id,
            'operator_code' => 'VO-OWNER-1',
            'first_name' => $ownerUser->name,
            'last_name' => 'Owner',
            'address' => 'Owner Road',
            'contact_number' => $ownerUser->mobile_number,
            'email' => $ownerUser->email,
            'status' => 'active',
        ]);

        $ownedVehicle = Vehicle::create([
            'vehicle_code' => 'VH-OWN-1',
            'operator_id' => $ownerOperator->id,
            'plate_number' => 'TRC-OWN-1',
            'vehicle_type' => 'Tricycle',
            'status' => 'active',
        ]);

        $otherOwner = User::factory()->create(['role' => 'vehicle_owner']);
        $otherOperator = Operator::create([
            'user_id' => $otherOwner->id,
            'operator_code' => 'VO-OWNER-2',
            'first_name' => $otherOwner->name,
            'last_name' => 'Owner',
            'address' => 'Owner Road 2',
            'contact_number' => $otherOwner->mobile_number,
            'email' => $otherOwner->email,
            'status' => 'active',
        ]);

        $otherVehicle = Vehicle::create([
            'vehicle_code' => 'VH-OWN-2',
            'operator_id' => $otherOperator->id,
            'plate_number' => 'TRC-OWN-2',
            'vehicle_type' => 'Tricycle',
            'status' => 'active',
        ]);

        $this->actingAs($ownerUser)->get(route('vehicle-owner.vehicles.location', $ownedVehicle))->assertOk();
        $this->actingAs($ownerUser)->get(route('vehicle-owner.vehicles.location', $otherVehicle))->assertForbidden();
    }
}
