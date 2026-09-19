<?php

namespace App\Http\Controllers;

use App\Models\{Application, Franchise, Operator, Permit, Vehicle, Violation};
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->role === 'viewer') return app(ReportUserDashboardController::class)->index();
        if (auth()->user()->role === 'operator') return app(OperatorPortalController::class)->dashboard();
        if (auth()->user()->role === 'vehicle_owner') return app(VehicleOwnerPortalController::class)->dashboard();
        $today = Carbon::today(); $soon = $today->copy()->addDays(30);
        $stats = [
            'operators' => Operator::count(), 'vehicles' => Vehicle::count(),
            'pending' => Application::whereIn('status', ['Pending', 'Under Review'])->count(),
            'franchises' => Franchise::where('expiry_date', '>=', $today)->count(),
            'permits' => Permit::where('expiry_date', '>=', $today)->count(),
            'expiring' => Franchise::whereBetween('expiry_date', [$today, $soon])->count() + Permit::whereBetween('expiry_date', [$today, $soon])->count(),
            'violations' => Violation::count(), 'unpaid' => Violation::whereIn('payment_status', ['Unpaid', 'Partially Paid'])->sum('penalty_amount'),
        ];
        $applicationStatuses = Application::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $violationTypes = Violation::query()->selectRaw('violation_type, count(*) as total')->groupBy('violation_type')->orderByDesc('total')->limit(6)->pluck('total', 'violation_type');
        $alerts = Franchise::with('operator')->whereBetween('expiry_date', [$today, $soon])->orderBy('expiry_date')->limit(5)->get();
        return view('dashboard', compact('stats', 'applicationStatuses', 'violationTypes', 'alerts'));
    }
}
