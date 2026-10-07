<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\{Notification, Operator, StaffApplication, User};
use App\Http\Requests\RegisterUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }
    public function showRegister() { return view('auth.register'); }
    public function register(RegisterUserRequest $request)
    {
        $data = $request->validated();
        unset($data['terms'], $data['privacy']);

        $isStaffApplication = ($data['role'] ?? null) === 'staff';
        if ($isStaffApplication) {
            $data['role'] = 'viewer';
        }

        $data['status'] = 'active';
        $user = DB::transaction(function () use ($data, $request, $isStaffApplication) {
            $user = User::create($data);

            if ($isStaffApplication) {
                $application = StaffApplication::create([
                    'user_id' => $user->id,
                    'full_name' => $user->name,
                    'contact_number' => $user->mobile_number,
                    'address' => $user->address,
                    'date_of_birth' => now()->subYears(18)->toDateString(),
                    'preferred_position' => $request->string('staff_position')->toString(),
                    'department' => $request->string('staff_department')->toString(),
                    'skills' => $request->string('staff_skills')->toString(),
                    'experience' => $request->string('staff_experience')->toString(),
                    'reason' => $request->string('staff_reason')->toString(),
                    'additional_information' => $request->string('staff_additional_information')->toString() ?: null,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]);

                foreach (User::query()->where('role', 'admin')->where('status', 'active')->cursor() as $administrator) {
                    Notification::create([
                        'user_id' => $administrator->id,
                        'title' => 'New Staff application requires review.',
                        'message' => $application->full_name.' has submitted a Staff application for review.',
                        'type' => 'info',
                        'action_url' => route('staff-applications.show', $application, false),
                    ]);
                }
            }

            if (in_array($user->role, ['operator', 'vehicle_owner'], true)) {
                Operator::create([
                    'user_id' => $user->id,
                    'operator_code' => ($user->role === 'vehicle_owner' ? 'VO-' : 'OP-').now()->format('Ymd').'-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                    'first_name' => $user->name,
                    'last_name' => '',
                    'address' => $user->address,
                    'contact_number' => $user->mobile_number,
                    'email' => $user->email,
                    'status' => 'active',
                ]);
            }

            AuditLog::create(['user_id' => $user->id, 'action' => 'registered', 'module' => 'Authentication', 'record_id' => $user->id, 'description' => 'New account registered', 'ip_address' => $request->ip()]);
            return $user;
        });

        if ($isStaffApplication) {
            Auth::login($user);
            return redirect()->route('staff-application.status')->with('success', 'Your Staff application has been successfully submitted and is pending administrator review.');
        }

        return redirect()->route('login')->with('success', 'Account created successfully. You can now sign in.');
    }
    public function login(Request $request)
    {
        $credentials = $request->validate(['login' => ['required', 'string'], 'password' => ['required']]);
        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        if (!Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']], $request->boolean('remember')) || Auth::user()->status !== 'active') {
            Auth::logout();
            return back()->withErrors(['login' => 'The credentials are invalid or this account is inactive.'])->onlyInput('login');
        }
        $request->session()->regenerate();
        $request->user()->update(['last_login_at' => now()]);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'login', 'module' => 'Authentication', 'description' => 'User logged in', 'ip_address' => $request->ip()]);
        return redirect()->intended(route('dashboard'));
    }
    public function logout(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
