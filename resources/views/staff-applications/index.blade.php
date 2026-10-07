@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">STAFF APPLICATIONS</div>
        <h1>Staff applications</h1>
        <p class="muted">Review pending staff access requests.</p>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <div>
            <h3>Application queue</h3>
        </div>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search applicant">
            <select name="status" class="form-select">
                <option value="">All</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                <option value="declined" @selected(request('status') === 'declined')>Declined</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                    <tr>
                        <td>
                            <strong>{{ $application->full_name }}</strong><br>
                            <small class="muted">{{ $application->applicant->username ?? '—' }}</small>
                        </td>
                        <td>{{ $application->preferred_position }}</td>
                        <td>
                            @if($application->status === 'pending')
                                <span class="badge badge-warning">Pending</span>
                            @elseif($application->status === 'approved')
                                <span class="badge badge-success">Approved</span>
                            @else
                                <span class="badge badge-danger">Declined</span>
                            @endif
                        </td>
                        <td class="text-end"><a href="{{ route('staff-applications.show', $application) }}" class="btn btn-sm btn-light border">View</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4">No staff applications found.</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-4">No staff applications found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $applications->links() }}</div>
</div>
@endsection
