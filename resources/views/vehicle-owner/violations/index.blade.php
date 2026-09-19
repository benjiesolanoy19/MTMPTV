@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">COMPLIANCE</div>
        <h1>Violations</h1>
        <p class="muted">Violations associated with your vehicle ownership.</p>
    </div>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Violation ID</th>
                    <th>Vehicle</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Penalty</th>
                </tr>
            </thead>
            <tbody>
                @forelse($violations as $violation)
                    <tr>
                        <td>{{ $violation->violation_number }}</td>
                        <td>{{ $violation->vehicle?->plate_number ?? '—' }}</td>
                        <td>{{ $violation->violation_type }}</td>
                        <td>{{ $violation->violation_date->format('d M Y') }}</td>
                        <td>{{ $violation->status }}</td>
                        <td>₱{{ number_format($violation->penalty_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">No violations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $violations->links() }}
</div>
@endsection
