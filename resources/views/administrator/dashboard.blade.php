@extends('layouts.app')
@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">SYSTEM ADMINISTRATION · {{ strtoupper(now()->format('l, d M Y')) }}</div>
        <h1>Welcome, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
        <p class="muted">A live overview of users, public reports, and administrator access requests.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('users.index') }}" class="btn btn-primary"><i data-lucide="users-round" class="me-1" aria-hidden="true"></i> Manage users</a>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card"><span class="stat-icon blue"><i data-lucide="users-round" aria-hidden="true"></i></span><div><small>Total users</small><strong>{{ number_format($stats['users']) }}</strong><span class="trend">{{ number_format($stats['active_users']) }} active</span></div></div>
    <div class="stat-card"><span class="stat-icon teal"><i data-lucide="file-text" aria-hidden="true"></i></span><div><small>Total reports</small><strong>{{ number_format($stats['reports']) }}</strong><span class="trend">All submitted reports</span></div></div>
    <div class="stat-card"><span class="stat-icon amber"><i data-lucide="clock-3" aria-hidden="true"></i></span><div><small>Pending reports</small><strong>{{ number_format($stats['pending_reports']) }}</strong><span class="trend warning">Awaiting review</span></div></div>
    <div class="stat-card"><span class="stat-icon blue"><i data-lucide="scan-eye" aria-hidden="true"></i></span><div><small>Under review</small><strong>{{ number_format($stats['under_review_reports']) }}</strong><span class="trend">Reports in progress</span></div></div>
    <div class="stat-card"><span class="stat-icon teal"><i data-lucide="circle-check" aria-hidden="true"></i></span><div><small>Resolved reports</small><strong>{{ number_format($stats['resolved_reports']) }}</strong><span class="trend">Marked resolved</span></div></div>
    <div class="stat-card"><span class="stat-icon coral"><i data-lucide="shield-check" aria-hidden="true"></i></span><div><small>Administrator applications</small><strong>{{ number_format($stats['pending_administrator_applications']) }}</strong><span class="trend warning">Pending decision</span></div></div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-head">
                <div><h3>Administrator applications</h3><p class="muted">Pending requests for elevated access</p></div>
                <a href="{{ route('administrator-applications.index', ['status' => 'pending']) }}" class="text-link">Review queue <i data-lucide="arrow-up-right" aria-hidden="true"></i></a>
            </div>
            @forelse($recentApplications as $application)
                <a class="alert-row text-decoration-none text-reset" href="{{ route('administrator-applications.show', $application) }}">
                    <div><strong>{{ $application->applicant->name }}</strong><small>{{ '@'.$application->applicant->username }} · {{ $application->applicant->roleLabel() }}</small></div>
                    <span class="badge badge-warning">{{ $application->submitted_at->diffForHumans() }}</span>
                </a>
            @empty
                <div class="empty"><i data-lucide="inbox" aria-hidden="true"></i><span>No pending administrator applications.</span></div>
            @endforelse
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-head"><div><h3>Recent system activity</h3><p class="muted">Latest recorded administrative and user actions</p></div><a href="{{ route('system-activity.index') }}" class="text-link">View activity <i data-lucide="arrow-up-right" aria-hidden="true"></i></a></div>
            @forelse($recentActivity as $entry)
                <div class="alert-row">
                    <div><strong>{{ $entry->description }}</strong><small>{{ $entry->user?->name ?? 'System' }} · {{ $entry->module }}</small></div>
                    <small>{{ $entry->created_at->diffForHumans() }}</small>
                </div>
            @empty
                <div class="empty"><i data-lucide="history" aria-hidden="true"></i><span>No system activity recorded yet.</span></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
