<?php

namespace App\Http\Controllers;

use App\Models\{Franchise, Permit, Renewal, Violation};
use Illuminate\Http\Request;

class PublicTransportController extends Controller
{
    public function franchises(Request $request) { $franchises = Franchise::with('operator')->when($request->search, fn ($q, $s) => $q->where('franchise_number', 'like', "%$s%")->orWhereHas('operator', fn ($o) => $o->where('last_name', 'like', "%$s%")))->when($request->status, fn ($q, $s) => $q->where('status', $s))->latest()->paginate(12)->withQueryString(); return view('report-user.transport.franchises', compact('franchises')); }
    public function franchise(Franchise $franchise) { $franchise->load(['operator', 'vehicle']); return view('report-user.transport.franchise', compact('franchise')); }
    public function permits(Request $request) { $permits = Permit::with('operator')->when($request->search, fn ($q, $s) => $q->where('permit_number', 'like', "%$s%")->orWhereHas('operator', fn ($o) => $o->where('last_name', 'like', "%$s%")))->when($request->status, fn ($q, $s) => $q->where('status', $s))->latest()->paginate(12)->withQueryString(); return view('report-user.transport.permits', compact('permits')); }
    public function permit(Permit $permit) { $permit->load(['operator', 'vehicle']); return view('report-user.transport.permit', compact('permit')); }
    public function renewals(Request $request) { $renewals = Renewal::with(['operator', 'vehicle'])->when($request->search, fn ($q, $s) => $q->where('renewal_number', 'like', "%$s%")->orWhereHas('operator', fn ($o) => $o->where('last_name', 'like', "%$s%")))->when($request->status, fn ($q, $s) => $q->where('status', $s))->latest('date_applied')->paginate(12)->withQueryString(); return view('report-user.transport.renewals', compact('renewals')); }
    public function renewal(Renewal $renewal) { $renewal->load(['operator', 'vehicle']); return view('report-user.transport.renewal', compact('renewal')); }
    public function violations(Request $request) { $violations = Violation::with(['operator', 'vehicle'])->when($request->search, fn ($q, $s) => $q->where('violation_number', 'like', "%$s%")->orWhere('violation_type', 'like', "%$s%")->orWhere('location', 'like', "%$s%"))->when($request->status, fn ($q, $s) => $q->where('status', $s))->latest('violation_date')->paginate(12)->withQueryString(); return view('report-user.transport.violations', compact('violations')); }
    public function violation(Violation $violation) { $violation->load(['operator', 'vehicle']); return view('report-user.transport.violation', compact('violation')); }
}