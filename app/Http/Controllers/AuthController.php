<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\{Operator, User};
use App\Http\Requests\RegisterUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }
    public function showRegister() { return view('auth.register'); }
    public function register(RegisterUserRequest $request)
    {
        $data = $request->validated();
        unset($data['terms'], $data['privacy']);
        $data['status'] = 'active';
        $user = User::create($data);
        if ($user->role === 'operator') {
            Operator::create([
                'user_id' => $user->id,
                'operator_code' => 'OP-'.now()->format('Ymd').'-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                'first_name' => $user->name,
                'last_name' => 'Operator',
                'address' => $user->address,
                'contact_number' => $user->mobile_number,
                'email' => $user->email,
                'status' => 'active',
            ]);
        }
        AuditLog::create(['user_id' => $user->id, 'action' => 'registered', 'module' => 'Authentication', 'record_id' => $user->id, 'description' => 'New account registered', 'ip_address' => $request->ip()]);
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
