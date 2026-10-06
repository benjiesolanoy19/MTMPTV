<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Application, AuditLog, Franchise, Notification, Operator, Permit, Report, User, Vehicle, Violation};
use App\Support\RolePermissionMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash};
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Password;

class ApiController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (!Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']])) {
            return response()->json(['message' => 'Invalid credentials or account inactive.'], 401);
        }

        $user = Auth::user();
        if ($user->status !== 'active') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->json(['message' => 'Invalid credentials or account inactive.'], 401);
        }

        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'api_login',
            'module' => 'Authentication',
            'description' => 'User logged in via API',
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'user' => $user->only(['id', 'name', 'username', 'email', 'role', 'status']),
            'permissions' => $user->rolePermissions()->pluck('permission'),
            'message' => 'Login successful'
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z][A-Za-z0-9._-]*$/', 'unique:users,username'],
            'email' => 'required|email:rfc|max:255|unique:users,email',
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()],
            'address' => 'required|string|max:500',
            'mobile_number' => ['required', 'regex:/^(?:\+63|0)9\d{9}$/'],
            'role' => ['required', 'in:'.implode(',', RolePermissionMatrix::PUBLIC_REGISTRATION_ROLES)],
        ]);

        $user = DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'address' => $data['address'],
                'mobile_number' => $data['mobile_number'],
                'role' => $data['role'],
                'status' => 'active',
            ]);

            if (in_array($user->role, ['operator', 'vehicle_owner'], true)) {
                Operator::create([
                    'user_id' => $user->id,
                    'operator_code' => ($user->role === 'vehicle_owner' ? 'VO-' : 'OP-').now()->format('Ymd').'-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                    'first_name' => $user->name,
                    'last_name' => '',
                    'address' => $user->address,
                    'contact_number' => $user->mobile_number,
                    'email' => $user->email,
                    'status' => 'active',
                ]);
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'api_registered',
                'module' => 'Authentication',
                'record_id' => $user->id,
                'description' => 'New account registered via API',
                'ip_address' => $request->ip()
            ]);

            return $user;
        });

        return response()->json([
            'user' => $user,
            'message' => 'Account created successfully.'
        ], 201);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('view dashboard'), 403);
        $today = Carbon::today();
        $soon = $today->copy()->addDays(30);

        if ($user->role === 'viewer') {
            return response()->json([
                'stats' => [
                    'my_reports' => Report::where('submitted_by', $user->id)->count(),
                    'resolved' => Report::where('submitted_by', $user->id)->where('status', 'Resolved')->count(),
                ],
                'recent_reports' => Report::where('submitted_by', $user->id)->latest()->limit(5)->get()
            ]);
        }

        if ($user->role === 'operator') {
            $operator = $this->operatorProfile($user);

            return response()->json([
                'stats' => [
                    'my_vehicles' => Vehicle::where('operator_id', $operator->id)->count(),
                    'pending_applications' => Application::where('operator_id', $operator->id)->whereIn('status', ['Pending', 'Under Review'])->count(),
                    'active_permits' => Permit::where('operator_id', $operator->id)->where('expiry_date', '>=', $today)->count(),
                    'my_violations' => Violation::where('operator_id', $operator->id)->count(),
                ],
                'recent_applications' => Application::where('operator_id', $operator->id)->latest()->limit(5)->get()
            ]);
        }

        if ($user->role === 'vehicle_owner') {
            $operator = $this->operatorProfile($user);
            return response()->json([
                'stats' => [
                    'vehicles' => $operator->vehicles()->count(),
                    'pending_applications' => $operator->applications()->whereIn('status', ['Pending', 'Under Review'])->count(),
                    'active_permits' => $operator->permits()->where('expiry_date', '>=', $today)->count(),
                    'upcoming_renewals' => $operator->renewals()->whereIn('status', ['Pending', 'Approved'])->whereBetween('new_expiry_date', [$today, $today->copy()->addDays(60)])->count(),
                    'active_violations' => $operator->violations()->where('status', 'Open')->count(),
                ],
                'recent_applications' => $operator->applications()->latest()->limit(5)->get(),
            ]);
        }

        abort_unless(in_array($user->role, ['admin', 'staff'], true), 403);
        $activeStatuses = ['Active', 'Expiring Soon'];
        return response()->json([
            'stats' => [
                'operators' => Operator::count(),
                'vehicles' => Vehicle::where('status', 'active')->count(),
                'pending' => Application::whereIn('status', ['Pending', 'Under Review'])->count(),
                'franchises' => Franchise::whereIn('status', $activeStatuses)->where('expiry_date', '>=', $today)->count(),
                'permits' => Permit::whereIn('status', $activeStatuses)->where('expiry_date', '>=', $today)->count(),
                'expiring' => Franchise::whereIn('status', $activeStatuses)->whereBetween('expiry_date', [$today, $soon])->count() + Permit::whereIn('status', $activeStatuses)->whereBetween('expiry_date', [$today, $soon])->count(),
                'violations' => Violation::count(),
                'unpaid_violations' => Violation::whereIn('payment_status', ['Unpaid', 'Partially Paid'])->sum('penalty_amount'),
            ],
            'recent_alerts' => Franchise::with('operator')->whereIn('status', $activeStatuses)->whereBetween('expiry_date', [$today, $soon])->orderBy('expiry_date')->limit(5)->get()
        ]);
    }

    public function applications(Request $request)
    {
        $user = $request->user();
        $this->authorizeRead($user, 'applications');
        $query = Application::with('vehicle');

        $this->scopeOperatorRecords($query, $user);

        return response()->json($query->latest()->get());
    }

    public function showApplication(Request $request, Application $application)
    {
        $user = $request->user();
        $this->authorizeRead($user, 'applications');
        $this->assertRecordScope($user, $application->operator_id);
        return response()->json($application->load('vehicle'));
    }

    public function storeApplication(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['operator', 'vehicle_owner'], true), 403);
        $permission = $user->role === 'operator' ? 'operator applications' : 'vehicle owner applications';
        abort_unless($user->hasPermission($permission), 403);
        $operator = $this->operatorProfile($user);

        $data = $request->validate([
            'vehicle_id' => 'required|integer',
            'application_type' => 'required|in:New Franchise,Franchise Renewal,New Permit,Permit Renewal,Registration',
            'remarks' => 'nullable|string|max:2000'
        ]);

        if (!$operator->vehicles()->whereKey($data['vehicle_id'])->exists()) abort(403);

        $duplicate = $operator->applications()
            ->where('vehicle_id', $data['vehicle_id'])
            ->where('application_type', $data['application_type'])
            ->whereIn('status', ['Pending', 'Under Review'])
            ->exists();

        if ($duplicate) {
            return response()->json(['message' => 'A matching application is already being processed.'], 422);
        }

        $data['operator_id'] = $operator->id;
        $data['application_number'] = 'APP-'.now()->format('YmdHis').'-'.$operator->id;
        $data['date_submitted'] = now();
        $data['status'] = 'Pending';

        $application = Application::create($data);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'api_submitted',
            'module' => 'Operator Applications',
            'record_id' => $application->id,
            'description' => 'Operator submitted an application via API',
            'ip_address' => $request->ip()
        ]);

        return response()->json($application, 201);
    }

    public function vehicles(Request $request)
    {
        $user = $request->user();
        $this->authorizeRead($user, 'vehicles');
        $query = Vehicle::query();
        $this->scopeOperatorRecords($query, $user);
        $vehicles = $query->latest()->get();
        if ($user->role === 'viewer') {
            $vehicles->each(fn (Vehicle $vehicle) => $vehicle->makeHidden(['engine_number', 'chassis_number', 'registration_number']));
        }
        return response()->json($vehicles);
    }

    public function showVehicle(Request $request, Vehicle $vehicle)
    {
        $user = $request->user();
        $this->authorizeRead($user, 'vehicles');
        $this->assertRecordScope($user, $vehicle->operator_id);
        if ($user->role === 'viewer') $vehicle->makeHidden(['engine_number', 'chassis_number', 'registration_number']);
        return response()->json($vehicle);
    }

    public function permits(Request $request)
    {
        $user = $request->user();
        $this->authorizeRead($user, 'permits');
        $query = Permit::with('vehicle');
        $this->scopeOperatorRecords($query, $user);

        $records = $query->latest()->get();
        if ($user->role === 'viewer') $records->each(fn (Permit $permit) => $permit->vehicle?->makeHidden(['engine_number', 'chassis_number', 'registration_number']));
        return response()->json($records);
    }

    public function showPermit(Request $request, Permit $permit)
    {
        $user = $request->user();
        $this->authorizeRead($user, 'permits');
        $this->assertRecordScope($user, $permit->operator_id);
        $permit->load('vehicle');
        if ($user->role === 'viewer') $permit->vehicle?->makeHidden(['engine_number', 'chassis_number', 'registration_number']);
        return response()->json($permit);
    }

    public function franchises(Request $request)
    {
        $user = $request->user();
        $this->authorizeRead($user, 'franchises');
        $query = Franchise::with('vehicle');
        $this->scopeOperatorRecords($query, $user);

        $records = $query->latest()->get();
        if ($user->role === 'viewer') $records->each(fn (Franchise $franchise) => $franchise->vehicle?->makeHidden(['engine_number', 'chassis_number', 'registration_number']));
        return response()->json($records);
    }

    public function showFranchise(Request $request, Franchise $franchise)
    {
        $user = $request->user();
        $this->authorizeRead($user, 'franchises');
        $this->assertRecordScope($user, $franchise->operator_id);
        $franchise->load('vehicle');
        if ($user->role === 'viewer') $franchise->vehicle?->makeHidden(['engine_number', 'chassis_number', 'registration_number']);
        return response()->json($franchise);
    }

    public function renewals(Request $request)
    {
        $user = $request->user();
        $this->authorizeRead($user, 'renewals');
        $query = Renewal::with('vehicle');
        $this->scopeOperatorRecords($query, $user);

        $records = $query->latest()->get();
        if ($user->role === 'viewer') $records->each(fn (Renewal $renewal) => $renewal->vehicle?->makeHidden(['engine_number', 'chassis_number', 'registration_number']));
        return response()->json($records);
    }

    public function violations(Request $request)
    {
        $user = $request->user();
        $this->authorizeRead($user, 'violations');
        $query = Violation::with('vehicle');
        $this->scopeOperatorRecords($query, $user);

        $records = $query->latest()->get();
        if ($user->role === 'viewer') $records->each(fn (Violation $violation) => $violation->vehicle?->makeHidden(['engine_number', 'chassis_number', 'registration_number']));
        return response()->json($records);
    }

    public function showViolation(Request $request, Violation $violation)
    {
        $user = $request->user();
        $this->authorizeRead($user, 'violations');
        $this->assertRecordScope($user, $violation->operator_id);
        $violation->load('vehicle');
        if ($user->role === 'viewer') $violation->vehicle?->makeHidden(['engine_number', 'chassis_number', 'registration_number']);
        return response()->json($violation);
    }

    public function reports(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'viewer') {
            abort_unless($user->hasPermission('view my reports'), 403);
            return response()->json(Report::where('submitted_by', $user->id)->latest()->get());
        }

        abort_unless(in_array($user->role, ['admin', 'staff'], true) && $user->hasPermission('view reports'), 403);
        return response()->json(Report::latest()->get());
    }

    public function storeReport(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === 'viewer' && $user->hasPermission('view my reports'), 403);

        $data = $request->validate([
            'report_type' => 'required|string',
            'location' => 'required|string',
            'description' => 'required|string',
            'vehicle_plate' => 'nullable|string',
        ]);

        $data['submitted_by'] = $user->id;
        $data['report_number'] = 'REP-'.now()->format('YmdHis').'-'.$user->id;
        $data['date_submitted'] = now();
        $data['status'] = 'Pending';

        $report = Report::create($data);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'api_submitted_report',
            'module' => 'Public Reports',
            'record_id' => $report->id,
            'description' => 'User submitted a report via API',
            'ip_address' => $request->ip()
        ]);

        return response()->json($report, 201);
    }

    public function notifications(Request $request)
    {
        $this->authorizeRead($request->user(), 'notifications');
        return response()->json($request->user()->notifications()->latest()->get());
    }

    private function authorizeRead(User $user, string $resource): void
    {
        $permission = match ($user->role) {
            'admin', 'staff' => 'view '.$resource,
            'viewer' => in_array($resource, ['operators', 'vehicles', 'franchises', 'permits', 'renewals', 'violations'], true)
                ? 'view '.$resource
                : ($resource === 'notifications' ? 'view notifications' : null),
            'operator' => match ($resource) {
                'vehicles' => 'operator portal',
                'applications' => 'operator applications',
                'permits' => 'operator permits',
                'franchises' => 'operator franchises',
                'renewals' => 'operator renewals',
                'violations' => 'operator violations',
                'notifications' => 'operator notifications',
                default => null,
            },
            'vehicle_owner' => match ($resource) {
                'vehicles' => 'vehicle owner vehicles',
                'applications' => 'vehicle owner applications',
                'permits' => 'vehicle owner permits',
                'franchises' => 'vehicle owner franchises',
                'renewals' => 'vehicle owner renewals',
                'violations' => 'vehicle owner violations',
                'notifications' => 'vehicle owner notifications',
                default => null,
            },
            default => null,
        };

        abort_unless($permission && $user->hasPermission($permission), 403);
    }

    private function operatorProfile(User $user): Operator
    {
        $operator = $user->operatorProfile;
        abort_unless($operator, 404, 'Operator profile not found.');
        return $operator;
    }

    private function scopeOperatorRecords($query, User $user): void
    {
        if (in_array($user->role, ['operator', 'vehicle_owner'], true)) {
            $query->where('operator_id', $this->operatorProfile($user)->id);
        }
    }

    private function assertRecordScope(User $user, int $operatorId): void
    {
        if (in_array($user->role, ['operator', 'vehicle_owner'], true)) {
            abort_unless($operatorId === $this->operatorProfile($user)->id, 404);
        }
    }
}
