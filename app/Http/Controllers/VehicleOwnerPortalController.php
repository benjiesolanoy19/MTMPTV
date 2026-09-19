<?php

namespace App\Http\Controllers;

use App\Models\{Application, AuditLog, Franchise, Operator, Permit, Renewal, Vehicle, VehicleLocation, Violation};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class VehicleOwnerPortalController extends Controller
{
    private function owner(): Operator
    {
        $user = auth()->user();
        $owner = $user->operatorProfile;

        if (!$owner && $user->role === 'vehicle_owner') {
            $owner = Operator::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'operator_code' => 'VO-'.now()->format('Ymd').'-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                    'first_name' => $user->name,
                    'last_name' => 'Owner',
                    'address' => $user->address,
                    'contact_number' => $user->mobile_number,
                    'email' => $user->email,
                    'status' => 'active',
                ]
            );
        }

        abort_unless($owner && $user->role === 'vehicle_owner', 404);
        return $owner;
    }

    public function dashboard()
    {
        $owner = $this->owner();
        $stats = [
            'vehicles' => $owner->vehicles()->count(),
            'active_permits' => $owner->permits()->where('expiry_date', '>=', today())->count(),
            'pending_applications' => $owner->applications()->whereIn('status', ['Pending', 'Under Review'])->count(),
            'upcoming_renewals' => $owner->renewals()->whereIn('status', ['Pending', 'Approved'])->whereBetween('new_expiry_date', [today(), today()->addDays(60)])->count(),
            'active_violations' => $owner->violations()->whereIn('status', ['Open'])->count(),
        ];

        $applications = $owner->applications()->with('vehicle')->latest()->limit(5)->get();

        return view('vehicle-owner.dashboard', compact('owner', 'stats', 'applications'));
    }

    public function profile()
    {
        return view('vehicle-owner.profile', ['owner' => $this->owner(), 'user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'mobile_number' => ['required', 'regex:/^(?:\+63|0)9\d{9}$/'],
            'address' => ['required', 'string', 'max:500'],
        ]);

        $user->update($data);
        $owner = $this->owner();
        $owner->update([
            'first_name' => $data['name'],
            'email' => $data['email'],
            'contact_number' => $data['mobile_number'],
            'address' => $data['address'],
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'updated',
            'module' => 'Vehicle Owner Profile',
            'record_id' => $owner->id,
            'description' => 'Vehicle owner updated personal information',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Vehicle owner profile updated.');
    }

    public function vehicles()
    {
        $owner = $this->owner();
        $vehicles = $owner->vehicles()->latest()->paginate(10);

        return view('vehicle-owner.vehicles.index', compact('owner', 'vehicles'));
    }

    public function createVehicle()
    {
        return view('vehicle-owner.vehicles.create', ['owner' => $this->owner()]);
    }

    public function storeVehicle(Request $request)
    {
        $owner = $this->owner();
        $data = $request->validate([
            'plate_number' => ['required', 'string', 'max:30', 'unique:vehicles,plate_number'],
            'vehicle_type' => ['required', 'string', 'max:60'],
            'make' => ['nullable', 'string', 'max:60'],
            'model' => ['nullable', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:40'],
            'year_model' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'engine_number' => ['nullable', 'string', 'max:80', 'unique:vehicles,engine_number'],
            'chassis_number' => ['nullable', 'string', 'max:80', 'unique:vehicles,chassis_number'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'registration_expiry' => ['nullable', 'date'],
        ]);

        $data['operator_id'] = $owner->id;
        $data['vehicle_code'] = 'VH-'.now()->format('Ymd').'-'.str_pad((string) ((Vehicle::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
        $data['status'] = 'inactive';

        $vehicle = Vehicle::create($data);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'created',
            'module' => 'Vehicle Owner Vehicles',
            'record_id' => $vehicle->id,
            'description' => 'Vehicle owner registered a vehicle',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('vehicle-owner.vehicles.show', $vehicle)->with('success', 'Vehicle registered successfully.');
    }

    public function showVehicle(Vehicle $vehicle)
    {
        abort_unless($vehicle->operator_id === $this->owner()->id, 404);
        $vehicle->load(['applications', 'franchises', 'permits', 'violations', 'operator']);
        return view('vehicle-owner.vehicles.show', compact('vehicle'));
    }

    public function vehicleLocation(Vehicle $vehicle)
    {
        $owner = $this->owner();
        abort_unless($vehicle->operator_id === $owner->id, 403);

        $vehicle->load('operator');
        $latestLocation = $vehicle->latestLocation()->first();
        $onlineTimeout = max(30, (int) config('services.location_online_timeout', 60));
        $status = $latestLocation && $latestLocation->recorded_at && $latestLocation->recorded_at->diffInSeconds(now()) <= $onlineTimeout ? 'online' : 'offline';

        return view('vehicle-owner.vehicles.location', [
            'vehicle' => $vehicle,
            'latestLocation' => $latestLocation,
            'status' => $status,
        ]);
    }

    public function vehicleLocationData(Vehicle $vehicle)
    {
        $owner = $this->owner();
        abort_unless($vehicle->operator_id === $owner->id, 403);

        $latestLocation = $vehicle->latestLocation()->first();
        $onlineTimeout = max(30, (int) config('services.location_online_timeout', 60));
        $status = $latestLocation && $latestLocation->recorded_at && $latestLocation->recorded_at->diffInSeconds(now()) <= $onlineTimeout ? 'online' : 'offline';

        return response()->json([
            'vehicle' => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'vehicle_code' => $vehicle->vehicle_code,
                'vehicle_type' => $vehicle->vehicle_type,
                'operator' => $vehicle->operator?->full_name,
            ],
            'location' => $latestLocation ? [
                'latitude' => $latestLocation->latitude,
                'longitude' => $latestLocation->longitude,
                'accuracy' => $latestLocation->accuracy,
                'recorded_at' => $latestLocation->recorded_at?->toIso8601String(),
                'status' => $status,
            ] : null,
        ]);
    }

    public function updateVehicleLocation(Request $request, Vehicle $vehicle)
    {
        $owner = $this->owner();
        abort_unless($vehicle->operator_id === $owner->id, 403);

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $location = VehicleLocation::create([
            'vehicle_id' => $vehicle->id,
            'operator_id' => $owner->id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'] ?? null,
            'speed' => 0,
            'heading' => 0,
            'status' => 'online',
            'is_sharing' => true,
            'recorded_at' => now(),
        ]);

        return response()->json(['success' => true, 'location' => $location]);
    }

    public function applications()
    {
        $owner = $this->owner();
        $applications = $owner->applications()->with('vehicle')->latest()->paginate(10);

        return view('vehicle-owner.applications.index', compact('owner', 'applications'));
    }

    public function createApplication()
    {
        $owner = $this->owner();
        $vehicles = $owner->vehicles()->get();

        return view('vehicle-owner.applications.create', compact('owner', 'vehicles'));
    }

    public function storeApplication(Request $request)
    {
        $owner = $this->owner();
        $data = $request->validate([
            'vehicle_id' => ['required', 'integer'],
            'application_type' => ['required', 'in:Registration,New Permit,Permit Renewal,New Franchise,Franchise Renewal'],
            'date_submitted' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless($owner->vehicles()->whereKey($data['vehicle_id'])->exists(), 403);

        $duplicate = $owner->applications()
            ->where('vehicle_id', $data['vehicle_id'])
            ->where('application_type', $data['application_type'])
            ->whereIn('status', ['Pending', 'Under Review'])
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['vehicle_id' => 'A matching application is already being processed.'])->withInput();
        }

        $data['operator_id'] = $owner->id;
        $data['application_number'] = 'APP-'.now()->format('YmdHis').'-'.$owner->id;
        $data['status'] = 'Pending';

        $application = Application::create($data);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'submitted',
            'module' => 'Vehicle Owner Applications',
            'record_id' => $application->id,
            'description' => 'Vehicle owner submitted an application',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('vehicle-owner.applications.show', $application)->with('success', 'Application submitted for review.');
    }

    public function application(Application $application)
    {
        abort_unless($application->operator_id === $this->owner()->id, 404);
        $application->load(['vehicle', 'reviewer']);
        return view('vehicle-owner.applications.show', compact('application'));
    }

    public function permits()
    {
        $owner = $this->owner();
        $permits = $owner->permits()->with('vehicle')->latest()->paginate(10);
        return view('vehicle-owner.permits.index', compact('owner', 'permits'));
    }

    public function permit(Permit $permit)
    {
        abort_unless($permit->operator_id === $this->owner()->id, 404);
        $permit->load(['vehicle', 'application']);
        return view('vehicle-owner.permits.show', compact('permit'));
    }

    public function franchises()
    {
        $owner = $this->owner();
        $franchises = $owner->franchises()->with('vehicle')->latest()->paginate(10);
        return view('vehicle-owner.franchises.index', compact('owner', 'franchises'));
    }

    public function franchise(Franchise $franchise)
    {
        abort_unless($franchise->operator_id === $this->owner()->id, 404);
        $franchise->load(['vehicle', 'application']);
        return view('vehicle-owner.franchises.show', compact('franchise'));
    }

    public function renewals()
    {
        $owner = $this->owner();
        $renewals = $owner->renewals()->with('vehicle')->latest()->paginate(10);
        return view('vehicle-owner.renewals.index', compact('owner', 'renewals'));
    }

    public function violations()
    {
        $owner = $this->owner();
        $violations = $owner->violations()->with('vehicle')->latest('violation_date')->paginate(10);
        return view('vehicle-owner.violations.index', compact('owner', 'violations'));
    }

    public function notifications()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(15);
        return view('vehicle-owner.notifications', compact('notifications'));
    }
}
