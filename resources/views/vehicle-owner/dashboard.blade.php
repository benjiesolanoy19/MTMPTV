@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">VEHICLE OWNER WORKSPACE</div>
        <h1>Welcome, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
        <p class="muted">Your registered vehicles, applications, permits, and compliance records.</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-icon blue"><i class="bi bi-truck"></i></span>
        <div>
            <small>My vehicles</small>
            <strong>{{ $stats['vehicles'] }}</strong>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon teal"><i class="bi bi-card-checklist"></i></span>
        <div>
            <small>Active permits</small>
            <strong>{{ $stats['active_permits'] }}</strong>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon amber"><i class="bi bi-hourglass-split"></i></span>
        <div>
            <small>Pending applications</small>
            <strong>{{ $stats['pending_applications'] }}</strong>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon coral"><i class="bi bi-calendar-event"></i></span>
        <div>
            <small>Upcoming renewals</small>
            <strong>{{ $stats['upcoming_renewals'] }}</strong>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon red"><i class="bi bi-exclamation-triangle"></i></span>
        <div>
            <small>Active violations</small>
            <strong>{{ $stats['active_violations'] }}</strong>
        </div>
    </div>
</div>

<div class="panel mt-4">
    <div class="panel-head">
        <div>
            <h3>Recent applications</h3>
            <p class="muted">Latest applications linked to your vehicles.</p>
        </div>
        <a href="{{ route('vehicle-owner.applications.index') }}" class="text-link">View all <i class="bi bi-arrow-up-right"></i></a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Application</th>
                    <th>Type</th>
                    <th>Vehicle</th>
                    <th>Status</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                    <tr>
                        <td><a href="{{ route('vehicle-owner.applications.show', $application) }}" class="text-decoration-none fw-semibold">{{ $application->application_number }}</a></td>
                        <td>{{ $application->application_type }}</td>
                        <td>{{ $application->vehicle?->plate_number ?? '—' }}</td>
                        <td>{{ $application->status }}</td>
                        <td>{{ $application->date_submitted->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No applications found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
