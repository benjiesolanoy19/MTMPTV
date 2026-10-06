<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Support\RolePermissionMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $roles = RolePermissionMatrix::roles();
        $assigned = RolePermission::query()->get()->groupBy('role')->map(fn ($items) => $items->pluck('permission')->all());
        $permissions = collect($roles)->mapWithKeys(fn ($role) => [$role => RolePermissionMatrix::permissionsFor($role)]);
        return view('permissions.index', compact('roles', 'permissions', 'assigned'));
    }

    public function update(Request $request, string $role)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless(in_array($role, RolePermissionMatrix::roles(), true), 404);
        $permissions = RolePermissionMatrix::permissionsFor($role);
        $selected = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:'.implode(',', $permissions)],
        ])['permissions'] ?? [];
        DB::transaction(function () use ($role, $selected) {
            RolePermission::where('role', $role)->delete();
            if ($selected !== []) {
                RolePermission::insert(array_map(fn ($permission) => ['role' => $role, 'permission' => $permission, 'created_at' => now(), 'updated_at' => now()], $selected));
            }
        });
        return back()->with('success', ucfirst(str_replace('_', ' ', $role)).' permissions updated.');
    }
}