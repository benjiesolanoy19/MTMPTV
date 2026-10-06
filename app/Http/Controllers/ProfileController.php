<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        abort_unless(auth()->user()->status === 'active', 403);
        return view('profile.show');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user->status === 'active', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z][A-Za-z0-9._-]*$/', Rule::unique('users')->ignore($user)],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->ignore($user)],
            'mobile_number' => ['required', 'regex:/^(?:\+63|0)9\d{9}$/'],
            'address' => ['required', 'string', 'max:500'],
        ]);
        $user->update($data);
        AuditLog::create(['user_id' => $user->id, 'action' => 'updated', 'module' => 'Profile', 'record_id' => $user->id, 'description' => 'User updated their profile', 'ip_address' => $request->ip()]);
        return back()->with('success', 'Profile updated successfully.');
    }
}