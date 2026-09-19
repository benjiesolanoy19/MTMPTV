<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use Illuminate\Http\Request;

class OperatorLiveLocationController extends Controller
{
    private function operator(): Operator
    {
        $operator = auth()->user()->operatorProfile;
        abort_unless($operator && auth()->user()->role === 'operator', 403);
        return $operator;
    }

    public function index()
    {
        $operator = $this->operator();
        $vehicle = $operator->vehicles()->first();
        $latest = $vehicle ? VehicleLocation::where('vehicle_id', $vehicle->id)->latest('recorded_at')->first() : null;

        return view('operator.live-location', [
            'operator' => $operator,
            'vehicle' => $vehicle,
            'latestLocation' => $latest,
            'sharingEnabled' => (bool) ($latest?->is_sharing ?? false),
        ]);
    }

    public function store(Request $request)
    {
        $operator = $this->operator();
        $data = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'integer', 'between:0,360'],
        ]);

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        abort_unless($vehicle->operator_id === $operator->id, 403, 'This vehicle is not assigned to your operator profile.');

        $location = VehicleLocation::create([
            'vehicle_id' => $vehicle->id,
            'operator_id' => $operator->id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'] ?? null,
            'speed' => $data['speed'] ?? 0,
            'heading' => $data['heading'] ?? 0,
            'status' => 'online',
            'is_sharing' => true,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated.',
            'location' => [
                'id' => $location->id,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'status' => 'online',
                'updated_at' => $location->recorded_at?->toDateTimeString(),
            ],
        ]);
    }

    public function stop(Request $request)
    {
        $operator = $this->operator();
        $data = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
        ]);

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        abort_unless($vehicle->operator_id === $operator->id, 403, 'This vehicle is not assigned to your operator profile.');

        VehicleLocation::create([
            'vehicle_id' => $vehicle->id,
            'operator_id' => $operator->id,
            'latitude' => $vehicle->latestLocation()->value('latitude'),
            'longitude' => $vehicle->latestLocation()->value('longitude'),
            'speed' => 0,
            'heading' => 0,
            'status' => 'offline',
            'is_sharing' => false,
            'recorded_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Location sharing stopped.']);
    }
}
