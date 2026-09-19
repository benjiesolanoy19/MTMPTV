@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">APPLICATIONS</div>
        <h1>My applications</h1>
        <p class="muted">Applications submitted for your vehicles.</p>
    </div>
    <a href="{{ route('vehicle-owner.applications.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Application</a>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Application ID</th>
                    <th>Type</th>
                    <th>Vehicle</th>
                    <th>Date submitted</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                    <tr>
                        <td>{{ $application->application_number }}</td>
                        <td>{{ $application->application_type }}</td>
                        <td>{{ $application->vehicle?->plate_number ?? '—' }}</td>
                        <td>{{ $application->date_submitted->format('d M Y') }}</td>
                        <td>{{ $application->status }}</td>
                        <td><a href="{{ route('vehicle-owner.applications.show', $application) }}" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i> View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">No applications found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $applications->links() }}
</div>
@endsection
