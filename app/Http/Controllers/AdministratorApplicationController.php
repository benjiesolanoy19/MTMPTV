<?php

namespace App\Http\Controllers;

use App\Models\AdministratorApplication;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdministratorApplicationController extends Controller
{
    public function create(Request $request)
    {
        $this->authorizeApplicant($request->user());
        $application = $request->user()->administratorApplications()->latest('submitted_at')->first();

        return view('administrator-applications.apply', compact('application'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->authorizeApplicant($user);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:5000'],
            'experience' => ['nullable', 'string', 'max:5000'],
            'additional_information' => ['nullable', 'string', 'max:5000'],
        ]);

        $application = DB::transaction(function () use ($user, $data, $request) {
            $applicant = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->authorizeApplicant($applicant);

            if ($applicant->administratorApplications()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'application' => 'Your administrator application is currently under review.',
                ]);
            }

            $application = $applicant->administratorApplications()->create([
                ...$data,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $applicant->id,
                'action' => 'submitted',
                'module' => 'Administrator Applications',
                'record_id' => $application->id,
                'description' => 'Administrator application submitted',
                'ip_address' => $request->ip(),
            ]);

            User::query()->where('role', 'admin')->where('status', 'active')->each(function (User $administrator) use ($application) {
                Notification::create([
                    'user_id' => $administrator->id,
                    'title' => 'New administrator application',
                    'message' => 'A user has submitted an application for administrator access. Application #'.$application->id.'.',
                    'type' => 'info',
                    'action_url' => route('administrator-applications.show', $application, false),
                ]);
            });

            return $application;
        });

        return redirect()->route('administrator-application.create')
            ->with('success', 'Your administrator application has been submitted.');
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        abort_if($status && ! in_array($status, ['pending', 'approved', 'rejected'], true), 404);

        $applications = AdministratorApplication::query()
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

        return view('administrator-applications.index', compact('applications', 'status'));
    }

    public function show(AdministratorApplication $administratorApplication)
    {
        $administratorApplication->load(['applicant', 'reviewer']);

        return view('administrator-applications.show', ['application' => $administratorApplication]);
    }

    public function approve(Request $request, AdministratorApplication $administratorApplication)
    {
        $reviewer = $request->user();
        abort_if($administratorApplication->user_id === $reviewer->id, 403);
        $data = $request->validate(['admin_remarks' => ['nullable', 'string', 'max:5000']]);

        DB::transaction(function () use ($administratorApplication, $reviewer, $request, $data) {
            $application = AdministratorApplication::query()->lockForUpdate()->findOrFail($administratorApplication->id);
            $this->ensurePending($application);
            abort_if($application->user_id === $reviewer->id, 403);

            $applicant = User::query()->lockForUpdate()->findOrFail($application->user_id);
            if ($applicant->status !== 'active' || $applicant->role === 'admin') {
                throw ValidationException::withMessages([
                    'application' => 'This account is no longer eligible for administrator approval.',
                ]);
            }

            $applicant->update(['role' => 'admin']);
            $application->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'admin_remarks' => $data['admin_remarks'] ?? null,
            ]);

            AuditLog::create([
                'user_id' => $reviewer->id,
                'action' => 'approved',
                'module' => 'Administrator Applications',
                'record_id' => $application->id,
                'description' => "Administrator application approved; user {$applicant->username} promoted to Administrator",
                'ip_address' => $request->ip(),
            ]);

            AuditLog::create([
                'user_id' => $reviewer->id,
                'action' => 'promoted',
                'module' => 'User Management',
                'record_id' => $applicant->id,
                'description' => "User {$applicant->username} promoted to Administrator",
                'ip_address' => $request->ip(),
            ]);

            Notification::create([
                'user_id' => $applicant->id,
                'title' => 'Administrator application approved',
                'message' => 'Your application has been approved. Your account now has Administrator privileges.',
                'type' => 'success',
                'action_url' => route('dashboard', [], false),
            ]);
        });

        return redirect()->route('administrator-applications.show', $administratorApplication)
            ->with('success', 'Application approved and the applicant was promoted to Administrator.');
    }

    public function reject(Request $request, AdministratorApplication $administratorApplication)
    {
        $reviewer = $request->user();
        abort_if($administratorApplication->user_id === $reviewer->id, 403);
        $data = $request->validate(['admin_remarks' => ['required', 'string', 'max:5000']]);

        DB::transaction(function () use ($administratorApplication, $reviewer, $request, $data) {
            $application = AdministratorApplication::query()->lockForUpdate()->findOrFail($administratorApplication->id);
            $this->ensurePending($application);
            abort_if($application->user_id === $reviewer->id, 403);

            $application->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'admin_remarks' => $data['admin_remarks'],
            ]);

            AuditLog::create([
                'user_id' => $reviewer->id,
                'action' => 'rejected',
                'module' => 'Administrator Applications',
                'record_id' => $application->id,
                'description' => 'Administrator application rejected',
                'ip_address' => $request->ip(),
            ]);

            Notification::create([
                'user_id' => $application->user_id,
                'title' => 'Administrator application rejected',
                'message' => 'Your administrator application was not approved. Remarks: '.$data['admin_remarks'],
                'type' => 'warning',
                'action_url' => route('administrator-application.create', [], false),
            ]);
        });

        return redirect()->route('administrator-applications.show', $administratorApplication)
            ->with('success', 'Application rejected.');
    }

    private function authorizeApplicant(User $user): void
    {
        abort_unless($user->status === 'active' && $user->role !== 'admin', 403);
    }

    private function ensurePending(AdministratorApplication $application): void
    {
        if ($application->status !== 'pending') {
            throw ValidationException::withMessages([
                'application' => 'This administrator application has already been reviewed.',
            ]);
        }
    }
}
