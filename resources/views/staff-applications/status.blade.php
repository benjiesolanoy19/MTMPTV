@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">STAFF APPLICATION</div>
        <h1>Status</h1>
        <p class="muted">Track the review progress of your staff application.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h3>Application status</h3>
                    <p class="muted">Your staff application is currently being reviewed.</p>
                </div>
            </div>
            <div class="mb-3">
                <strong>Status:</strong>
                @if($application->status === 'pending')
                    <span class="badge badge-warning">Pending Administrator Review</span>
                @elseif($application->status === 'approved')
                    <span class="badge badge-success">Approved</span>
                @else
                    <span class="badge badge-danger">Declined</span>
                @endif
            </div>
            <p>Your Staff application has been submitted and is currently waiting for Administrator approval.</p>
            <dl class="row mt-4">
                <dt class="col-sm-4">Application date</dt>
                <dd class="col-sm-8">{{ $application->submitted_at?->format('F d, Y') ?? '—' }}</dd>
                <dt class="col-sm-4">Applicant</dt>
                <dd class="col-sm-8">{{ $application->full_name }}</dd>
                <dt class="col-sm-4">Preferred position</dt>
                <dd class="col-sm-8">{{ $application->preferred_position }}</dd>
                @if($application->admin_remarks)
                    <dt class="col-sm-4">Administrator remarks</dt>
                    <dd class="col-sm-8">{{ $application->admin_remarks }}</dd>
                @endif
            </dl>
        </div>
    </div>
</div>
@endsection
