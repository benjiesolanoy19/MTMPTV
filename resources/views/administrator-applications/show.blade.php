@extends('layouts.app')
@section('content')
<div class="page-head"><div><div class="eyebrow">APPLICATION REVIEW</div><h1>Administrator application</h1><p class="muted">Review the applicant's request. Decisions are recorded in the system activity log.</p></div><a href="{{ route('administrator-applications.index') }}" class="btn btn-light border"><i data-lucide="arrow-left" aria-hidden="true"></i> All applications</a></div>
<div class="panel">
    <div class="row g-4">
        <div class="col-md-6"><small class="text-muted d-block">Applicant</small><strong>{{ $application->applicant->name }}</strong><div>{{ '@'.$application->applicant->username }} · {{ $application->applicant->email }}</div></div>
        <div class="col-md-3"><small class="text-muted d-block">Current role</small><strong>{{ $application->applicant->roleLabel() }}</strong></div>
        <div class="col-md-3"><small class="text-muted d-block">Status</small><span class="badge {{ $application->status === 'pending' ? 'bg-warning-subtle text-warning-emphasis' : ($application->status === 'approved' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary') }}">{{ ucfirst($application->status) }}</span></div>
        <div class="col-md-6"><small class="text-muted d-block">Contact</small><strong>{{ $application->applicant->mobile_number }}</strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Submitted</small><strong>{{ $application->submitted_at->format('F j, Y g:i A') }}</strong></div>
        <div class="col-12"><small class="text-muted d-block">Reason</small><p class="mb-0">{{ $application->reason }}</p></div>
        <div class="col-12"><small class="text-muted d-block">Relevant experience</small><p class="mb-0">{{ $application->experience ?: 'Not provided.' }}</p></div>
        <div class="col-12"><small class="text-muted d-block">Additional information</small><p class="mb-0">{{ $application->additional_information ?: 'Not provided.' }}</p></div>
        @if($application->reviewed_at)
            <div class="col-md-6"><small class="text-muted d-block">Reviewed</small><strong>{{ $application->reviewed_at->format('F j, Y g:i A') }}</strong></div>
            <div class="col-md-6"><small class="text-muted d-block">Reviewer</small><strong>{{ $application->reviewer?->name ?? 'Unavailable' }}</strong></div>
            <div class="col-12"><small class="text-muted d-block">Administrator remarks</small><p class="mb-0">{{ $application->admin_remarks ?: 'None.' }}</p></div>
        @endif
    </div>
    @if($application->status === 'pending')
        <hr class="my-4">
        <div class="row g-4">
            <div class="col-md-6">
                <form method="POST" action="{{ route('administrator-applications.approve', $application) }}">
                    @csrf
                    <label for="approval-remarks" class="form-label">Approval remarks (optional)</label>
                    <textarea id="approval-remarks" name="admin_remarks" class="form-control mb-3" rows="3" maxlength="5000"></textarea>
                    <button class="btn btn-primary" type="submit"><i data-lucide="check" aria-hidden="true"></i> Approve and promote</button>
                </form>
            </div>
            <div class="col-md-6">
                <form method="POST" action="{{ route('administrator-applications.reject', $application) }}">
                    @csrf
                    <label for="rejection-remarks" class="form-label">Rejection remarks <span class="text-danger">*</span></label>
                    <textarea id="rejection-remarks" name="admin_remarks" class="form-control mb-3" rows="3" maxlength="5000" required></textarea>
                    <button class="btn btn-outline-danger" type="submit"><i data-lucide="x" aria-hidden="true"></i> Reject application</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
