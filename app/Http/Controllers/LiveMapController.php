<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LiveMapController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user() && in_array(auth()->user()->role, ['admin', 'staff'], true), 403);

        $vehicles = Vehicle::with(['operator.user', 'latestLocation'])->get();

        $onlineTimeout = max(30, (int) config('services.location_online_timeout', 60));
        $vehiclesData = $vehicles->map(function (Vehicle $vehicle) use ($onlineTimeout) {
            $latest = $vehicle->latestLocation;
            $isOnline = $latest && $latest->recorded_at && $latest->recorded_at->diffInSeconds(now()) <= $onlineTimeout;

            return [
                'id' => $vehicle->id,
                'vehicle_code' => $vehicle->vehicle_code,
                'plate_number' => $vehicle->plate_number,
                'vehicle_type' => $vehicle->vehicle_type,
                'operator_name' => $vehicle->operator?->full_name ?? 'Unassigned',
                'latitude' => $latest?->latitude,
                'longitude' => $latest?->longitude,
                'status' => $isOnline ? 'online' : 'offline',
                'last_updated' => $latest?->recorded_at ? $latest->recorded_at->diffForHumans() : 'never',
                'recorded_at' => $latest?->recorded_at?->toDateTimeString(),
                'is_sharing' => (bool) ($latest?->is_sharing ?? false),
            ];
        })->values();

        return view('live-map.index', [
            'vehicles' => $vehiclesData,
            'onlineCount' => $vehiclesData->where('status', 'online')->count(),
            'offlineCount' => $vehiclesData->where('status', 'offline')->count(),
            'apiKey' => config('services.google_maps.api_key'),
        ]);
    }

    public function data()
    {
        abort_unless(auth()->user() && in_array(auth()->user()->role, ['admin', 'staff'], true), 403);

        $onlineTimeout = max(30, (int) config('services.location_online_timeout', 60));
        $vehicles = Vehicle::with(['operator.user', 'latestLocation'])->get()->map(function (Vehicle $vehicle) use ($onlineTimeout) {
            $latest = $vehicle->latestLocation;
            $status = $latest && $latest->recorded_at && $latest->recorded_at->diffInSeconds(now()) <= $onlineTimeout ? 'online' : 'offline';

            return [
                'id' => $vehicle->id,
                'vehicle_code' => $vehicle->vehicle_code,
                'plate_number' => $vehicle->plate_number,
                'vehicle_type' => $vehicle->vehicle_type,
                'operator_name' => $vehicle->operator?->full_name ?? 'Unassigned',
                'latitude' => $latest?->latitude,
                'longitude' => $latest?->longitude,
                'status' => $status,
                'last_updated' => $latest?->recorded_at ? $latest->recorded_at->diffForHumans() : 'never',
                'recorded_at' => $latest?->recorded_at?->toDateTimeString(),
                'is_sharing' => (bool) ($latest?->is_sharing ?? false),
            ];
        })->values();

        return response()->json(['vehicles' => $vehicles]);
    }
}
