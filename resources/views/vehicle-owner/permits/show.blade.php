@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <a href="{{ route('vehicle-owner.permits.index') }}" class="text-link"><i class="bi bi-arrow-left"></i> My permits</a>
        <h1>{{ $permit->permit_number }}</h1>
        <p class="muted">Permit record for {{ $permit->vehicle?->plate_number ?? 'this vehicle' }}</p>
    </div>
</div>

<div class="panel">
    <dl class="row small">
        <dt class="col-md-3 text-muted">Permit number</dt><dd class="col-md-9">{{ $permit->permit_number }}</dd>
        <dt class="col-md-3 text-muted">Vehicle</dt><dd class="col-md-9">{{ $permit->vehicle?->plate_number ?? '—' }}</dd>
        <dt class="col-md-3 text-muted">Permit type</dt><dd class="col-md-9">{{ $permit->application?->application_type ?? '—' }}</dd>
        <dt class="col-md-3 text-muted">Issue date</dt><dd class="col-md-9">{{ $permit->issue_date->format('d M Y') }}</dd>
        <dt class="col-md-3 text-muted">Expiry date</dt><dd class="col-md-9">{{ $permit->expiry_date->format('d M Y') }}</dd>
        <dt class="col-md-3 text-muted">Status</dt><dd class="col-md-9">{{ $permit->status }}</dd>
    </dl>
</div>
@endsection
