@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <a href="{{ route('vehicle-owner.franchises.index') }}" class="text-link"><i class="bi bi-arrow-left"></i> My franchise</a>
        <h1>{{ $franchise->franchise_number }}</h1>
        <p class="muted">Franchise record for {{ $franchise->vehicle?->plate_number ?? 'this vehicle' }}</p>
    </div>
</div>

<div class="panel">
    <dl class="row small">
        <dt class="col-md-3 text-muted">Franchise number</dt><dd class="col-md-9">{{ $franchise->franchise_number }}</dd>
        <dt class="col-md-3 text-muted">Vehicle</dt><dd class="col-md-9">{{ $franchise->vehicle?->plate_number ?? '—' }}</dd>
        <dt class="col-md-3 text-muted">Route</dt><dd class="col-md-9">{{ $franchise->remarks ?? '—' }}</dd>
        <dt class="col-md-3 text-muted">Issue date</dt><dd class="col-md-9">{{ $franchise->issue_date->format('d M Y') }}</dd>
        <dt class="col-md-3 text-muted">Expiry date</dt><dd class="col-md-9">{{ $franchise->expiry_date->format('d M Y') }}</dd>
        <dt class="col-md-3 text-muted">Status</dt><dd class="col-md-9">{{ $franchise->status }}</dd>
    </dl>
</div>
@endsection
