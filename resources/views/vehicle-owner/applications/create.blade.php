@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">APPLICATIONS / NEW</div>
        <h1>Submit application</h1>
        <p class="muted">Submit an application for one of your registered vehicles.</p>
    </div>
</div>

<div class="panel">
    <form method="POST" action="{{ route('vehicle-owner.applications.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Vehicle</label>
                <select name="vehicle_id" class="form-select" required>
                    <option value="">Select vehicle</option>
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>{{ $vehicle->plate_number }} · {{ $vehicle->vehicle_type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Application type</label>
                <select name="application_type" class="form-select" required>
                    <option value="">Select type</option>
                    @foreach(['Registration','New Permit','Permit Renewal','New Franchise','Franchise Renewal'] as $type)
                        <option value="{{ $type }}" @selected(old('application_type') == $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Date submitted</label>
                <input type="date" name="date_submitted" class="form-control" value="{{ old('date_submitted', now()->toDateString()) }}" required>
            </div>
            <div class="col-12">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="4">{{ old('remarks') }}</textarea>
            </div>
        </div>
        <button class="btn btn-primary mt-4">Submit application</button>
    </form>
</div>
@endsection
