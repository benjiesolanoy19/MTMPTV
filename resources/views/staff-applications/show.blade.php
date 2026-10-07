@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">APPLICATION REVIEW</div>
        <h1>Staff application</h1>
        <p class="muted">Review applicant details and make a decision.</p>
    </div>
    <a href="{{ route('staff-applications.index') }}" class="btn btn-light border">All applications</a>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-head">
                <div><h3>Applicant information</h3></div>
            </div>
            <dl class="row mb-0">
                <dt class="col-sm-4">Applicant</dt><dd class="col-sm-8">{{ $application->full_name }}</dd>
                <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $application->applicant->email ?? '—' }}</dd>
                <dt class="col-sm-4">Contact number</dt><dd class="col-sm-8">{{ $application->contact_number }}</dd>
                <dt class="col-sm-4">Address</dt><dd class="col-sm-8">{{ $application->address }}</dd>
                <dt class="col-sm-4">Date of birth</dt><dd class="col-sm-8">{{ $application->date_of_birth?->format('F d, Y') ?? '—' }}</dd>
            </dl>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="panel">
            <div class="panel-head"><div><h3>Application status</h3></div></div>
            <p><strong>Status:</strong>
                @if($application->status === 'pending')
                    <span class="badge badge-warning">Pending</span>
                @elseif($application->status === 'approved')
                    <span class="badge badge-success">Approved</span>
                @else
                    <span class="badge badge-danger">Declined</span>
                @endif
            </p>
            <p><strong>Submitted:</strong> {{ $application->submitted_at?->format('F d, Y h:i A') ?? '—' }}</p>
            @if($application->reviewed_by)
                <p><strong>Reviewed by:</strong> {{ $application->reviewer?->name ?? 'Administrator' }}</p>
            @endif
            @if($application->admin_remarks)
                <p><strong>Administrator remarks:</strong> {{ $application->admin_remarks }}</p>
            @endif
            @if($application->status === 'pending')
                <form method="POST" action="{{ route('staff-applications.approve', $application) }}" class="mb-2">
                    @csrf
                    <textarea name="admin_remarks" class="form-control mb-2" rows="3" placeholder="Approval remarks (optional)"></textarea>
                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('Approve this Staff application?')">Accept</button>
                </form>
                <form method="POST" action="{{ route('staff-applications.reject', $application) }}">
                    @csrf
                    <textarea name="admin_remarks" class="form-control mb-2" rows="3" placeholder="Reason for decline" required></textarea>
                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Decline this Staff application?')">Decline</button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="panel mt-4">
    <div class="panel-head"><div><h3>Application information</h3></div></div>
    <dl class="row mb-0">
        <dt class="col-sm-4">Preferred position</dt><dd class="col-sm-8">{{ $application->preferred_position }}</dd>
        <dt class="col-sm-4">Department</dt><dd class="col-sm-8">{{ $application->department ?: '—' }}</dd>
        <dt class="col-sm-4">Relevant skills</dt><dd class="col-sm-8">{{ $application->skills ?: '—' }}</dd>
        <dt class="col-sm-4">Experience</dt><dd class="col-sm-8">{{ $application->experience ?: '—' }}</dd>
        <dt class="col-sm-4">Reason for applying</dt><dd class="col-sm-8">{{ $application->reason }}</dd>
        <dt class="col-sm-4">Additional information</dt><dd class="col-sm-8">{{ $application->additional_information ?: '—' }}</dd>
    </dl>
</div>
@endsection
