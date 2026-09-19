@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <a href="{{ route('vehicle-owner.applications.index') }}" class="text-link"><i class="bi bi-arrow-left"></i> My applications</a>
        <h1>{{ $application->application_number }}</h1>
        <p class="muted">{{ $application->application_type }} · {{ $application->status }}</p>
    </div>
</div>

<div class="panel">
    <dl class="row small">
        <dt class="col-md-3 text-muted">Application ID</dt><dd class="col-md-9">{{ $application->application_number }}</dd>
        <dt class="col-md-3 text-muted">Application type</dt><dd class="col-md-9">{{ $application->application_type }}</dd>
        <dt class="col-md-3 text-muted">Vehicle</dt><dd class="col-md-9">{{ $application->vehicle?->plate_number ?? '—' }}</dd>
        <dt class="col-md-3 text-muted">Date submitted</dt><dd class="col-md-9">{{ $application->date_submitted->format('d M Y') }}</dd>
        <dt class="col-md-3 text-muted">Current status</dt><dd class="col-md-9">{{ $application->status }}</dd>
        <dt class="col-md-3 text-muted">Submitted information</dt><dd class="col-md-9">{{ $application->remarks ?: '—' }}</dd>
    </dl>
</div>
@endsection
