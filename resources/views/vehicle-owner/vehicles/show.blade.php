@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <a href="{{ route('vehicle-owner.vehicles.index') }}" class="text-link"><i class="bi bi-arrow-left"></i> My vehicles</a>
        <h1>{{ $vehicle->plate_number }}</h1>
        <p class="muted">{{ $vehicle->vehicle_type }} · {{ $vehicle->make ?? '—' }} {{ $vehicle->model ?? '' }}</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="panel">
            <h3 class="mb-3">Vehicle information</h3>
            <dl class="row small">
                <dt class="col-5 text-muted">Plate number</dt><dd class="col-7">{{ $vehicle->plate_number }}</dd>
                <dt class="col-5 text-muted">Vehicle type</dt><dd class="col-7">{{ $vehicle->vehicle_type }}</dd>
                <dt class="col-5 text-muted">Make</dt><dd class="col-7">{{ $vehicle->make ?? '—' }}</dd>
                <dt class="col-5 text-muted">Model</dt><dd class="col-7">{{ $vehicle->model ?? '—' }}</dd>
                <dt class="col-5 text-muted">Year</dt><dd class="col-7">{{ $vehicle->year_model ?? '—' }}</dd>
                <dt class="col-5 text-muted">Color</dt><dd class="col-7">{{ $vehicle->color ?? '—' }}</dd>
                <dt class="col-5 text-muted">Engine number</dt><dd class="col-7">{{ $vehicle->engine_number ?? '—' }}</dd>
                <dt class="col-5 text-muted">Chassis number</dt><dd class="col-7">{{ $vehicle->chassis_number ?? '—' }}</dd>
                <dt class="col-5 text-muted">Registration number</dt><dd class="col-7">{{ $vehicle->registration_number ?? '—' }}</dd>
                <dt class="col-5 text-muted">Registration status</dt><dd class="col-7">{{ ucfirst($vehicle->status) }}</dd>
                <dt class="col-5 text-muted">Vehicle status</dt><dd class="col-7">{{ ucfirst($vehicle->status) }}</dd>
            </dl>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <h3 class="mb-3">Owner and operator</h3>
            <dl class="row small">
                <dt class="col-5 text-muted">Owner</dt><dd class="col-7">{{ $vehicle->operator->full_name }}</dd>
                <dt class="col-5 text-muted">Ownership status</dt><dd class="col-7">Owned</dd>
                <dt class="col-5 text-muted">Operator</dt><dd class="col-7">{{ $vehicle->operator?->full_name ?? '—' }}</dd>
                <dt class="col-5 text-muted">Operator status</dt><dd class="col-7">{{ $vehicle->operator?->status ? ucfirst($vehicle->operator->status) : '—' }}</dd>
            </dl>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="panel">
            <h3 class="mb-3">Permits</h3>
            @forelse($vehicle->permits as $permit)
                <div class="border rounded p-3 mb-2">
                    <strong>{{ $permit->permit_number }}</strong><br>
                    <small>{{ $permit->status }} · {{ $permit->issue_date->format('d M Y') }} to {{ $permit->expiry_date->format('d M Y') }}</small>
                </div>
            @empty
                <div class="empty"><span>No permits found.</span></div>
            @endforelse
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <h3 class="mb-3">Franchise</h3>
            @forelse($vehicle->franchises as $franchise)
                <div class="border rounded p-3 mb-2">
                    <strong>{{ $franchise->franchise_number }}</strong><br>
                    <small>{{ $franchise->status }} · {{ $franchise->issue_date->format('d M Y') }} to {{ $franchise->expiry_date->format('d M Y') }}</small>
                </div>
            @empty
                <div class="empty"><span>No franchise records found.</span></div>
            @endforelse
        </div>
    </div>
</div>

<div class="panel mt-4">
    <h3 class="mb-3">Violations</h3>
    @forelse($vehicle->violations as $violation)
        <div class="border rounded p-3 mb-2">
            <strong>{{ $violation->violation_number }}</strong>
            <div class="small text-muted">{{ $violation->violation_type }} · {{ $violation->violation_date->format('d M Y') }} · {{ $violation->status }}</div>
        </div>
    @empty
        <div class="empty"><span>No violations found.</span></div>
    @endforelse
</div>
@endsection
