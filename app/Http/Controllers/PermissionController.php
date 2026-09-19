<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function index()
    {
        $roles = ['staff', 'viewer', 'operator', 'vehicle_owner'];
        $permissions = ['view dashboard', 'view operators', 'manage operators', 'view vehicles', 'manage vehicles', 'view applications', 'create applications', 'manage applications', 'view franchises', 'manage franchises', 'view permits', 'manage permits', 'view renewals', 'manage renewals', 'view violations', 'manage violations', 'view reports', 'view my reports', 'view notifications', 'manage users', 'operator portal', 'operator applications', 'operator permits', 'operator franchises', 'operator renewals', 'operator violations', 'operator notifications', 'vehicle owner portal', 'vehicle owner vehicles', 'vehicle owner applications', 'vehicle owner permits', 'vehicle owner franchises', 'vehicle owner renewals', 'vehicle owner violations', 'vehicle owner notifications'];
        $assigned = RolePermission::query()->get()->groupBy('role')->map(fn ($items) => $items->pluck('permission')->all());
        return view('permissions.index', compact('roles', 'permissions', 'assigned'));
    }

    public function update(Request $request, string $role)
    {
        abort_unless(in_array($role, ['staff', 'viewer', 'operator', 'vehicle_owner'], true), 404);
        $permissions = ['view dashboard', 'view operators', 'manage operators', 'view vehicles', 'manage vehicles', 'view applications', 'create applications', 'manage applications', 'view franchises', 'manage franchises', 'view permits', 'manage permits', 'view renewals', 'manage renewals', 'view violations', 'manage violations', 'view reports', 'view my reports', 'view notifications', 'manage users', 'operator portal', 'operator applications', 'operator permits', 'operator franchises', 'operator renewals', 'operator violations', 'operator notifications', 'vehicle owner portal', 'vehicle owner vehicles', 'vehicle owner applications', 'vehicle owner permits', 'vehicle owner franchises', 'vehicle owner renewals', 'vehicle owner violations', 'vehicle owner notifications'];
        $selected = $request->validate(['permissions' => ['nullable', 'array'], 'permissions.*' => ['string', 'in:'.implode(',', $permissions)]])['permissions'] ?? [];
        DB::transaction(function () use ($role, $selected) {
            RolePermission::where('role', $role)->delete();
            RolePermission::insert(array_map(fn ($permission) => ['role' => $role, 'permission' => $permission, 'created_at' => now(), 'updated_at' => now()], $selected));
        });
        return back()->with('success', ucfirst(str_replace('_', ' ', $role)).' permissions updated.');
    }
}