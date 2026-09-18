<?php

namespace App\Http\Controllers;

use App\Models\{Application, AuditLog, Operator, Vehicle};
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request) { $applications = Application::with(['operator', 'vehicle'])->when($request->search, fn($q, $s) => $q->where('application_number', 'like', "%$s%")->orWhereHas('operator', fn($o) => $o->where('last_name', 'like', "%$s%")))->when($request->status, fn($q, $s) => $q->where('status', $s))->latest()->paginate(10)->withQueryString(); return view('applications.index', compact('applications')); }
    public function create() { return view('applications.form', ['application' => new Application, 'operators' => Operator::where('status', 'active')->get(), 'vehicles' => Vehicle::where('status', 'active')->get()]); }
    public function store(Request $request) { $data = $request->validate(['operator_id' => 'required|exists:operators,id', 'vehicle_id' => 'required|exists:vehicles,id', 'application_type' => 'required|in:New Franchise,Franchise Renewal,New Permit,Permit Renewal,Registration', 'date_submitted' => 'required|date', 'remarks' => 'nullable|string']); $data['application_number'] = 'APP-'.date('YmdHis'); $application = Application::create($data); AuditLog::create(['user_id' => auth()->id(), 'action' => 'created', 'module' => 'Applications', 'record_id' => $application->id, 'description' => 'Application submitted', 'ip_address' => request()->ip()]); return redirect()->route('applications.index')->with('success', 'Application submitted.'); }
    public function show(Application $application) { $application->load(['operator', 'vehicle', 'reviewer']); return view('applications.show', compact('application')); }
    public function updateStatus(Request $request, Application $application) { $data = $request->validate(['status' => 'required|in:Under Review,Approved,Rejected,Cancelled', 'rejection_reason' => 'nullable|string']); $application->update(array_merge($data, ['reviewed_by' => auth()->id(), 'reviewed_at' => now()])); AuditLog::create(['user_id' => auth()->id(), 'action' => strtolower($data['status']), 'module' => 'Applications', 'record_id' => $application->id, 'description' => "Application marked {$data['status']}", 'ip_address' => request()->ip()]); return back()->with('success', 'Application status updated.'); }
}
