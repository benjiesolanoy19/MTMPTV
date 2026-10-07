<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\StaffApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffApplicationController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->status === 'active' && $user->role !== 'admin', 403);

        $application = $user->staffApplications()->latest('submitted_at')->first();

        if ($application && $application->status === 'approved' && ! $user->canAccessStaff()) {
            return redirect()->route('staff.onboarding');
        }

        return view('staff-applications.apply', compact('application'));
    }

    public function status(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->status === 'active', 403);

        $application = $user->staffApplications()->latest('submitted_at')->first();

        if (! $application) {
            return redirect()->route('staff-application.create');
        }

        return view('staff-applications.status', compact('application'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->authorizeApplicant($user);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:-18 years'],
            'preferred_position' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'string', 'max:5000'],
            'experience' => ['nullable', 'string', 'max:5000'],
            'reason' => ['required', 'string', 'max:5000'],
            'additional_information' => ['nullable', 'string', 'max:5000'],
        ]);

        $application = DB::transaction(function () use ($user, $data, $request) {
            $applicant = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->authorizeApplicant($applicant);

            if ($applicant->staffApplications()->whereIn('status', ['pending', 'approved'])->exists()) {
                throw ValidationException::withMessages([
                    'application' => 'You already have an active staff application or an approved staff application.',
                ]);
            }

            $application = $applicant->staffApplications()->create([
                ...$data,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $applicant->id,
                'action' => 'submitted',
                'module' => 'Staff Applications',
                'record_id' => $application->id,
                'description' => 'Staff application submitted',
                'ip_address' => $request->ip(),
            ]);

            User::query()->where('role', 'admin')->where('status', 'active')->each(function (User $administrator) use ($application) {
                Notification::create([
                    'user_id' => $administrator->id,
                    'title' => 'New staff application',
                    'message' => $application->full_name.' has submitted a staff application for review.',
                    'type' => 'info',
                    'action_url' => route('staff-applications.show', $application, false),
                ]);
            });

            return $application;
        });

        return redirect()->route('staff-application.status')->with('success', 'Your Staff application has been submitted and is currently waiting for Administrator approval.');
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        abort_if($status && ! in_array($status, ['pending', 'approved', 'declined'], true), 404);

        $applications = StaffApplication::query()
            ->with(['applicant', 'reviewer'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->whereHas('applicant', fn ($applicant) => $applicant
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->latest('submitted_at')
            ->paginate(12)
            ->withQueryString();

        return view('staff-applications.index', compact('applications', 'status'));
    }

    public function show(StaffApplication $staffApplication)
    {
        $staffApplication->load(['applicant', 'reviewer']);

        return view('staff-applications.show', ['application' => $staffApplication]);
    }

    public function approve(Request $request, StaffApplication $staffApplication)
    {
        $reviewer = $request->user();
        abort_if($staffApplication->user_id === $reviewer->id, 403);
        $data = $request->validate(['admin_remarks' => ['nullable', 'string', 'max:5000']]);

        DB::transaction(function () use ($staffApplication, $reviewer, $request, $data) {
            $application = StaffApplication::query()->lockForUpdate()->findOrFail($staffApplication->id);
            $this->ensurePending($application);
            abort_if($application->user_id === $reviewer->id, 403);

            $applicant = User::query()->lockForUpdate()->findOrFail($application->user_id);
            if ($applicant->status !== 'active' || $applicant->role === 'admin') {
                throw ValidationException::withMessages([
                    'application' => 'This account is no longer eligible for staff approval.',
                ]);
            }

            $applicant->update(['role' => 'staff']);
            $application->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'admin_remarks' => $data['admin_remarks'] ?? null,
            ]);

            AuditLog::create([
                'user_id' => $reviewer->id,
                'action' => 'approved',
                'module' => 'Staff Applications',
                'record_id' => $application->id,
                'description' => "Staff application approved; user {$applicant->username} promoted to Staff",
                'ip_address' => $request->ip(),
            ]);

            Notification::create([
                'user_id' => $applicant->id,
                'title' => 'Your Staff application has been approved. Please complete your Staff onboarding.',
                'message' => 'Your Staff application has been approved. Please complete your Staff onboarding to continue.',
                'type' => 'success',
                'action_url' => route('staff.onboarding', [], false),
            ]);
        });

        return redirect()->route('staff-applications.show', $staffApplication)
            ->with('success', 'Staff application approved and the applicant was promoted to Staff.');
    }

    public function reject(Request $request, StaffApplication $staffApplication)
    {
        $reviewer = $request->user();
        abort_if($staffApplication->user_id === $reviewer->id, 403);
        $data = $request->validate(['admin_remarks' => ['required', 'string', 'max:5000']]);

        DB::transaction(function () use ($staffApplication, $reviewer, $request, $data) {
            $application = StaffApplication::query()->lockForUpdate()->findOrFail($staffApplication->id);
            $this->ensurePending($application);
            abort_if($application->user_id === $reviewer->id, 403);

            $application->update([
                'status' => 'declined',
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'admin_remarks' => $data['admin_remarks'],
            ]);

            AuditLog::create([
                'user_id' => $reviewer->id,
                'action' => 'declined',
                'module' => 'Staff Applications',
                'record_id' => $application->id,
                'description' => 'Staff application declined',
                'ip_address' => $request->ip(),
            ]);

            Notification::create([
                'user_id' => $application->user_id,
                'title' => 'Staff application declined',
                'message' => 'Your Staff application has been declined. Remarks: '.$data['admin_remarks'],
                'type' => 'warning',
                'action_url' => route('staff-application.status', [], false),
            ]);
        });

        return redirect()->route('staff-applications.show', $staffApplication)
            ->with('success', 'Application declined.');
    }

    private function authorizeApplicant(User $user): void
    {
        abort_unless($user && $user->status === 'active' && $user->role !== 'admin', 403);
    }

    private function ensurePending(StaffApplication $application): void
    {
        if ($application->status !== 'pending') {
            throw ValidationException::withMessages([
                'application' => 'This staff application has already been reviewed.',
            ]);
        }
    }
}
