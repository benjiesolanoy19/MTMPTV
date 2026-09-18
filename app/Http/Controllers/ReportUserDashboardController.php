<?php

namespace App\Http\Controllers;

use App\Models\Report;

class ReportUserDashboardController extends Controller
{
    public function index()
    {
        $reports = Report::query()->latest('date_submitted')->paginate(5, ['*'], 'recent_page');
        $stats = [
            'total' => Report::count(),
            'pending' => Report::where('status', 'Pending')->count(),
            'review' => Report::where('status', 'Under Review')->count(),
            'resolved' => Report::whereIn('status', ['Resolved', 'Closed'])->count(),
        ];
        return view('report-user.dashboard', compact('reports', 'stats'));
    }
}