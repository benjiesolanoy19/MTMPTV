<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function create()
    {
        return view('report-user.reports.create', ['apiKey' => config('services.google_maps.api_key')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'report_type' => ['required', 'string', 'max:80'],
            'date_submitted' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $data['submitted_by'] = auth()->id();
        $data['report_number'] = 'RPT-'.now()->format('YmdHis').'-'.auth()->id();
        $data['status'] = 'Submitted';

        $report = Report::create($data);

        return redirect()->route('reports.show', $report)->with('success', 'Report submitted successfully.');
    }

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