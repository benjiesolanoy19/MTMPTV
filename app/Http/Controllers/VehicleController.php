<?php

namespace App\Http\Controllers;

use App\Models\{AuditLog, Operator, Vehicle};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function index(Request $request) { $vehicles = Vehicle::with('operator')->when($request->search, fn($q, $s) => $q->where(fn($x) => $x->where('vehicle_code', 'like', "%$s%")->orWhere('plate_number', 'like', "%$s%")->orWhereHas('operator', fn($o) => $o->where('last_name', 'like', "%$s%"))))->when($request->status, fn($q, $s) => $q->where('status', $s))->latest()->paginate(10)->withQueryString(); return auth()->user()->role === 'viewer' ? view('report-user.transport.vehicles', compact('vehicles')) : view('vehicles.index', compact('vehicles')); }
    public function create() { return view('vehicles.form', ['vehicle' => new Vehicle, 'operators' => Operator::where('status', 'active')->orderBy('last_name')->get()]); }
    public function store(Request $request) { $vehicle = Vehicle::create($request->validate($this->rules())); $this->log('created', $vehicle); return redirect()->route('vehicles.index')->with('success', 'Vehicle created.'); }
    public function show(Vehicle $vehicle) { $vehicle->load('operator'); return auth()->user()->role === 'viewer' ? view('report-user.transport.vehicle', compact('vehicle')) : view('vehicles.show', compact('vehicle')); }
    public function edit(Vehicle $vehicle) { return view('vehicles.form', ['vehicle' => $vehicle, 'operators' => Operator::orderBy('last_name')->get()]); }
    public function update(Request $request, Vehicle $vehicle) { $vehicle->update($request->validate($this->rules($vehicle))); $this->log('updated', $vehicle); return redirect()->route('vehicles.show', $vehicle)->with('success', 'Vehicle updated.'); }
    public function destroy(Vehicle $vehicle) { $this->log('deleted', $vehicle); $vehicle->delete(); return back()->with('success', 'Vehicle deleted.'); }
    private function rules(?Vehicle $vehicle = null): array { return ['vehicle_code' => ['required', Rule::unique('vehicles')->ignore($vehicle)], 'operator_id' => 'required|exists:operators,id', 'plate_number' => ['required', Rule::unique('vehicles')->ignore($vehicle)], 'engine_number' => 'nullable|string|max:100', 'chassis_number' => 'nullable|string|max:100', 'vehicle_type' => 'required|string|max:50', 'make' => 'nullable|string|max:50', 'model' => 'nullable|string|max:50', 'color' => 'nullable|string|max:30', 'year_model' => 'nullable|integer|min:1950|max:'.(date('Y') + 1), 'registration_number' => 'nullable|string|max:50', 'registration_expiry' => 'nullable|date', 'status' => 'required|in:active,inactive,impounded']; }
    private function log(string $action, Vehicle $vehicle): void { AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'module' => 'Vehicles', 'record_id' => $vehicle->id, 'description' => "Vehicle {$vehicle->vehicle_code} {$action}", 'ip_address' => request()->ip()]); }
}
