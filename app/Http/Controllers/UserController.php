<?php

namespace App\Http\Controllers;

use App\Models\{AuditLog, User};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()->when($request->search, fn ($q, $s) => $q->where(fn ($x) => $x->where('name', 'like', "%$s%")->orWhere('username', 'like', "%$s%")->orWhere('email', 'like', "%$s%")))->when($request->role, fn ($q, $role) => $q->where('role', $role))->when($request->status, fn ($q, $status) => $q->where('status', $status))->latest()->paginate(12)->withQueryString();
        return view('users.index', compact('users'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', Rule::in(['admin', 'staff', 'viewer', 'operator', 'vehicle_owner'])], 'status' => ['required', Rule::in(['active', 'pending', 'suspended', 'deactivated'])]]);
        if ($user->is(auth()->user()) && ($data['role'] !== 'admin' || $data['status'] !== 'active')) return back()->withErrors(['user' => 'You cannot deactivate or remove your own administrator access.']);
        $user->update($data);
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'updated', 'module' => 'User Management', 'record_id' => $user->id, 'description' => "Account {$user->username} updated", 'ip_address' => $request->ip()]);
        return back()->with('success', 'Account updated.');
    }
}