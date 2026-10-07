<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffOnboardingController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user && $user->role === 'staff' && $user->canAccessStaff()) {
            return redirect()->route('staff.dashboard');
        }

        abort_unless($user && $user->role === 'staff' && $user->staffApplicationIsApproved(), 403);

        $profile = $user->staffProfile()->firstOrNew();

        return view('staff.onboarding', compact('profile'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->role === 'staff' && $user->staffApplicationIsApproved(), 403);

        $data = $request->validate([
            'staff_id' => ['nullable', 'string', 'max:120'],
            'position' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'date_of_birth' => ['nullable', 'date'],
            'skills' => ['nullable', 'string', 'max:5000'],
            'experience' => ['nullable', 'string', 'max:5000'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'additional_information' => ['nullable', 'string', 'max:5000'],
        ]);

        $profile = DB::transaction(function () use ($user, $data, $request) {
            $record = $user->staffProfile()->firstOrNew();
            $record->fill([
                'user_id' => $user->id,
                'staff_id' => $data['staff_id'] ?? $record->staff_id,
                'position' => $data['position'],
                'department' => $data['department'],
                'contact_number' => $data['contact_number'],
                'address' => $data['address'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'skills' => $data['skills'] ?? null,
                'experience' => $data['experience'] ?? null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'additional_information' => $data['additional_information'] ?? null,
                'profile_completed' => true,
            ]);
            $record->save();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'completed',
                'module' => 'Staff Onboarding',
                'record_id' => $record->id,
                'description' => 'Staff onboarding profile completed',
                'ip_address' => $request->ip(),
            ]);

            $user->update(['role' => 'staff']);

            return $record;
        });

        return redirect()->route('staff.dashboard')->with('success', 'Your staff profile has been completed.');
    }
}
