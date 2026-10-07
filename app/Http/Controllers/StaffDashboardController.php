<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class StaffDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->canAccessStaff(), 403);

        return view('staff.dashboard', ['user' => $user]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->canAccessStaff(), 403);

        return view('staff.profile', ['user' => $user->load('staffProfile')]);
    }
}
