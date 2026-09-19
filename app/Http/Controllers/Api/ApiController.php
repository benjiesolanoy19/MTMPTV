<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Application, AuditLog, Franchise, Notification, Operator, Permit, Report, User, Vehicle, Violation};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash};
use Illuminate\Support\Carbon;

class ApiController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (!Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']]) || Auth::user()->status !== 'active') {
            return response()->json(['message' => 'Invalid credentials or account inactive.'], 401);
        }

        $user = Auth::user();
        $user->update(['last_login_at' => now()]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'api_login',
            'module' => 'Authentication',
            'description' => 'User logged in via API',
            'ip_address' => $request->ip()
        ]);

        // Note: In a real app we'd use Sanctum tokens. For simplicity, we return user data.
        // If stateful session is used, the client will need to handle cookies.
        return response()->json([
            'user' => $user->load('rolePermissions'),
            'message' => 'Login successful'
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'address' => 'required|string',
            'mobile_number' => 'required|string',
            'role' => 'required|in:viewer,operator,vehicle_owner',
        ]);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'address' => $request->address,
                'mobile_number' => $request->mobile_number,
                'role' => $request->role,
                'status' => 'active',
            ]);

            if ($user->role === 'operator') {
                Operator::create([
                    'user_id' => $user->id,
                    'operator_code' => 'OP-'.now()->format('Ymd').'-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                    'first_name' => $user->name,
                    'last_name' => 'Operator',
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
            $operator = $user->operatorProfile;
            if (!$operator) return response()->json(['message' => 'Operator profile not found.'], 404);

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

        // Admin/Staff stats
        return response()->json([
            'stats' => [
                'operators' => Operator::count(),
                'vehicles' => Vehicle::count(),
                'pending' => Application::whereIn('status', ['Pending', 'Under Review'])->count(),
                'franchises' => Franchise::where('expiry_date', '>=', $today)->count(),
                'permits' => Permit::where('expiry_date', '>=', $today)->count(),
                'expiring' => Franchise::whereBetween('expiry_date', [$today, $soon])->count() + Permit::whereBetween('expiry_date', [$today, $soon])->count(),
                'violations' => Violation::count(),
                'unpaid_violations' => Violation::whereIn('payment_status', ['Unpaid', 'Partially Paid'])->sum('penalty_amount'),
            ],
            'recent_alerts' => Franchise::with('operator')->whereBetween('expiry_date', [$today, $soon])->orderBy('expiry_date')->limit(5)->get()
        ]);
    }

    public function applications(Request $request)
    {
        $user = $request->user();
        $query = Application::with('vehicle');

        if ($user->role === 'operator') {
            $query->where('operator_id', $user->operatorProfile?->id);
        }

        return response()->json($query->latest()->get());
    }

    public function showApplication(Request $request, Application $application)
    {
        $user = $request->user();
        if ($user->role === 'operator' && $application->operator_id !== $user->operatorProfile?->id) {
            abort(403);
        }
        return response()->json($application->load('vehicle'));
    }

    public function storeApplication(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'operator') abort(403);
        $operator = $user->operatorProfile;

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
        $query = Vehicle::query();

        if ($user->role === 'operator') {
            $query->where('operator_id', $user->operatorProfile?->id);
        }

        return response()->json($query->latest()->get());
    }

    public function showVehicle(Request $request, Vehicle $vehicle)
    {
        $user = $request->user();
        if ($user->role === 'operator' && $vehicle->operator_id !== $user->operatorProfile?->id) {
            abort(403);
        }
        return response()->json($vehicle);
    }

    public function permits(Request $request)
    {
        $user = $request->user();
        $query = Permit::with('vehicle');

        if ($user->role === 'operator') {
            $query->where('operator_id', $user->operatorProfile?->id);
        }

        return response()->json($query->latest()->get());
    }

    public function showPermit(Request $request, Permit $permit)
    {
        $user = $request->user();
        if ($user->role === 'operator' && $permit->operator_id !== $user->operatorProfile?->id) {
            abort(403);
        }
        return response()->json($permit->load('vehicle'));
    }

    public function franchises(Request $request)
    {
        $user = $request->user();
        $query = Franchise::with('vehicle');

        if ($user->role === 'operator') {
            $query->where('operator_id', $user->operatorProfile?->id);
        }

        return response()->json($query->latest()->get());
    }

    public function showFranchise(Request $request, Franchise $franchise)
    {
        $user = $request->user();
        if ($user->role === 'operator' && $franchise->operator_id !== $user->operatorProfile?->id) {
            abort(403);
        }
        return response()->json($franchise->load('vehicle'));
    }

    public function renewals(Request $request)
    {
        $user = $request->user();
        $query = Renewal::with('vehicle');

        if ($user->role === 'operator') {
            $query->where('operator_id', $user->operatorProfile?->id);
        }

        return response()->json($query->latest()->get());
    }

    public function violations(Request $request)
    {
        $user = $request->user();
        $query = Violation::with('vehicle');

        if ($user->role === 'operator') {
            $query->where('operator_id', $user->operatorProfile?->id);
        }

        return response()->json($query->latest()->get());
    }

    public function showViolation(Request $request, Violation $violation)
    {
        $user = $request->user();
        if ($user->role === 'operator' && $violation->operator_id !== $user->operatorProfile?->id) {
            abort(403);
        }
        return response()->json($violation->load('vehicle'));
    }

    public function reports(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'viewer') abort(403);
        return response()->json(Report::where('submitted_by', $user->id)->latest()->get());
    }

    public function storeReport(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'viewer') abort(403);

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
        return response()->json($request->user()->notifications()->latest()->get());
    }
}
