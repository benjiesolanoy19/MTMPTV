@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">VEHICLES / REGISTER</div>
        <h1>Register vehicle</h1>
        <p class="muted">Submit your vehicle information for municipal review.</p>
    </div>
</div>

<div class="panel">
    <form method="POST" action="{{ route('vehicle-owner.vehicles.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Plate number</label>
                <input name="plate_number" class="form-control" value="{{ old('plate_number') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Vehicle type</label>
                <input name="vehicle_type" class="form-control" value="{{ old('vehicle_type', 'Tricycle') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Make</label>
                <input name="make" class="form-control" value="{{ old('make') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Model</label>
                <input name="model" class="form-control" value="{{ old('model') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Year</label>
                <input type="number" name="year_model" class="form-control" value="{{ old('year_model') }}" min="1900" max="2100">
            </div>
            <div class="col-md-4">
                <label class="form-label">Color</label>
                <input name="color" class="form-control" value="{{ old('color') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Engine number</label>
                <input name="engine_number" class="form-control" value="{{ old('engine_number') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Chassis number</label>
                <input name="chassis_number" class="form-control" value="{{ old('chassis_number') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Registration number</label>
                <input name="registration_number" class="form-control" value="{{ old('registration_number') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Registration expiry</label>
                <input type="date" name="registration_expiry" class="form-control" value="{{ old('registration_expiry') }}">
            </div>
        </div>
        <button class="btn btn-primary mt-4">Register vehicle</button>
    </form>
</div>
@endsection
