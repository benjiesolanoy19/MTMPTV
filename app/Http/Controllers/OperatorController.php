<?php

namespace App\Http\Controllers;

use App\Models\{AuditLog, Operator};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperatorController extends Controller
{
    public function index(Request $request) { $operators = Operator::query()->when($request->search, fn($q, $s) => $q->where(fn($x) => $x->where('operator_code', 'like', "%$s%")->orWhere('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%")->orWhere('contact_number', 'like', "%$s%")))->when($request->status, fn($q, $s) => $q->where('status', $s))->latest()->paginate(10)->withQueryString(); return auth()->user()->role === 'viewer' ? view('report-user.transport.operators', compact('operators')) : view('operators.index', compact('operators')); }
    public function create() { return view('operators.form', ['operator' => new Operator]); }
    public function store(Request $request) { $data = $request->validate($this->rules()); $operator = Operator::create($data); $this->log('created', $operator); return redirect()->route('operators.show', $operator)->with('success', 'Operator created.'); }
    public function show(Operator $operator) { $operator->load(['vehicles', 'applications', 'franchises', 'permits', 'violations']); return auth()->user()->role === 'viewer' ? view('report-user.transport.operator', compact('operator')) : view('operators.show', compact('operator')); }
    public function edit(Operator $operator) { return view('operators.form', compact('operator')); }
    public function update(Request $request, Operator $operator) { $data = $request->validate($this->rules($operator)); $operator->update($data); $this->log('updated', $operator); return redirect()->route('operators.show', $operator)->with('success', 'Operator updated.'); }
    public function destroy(Operator $operator) { $this->log('deleted', $operator); $operator->delete(); return redirect()->route('operators.index')->with('success', 'Operator deleted.'); }
    private function rules(?Operator $operator = null): array { return ['operator_code' => ['required', 'string', 'max:30', Rule::unique('operators')->ignore($operator)], 'first_name' => 'required|string|max:100', 'middle_name' => 'nullable|string|max:100', 'last_name' => 'required|string|max:100', 'suffix' => 'nullable|string|max:20', 'address' => 'required|string', 'contact_number' => 'required|string|max:30', 'email' => 'nullable|email', 'valid_id_type' => 'nullable|string|max:50', 'valid_id_number' => 'nullable|string|max:50', 'status' => 'required|in:active,inactive', 'remarks' => 'nullable|string']; }
    private function log(string $action, Operator $operator): void { AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'module' => 'Operators', 'record_id' => $operator->id, 'description' => "Operator {$operator->operator_code} {$action}", 'ip_address' => request()->ip()]); }
}
