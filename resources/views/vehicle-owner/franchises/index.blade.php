@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">FRANCHISE</div>
        <h1>My franchise</h1>
        <p class="muted">Franchise records linked to your vehicles.</p>
    </div>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Franchise number</th>
                    <th>Vehicle</th>
                    <th>Route</th>
                    <th>Issue date</th>
                    <th>Expiry date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($franchises as $franchise)
                    <tr>
                        <td>{{ $franchise->franchise_number }}</td>
                        <td>{{ $franchise->vehicle?->plate_number ?? '—' }}</td>
                        <td>{{ $franchise->remarks ?? '—' }}</td>
                        <td>{{ $franchise->issue_date->format('d M Y') }}</td>
                        <td>{{ $franchise->expiry_date->format('d M Y') }}</td>
                        <td>{{ $franchise->status }}</td>
                        <td><a href="{{ route('vehicle-owner.franchises.show', $franchise) }}" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i> View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">No franchise records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $franchises->links() }}
</div>
@endsection
