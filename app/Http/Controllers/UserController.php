<?php

namespace App\Http\Controllers;

use App\Models\{AuditLog, User};
use App\Support\RolePermissionMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'role' => ['required', Rule::in(array_merge(['admin'], RolePermissionMatrix::roles()))],
            'status' => ['required', Rule::in(['active', 'pending', 'suspended', 'deactivated'])],
        ]);
        if ($user->is($request->user()) && ($data['role'] !== $user->role || $data['status'] !== $user->status)) {
            return back()->withErrors(['user' => 'You cannot change your own role or account status.']);
        }
        DB::transaction(function () use ($user, $data, $request) {
            $account = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($account->role === 'admin' && $account->status === 'active'
                && ($data['role'] !== 'admin' || $data['status'] !== 'active')) {
                $activeAdministrators = User::query()
                    ->where('role', 'admin')
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->get();

                if ($activeAdministrators->count() <= 1) {
                    abort(422, 'The only active administrator account cannot be deactivated or demoted.');
                }
            }

            $account->update($data);
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'updated',
                'module' => 'User Management',
                'record_id' => $account->id,
                'description' => "Account {$account->username} updated",
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('success', 'Account updated.');
    }
}