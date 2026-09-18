<?php

namespace App\Http\Controllers;

use App\Models\{Application, AuditLog, Franchise, Operator, Permit, Renewal, Violation};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class OperatorPortalController extends Controller
{
    private function operator(): Operator
    {
        $operator = auth()->user()->operatorProfile;
        abort_unless($operator, 404);
        return $operator;
    }

    public function dashboard()
    {
        $operator = $this->operator();
        $stats = [
            'vehicles' => $operator->vehicles()->where('status', 'active')->count(),
            'applications' => $operator->applications()->whereIn('status', ['Pending', 'Under Review'])->count(),
            'permits' => $operator->permits()->where('expiry_date', '>=', today())->count(),
            'renewals' => $operator->renewals()->whereIn('status', ['Pending', 'Approved'])->whereBetween('new_expiry_date', [today(), today()->addDays(60)])->count(),
            'violations' => $operator->violations()->whereIn('status', ['Open'])->count(),
        ];
        $applications = $operator->applications()->with('vehicle')->latest()->limit(5)->get();
        return view('operator.dashboard', compact('operator', 'stats', 'applications'));
    }

    public function profile()
    {
        return view('operator.profile', ['operator' => $this->operator(), 'user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate(['name' => 'required|string|max:120', 'email' => 'required|email|unique:users,email,'.$user->id, 'mobile_number' => ['required', 'regex:/^(?:\+63|0)9\d{9}$/'], 'address' => 'required|string|max:500']);
        $user->update($data);
        $operator = $this->operator();
        $operator->update(['first_name' => $data['name'], 'email' => $data['email'], 'contact_number' => $data['mobile_number'], 'address' => $data['address']]);
        AuditLog::create(['user_id' => $user->id, 'action' => 'updated', 'module' => 'Operator Profile', 'record_id' => $operator->id, 'description' => 'Operator updated personal information', 'ip_address' => $request->ip()]);
        return back()->with('success', 'Operator profile updated.');
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate(['current_password' => 'required', 'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()]]);
        abort_unless(Hash::check($data['current_password'], auth()->user()->password), 422, 'The current password is incorrect.');
        auth()->user()->update(['password' => $data['password']]);
        return back()->with('success', 'Password changed successfully.');
    }

    public function vehicles() { $operator = $this->operator(); $vehicles = $operator->vehicles()->latest()->paginate(10); return view('operator.vehicles', compact('operator', 'vehicles')); }
    public function applications() { $operator = $this->operator(); $applications = $operator->applications()->with('vehicle')->latest()->paginate(10); return view('operator.applications.index', compact('operator', 'applications')); }
    public function createApplication() { $operator = $this->operator(); $vehicles = $operator->vehicles()->where('status', 'active')->get(); return view('operator.applications.create', compact('operator', 'vehicles')); }

    public function storeApplication(Request $request)
    {
        $operator = $this->operator();
        $data = $request->validate(['vehicle_id' => 'required|integer', 'application_type' => 'required|in:New Franchise,Franchise Renewal,New Permit,Permit Renewal,Registration', 'date_submitted' => 'required|date', 'remarks' => 'nullable|string|max:2000']);
        abort_unless($operator->vehicles()->whereKey($data['vehicle_id'])->exists(), 403);
        $duplicate = $operator->applications()->where('vehicle_id', $data['vehicle_id'])->where('application_type', $data['application_type'])->whereIn('status', ['Pending', 'Under Review'])->exists();
        if ($duplicate) return back()->withErrors(['vehicle_id' => 'A matching application is already being processed.'])->withInput();
        $data['operator_id'] = $operator->id;
        $data['application_number'] = 'APP-'.now()->format('YmdHis').'-'.$operator->id;
        $application = Application::create($data);
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'submitted', 'module' => 'Operator Applications', 'record_id' => $application->id, 'description' => 'Operator submitted an application', 'ip_address' => $request->ip()]);
        return redirect()->route('operator.applications.show', $application)->with('success', 'Application submitted for review.');
    }

    public function application(Application $application) { abort_unless($application->operator_id === $this->operator()->id, 404); $application->load('vehicle'); return view('operator.applications.show', compact('application')); }
    public function permits() { $operator = $this->operator(); $permits = $operator->permits()->with('vehicle')->latest()->paginate(10); return view('operator.permits', compact('operator', 'permits')); }
    public function permit(Permit $permit) { abort_unless($permit->operator_id === $this->operator()->id, 404); $permit->load('vehicle'); return view('operator.permit', compact('permit')); }
    public function franchises() { $operator = $this->operator(); $franchises = $operator->franchises()->with('vehicle')->latest()->paginate(10); return view('operator.franchises', compact('operator', 'franchises')); }
    public function franchise(Franchise $franchise) { abort_unless($franchise->operator_id === $this->operator()->id, 404); $franchise->load('vehicle'); return view('operator.franchise', compact('franchise')); }
    public function renewals() { $operator = $this->operator(); $renewals = $operator->renewals()->with('vehicle')->latest()->paginate(10); return view('operator.renewals', compact('operator', 'renewals')); }
    public function violations() { $operator = $this->operator(); $violations = $operator->violations()->with('vehicle')->latest('violation_date')->paginate(10); return view('operator.violations', compact('operator', 'violations')); }
    public function notifications() { $notifications = auth()->user()->notifications()->latest()->paginate(15); return view('operator.notifications', compact('notifications')); }
}
