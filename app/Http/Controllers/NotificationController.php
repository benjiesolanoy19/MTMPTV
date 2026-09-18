<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(15);
        return view('report-user.notifications.index', compact('notifications'));
    }

    public function read(Request $request, int $notification)
    {
        $request->user()->notifications()->whereKey($notification)->update(['read_at' => now()]);
        return back();
    }
}