@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">VEHICLES</div>
        <h1>My vehicles</h1>
        <p class="muted">Vehicles registered under your ownership.</p>
    </div>
    <a href="{{ route('vehicle-owner.vehicles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Register Vehicle</a>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Plate</th>
                    <th>Type</th>
                    <th>Make</th>
                    <th>Model</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehicles as $vehicle)
                    <tr>
                        <td class="fw-semibold">{{ $vehicle->plate_number }}</td>
                        <td>{{ $vehicle->vehicle_type }}</td>
                        <td>{{ $vehicle->make ?? '—' }}</td>
                        <td>{{ $vehicle->model ?? '—' }}</td>
                        <td>{{ $vehicle->year_model ?? '—' }}</td>
                        <td>{{ ucfirst($vehicle->status) }}</td>
                        <td class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('vehicle-owner.vehicles.show', $vehicle) }}" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i> View Details</a>
                            <a href="{{ route('vehicle-owner.vehicles.location', $vehicle) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-geo-alt"></i> View Location</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No vehicles registered yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $vehicles->links() }}
</div>
@endsection
