@extends('layouts.app')
@section('content')
<div class="page-head">
    <div><div class="eyebrow">ACCOUNT REQUEST</div><h1>Apply as Administrator</h1><p class="muted">Administrator access is granted only after an existing Administrator reviews your application.</p></div>
</div>

@if($application && $application->status === 'pending')
    <div class="panel">
        <div class="empty"><i data-lucide="hourglass" aria-hidden="true"></i><span>Your administrator application is currently under review.</span></div>
        <div class="mt-3"><strong>Submitted:</strong> {{ $application->submitted_at->format('F j, Y') }}</div>
        <div class="mt-3"><strong>Reason:</strong><p class="mb-0">{{ $application->reason }}</p></div>
    </div>
@else
    @if($application)
        <div class="alert {{ $application->status === 'approved' ? 'alert-success' : 'alert-warning' }}">
            Your previous application was {{ $application->status }} on {{ $application->reviewed_at?->format('F j, Y') ?? 'an earlier date' }}.
            @if($application->admin_remarks)<div class="mt-1"><strong>Administrator remarks:</strong> {{ $application->admin_remarks }}</div>@endif
            You may submit a new application below.
        </div>
    @endif
    <div class="panel">
        <div class="panel-head"><div><h3>Application details</h3><p class="muted">Your account identity and current role are filled from your signed-in account.</p></div></div>
        <div class="row g-3 mb-4">
            <div class="col-md-6"><small class="text-muted d-block">Applicant</small><strong>{{ auth()->user()->name }}</strong></div>
            <div class="col-md-6"><small class="text-muted d-block">Current role</small><strong>{{ auth()->user()->roleLabel() }}</strong></div>
            <div class="col-md-6"><small class="text-muted d-block">Contact email</small><strong>{{ auth()->user()->email }}</strong></div>
            <div class="col-md-6"><small class="text-muted d-block">Contact number</small><strong>{{ auth()->user()->mobile_number }}</strong></div>
        </div>
        <form method="POST" action="{{ route('administrator-application.store') }}" class="row g-3">
            @csrf
            <div class="col-12"><label for="reason" class="form-label">Why are you applying? <span class="text-danger">*</span></label><textarea id="reason" name="reason" class="form-control" rows="4" maxlength="5000" required>{{ old('reason') }}</textarea></div>
            <div class="col-12"><label for="experience" class="form-label">Relevant experience</label><textarea id="experience" name="experience" class="form-control" rows="3" maxlength="5000">{{ old('experience') }}</textarea></div>
            <div class="col-12"><label for="additional_information" class="form-label">Additional information</label><textarea id="additional_information" name="additional_information" class="form-control" rows="3" maxlength="5000">{{ old('additional_information') }}</textarea></div>
            <div class="col-12"><p class="small text-muted mb-0">Submitting this form does not change your role. Your request will remain pending until reviewed by an Administrator.</p></div>
            <div class="col-12"><button type="submit" class="btn btn-primary"><i data-lucide="send" class="me-1" aria-hidden="true"></i> Submit application</button></div>
        </form>
    </div>
@endif
@endsection
