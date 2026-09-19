@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">PERMITS</div>
        <h1>My permits</h1>
        <p class="muted">Official permits associated with your registered vehicles.</p>
    </div>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Permit number</th>
                    <th>Vehicle</th>
                    <th>Issue date</th>
                    <th>Expiry date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permits as $permit)
                    <tr>
                        <td>{{ $permit->permit_number }}</td>
                        <td>{{ $permit->vehicle?->plate_number ?? '—' }}</td>
                        <td>{{ $permit->issue_date->format('d M Y') }}</td>
                        <td>{{ $permit->expiry_date->format('d M Y') }}</td>
                        <td>{{ $permit->status }}</td>
                        <td><a href="{{ route('vehicle-owner.permits.show', $permit) }}" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i> View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">No permits found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $permits->links() }}
</div>
@endsection
