@extends('layouts.app')
@section('content')
<div class="page-head"><div><div class="eyebrow">USER MANAGEMENT</div><h1>Administrator applications</h1><p class="muted">Review and decide requests for elevated system access.</p></div></div>
<div class="panel">
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-5"><input name="search" class="form-control" placeholder="Search applicant name, username, or email" value="{{ request('search') }}"></div>
        <div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option>@foreach(['pending','approved','rejected'] as $value)<option value="{{ $value }}" @selected($status === $value)>{{ ucfirst($value) }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-light border"><i data-lucide="search" aria-hidden="true"></i> Search</button></div>
    </form>
    <div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>Applicant</th><th>Current role</th><th>Submitted</th><th>Status</th><th>Reviewed</th><th></th></tr></thead>
        <tbody>
        @forelse($applications as $application)
            <tr>
                <td><strong>{{ $application->applicant->name }}</strong><small class="d-block text-muted">{{ '@'.$application->applicant->username }} · {{ $application->applicant->email }}</small></td>
                <td>{{ $application->applicant->roleLabel() }}</td>
                <td>{{ $application->submitted_at->format('d M Y') }}</td>
                <td><span class="badge {{ $application->status === 'pending' ? 'bg-warning-subtle text-warning-emphasis' : ($application->status === 'approved' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary') }}">{{ ucfirst($application->status) }}</span></td>
                <td>{{ $application->reviewed_at?->format('d M Y') ?? '—' }}</td>
                <td><a href="{{ route('administrator-applications.show', $application) }}" class="btn btn-sm btn-light border">Review <i data-lucide="arrow-right" aria-hidden="true"></i></a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center py-5 text-muted">No administrator applications found.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $applications->links() }}
</div>
@endsection
