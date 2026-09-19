@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">RENEWALS</div>
        <h1>Renewals</h1>
        <p class="muted">Upcoming renewals for your vehicles and permits.</p>
    </div>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Record</th>
                    <th>Vehicle</th>
                    <th>Expiry date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($renewals as $renewal)
                    <tr>
                        <td>{{ $renewal->renewal_number }}</td>
                        <td>{{ $renewal->vehicle?->plate_number ?? '—' }}</td>
                        <td>{{ $renewal->new_expiry_date->format('d M Y') }}</td>
                        <td>{{ $renewal->status }}</td>
                        <td><a href="#" class="btn btn-sm btn-light border disabled">Request Renewal</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-5 text-muted">No renewals found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $renewals->links() }}
</div>
@endsection
