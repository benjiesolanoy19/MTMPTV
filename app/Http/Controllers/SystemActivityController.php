<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

class SystemActivityController extends Controller
{
    public function index()
    {
        $activity = AuditLog::with('user')->latest()->paginate(20);

        return view('system-activity.index', compact('activity'));
    }
}
