<?php

namespace App\Http\Controllers;

use App\Models\{AdministratorApplication, Application, AuditLog, Franchise, Operator, Permit, Report, User, Vehicle, Violation};
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->role === 'admin') {
            $stats = [
                'users' => User::count(),
                'active_users' => User::where('status', 'active')->count(),
                'reports' => Report::count(),
                'pending_reports' => Report::whereIn('status', ['Submitted', 'Pending'])->count(),
                'under_review_reports' => Report::where('status', 'Under Review')->count(),
                'resolved_reports' => Report::where('status', 'Resolved')->count(),
                'pending_administrator_applications' => AdministratorApplication::where('status', 'pending')->count(),
            ];
            $recentActivity = AuditLog::with('user')->latest()->limit(8)->get();
            $recentApplications = AdministratorApplication::with('applicant')
                ->where('status', 'pending')
                ->latest('submitted_at')
                ->limit(5)
                ->get();

            return view('administrator.dashboard', compact('stats', 'recentActivity', 'recentApplications'));
        }

        if (auth()->user()->role === 'viewer') return app(ReportUserDashboardController::class)->index();
        if (auth()->user()->role === 'operator') return app(OperatorPortalController::class)->dashboard();
        if (auth()->user()->role === 'vehicle_owner') return app(VehicleOwnerPortalController::class)->dashboard();
        $today = Carbon::today(); $soon = $today->copy()->addDays(30);
        $validStatus = ['Active', 'Expiring Soon'];
        $stats = [
            'operators' => Operator::count(), 'vehicles' => Vehicle::where('status', 'active')->count(),
            'pending' => Application::whereIn('status', ['Pending', 'Under Review'])->count(),
            'franchises' => Franchise::whereIn('status', $validStatus)->where('expiry_date', '>=', $today)->count(),
            'permits' => Permit::whereIn('status', $validStatus)->where('expiry_date', '>=', $today)->count(),
            'expiring' => Franchise::whereIn('status', $validStatus)->whereBetween('expiry_date', [$today, $soon])->count()
                + Permit::whereIn('status', $validStatus)->whereBetween('expiry_date', [$today, $soon])->count(),
            'violations' => Violation::count(), 'unpaid' => Violation::whereIn('payment_status', ['Unpaid', 'Partially Paid'])->sum('penalty_amount'),
        ];
        $applicationStatuses = Application::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $violationTypes = Violation::query()->selectRaw('violation_type, count(*) as total')->groupBy('violation_type')->orderByDesc('total')->limit(6)->pluck('total', 'violation_type');
        $alerts = Franchise::with('operator')->whereIn('status', $validStatus)->whereBetween('expiry_date', [$today, $soon])->orderBy('expiry_date')->limit(5)->get();
        return view('dashboard', compact('stats', 'applicationStatuses', 'violationTypes', 'alerts'));
    }
}
