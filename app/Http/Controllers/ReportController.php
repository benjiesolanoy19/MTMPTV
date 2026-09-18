<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = $this->filtered($request)->latest('date_submitted')->paginate(12)->withQueryString();
        return view('report-user.reports.index', compact('reports'));
    }

    public function show(Report $report)
    {
        return view('report-user.reports.show', compact('report'));
    }

    public function mine(Request $request)
    {
        $reports = $this->filtered($request)->where('submitted_by', auth()->id())->latest('date_submitted')->paginate(12)->withQueryString();
        return view('report-user.reports.mine', compact('reports'));
    }

    private function filtered(Request $request)
    {
        return Report::query()->when($request->search, fn ($q, $search) => $q->where(fn ($x) => $x->where('report_number', 'like', "%$search%")->orWhere('report_type', 'like', "%$search%")->orWhere('location', 'like', "%$search%")))->when($request->status, fn ($q, $status) => $q->where('status', $status))->when($request->date_from, fn ($q, $date) => $q->whereDate('date_submitted', '>=', $date))->when($request->date_to, fn ($q, $date) => $q->whereDate('date_submitted', '<=', $date));
    }
}