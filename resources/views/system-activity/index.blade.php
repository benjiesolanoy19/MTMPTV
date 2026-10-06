@extends('layouts.app')
@section('content')
<div class="page-head"><div><div class="eyebrow">SYSTEM MANAGEMENT</div><h1>System activity</h1><p class="muted">Audit history recorded by the application.</p></div></div>
<div class="panel">
    <div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>Action</th><th>Actor</th><th>Module</th><th>Recorded</th><th>IP address</th></tr></thead>
        <tbody>
        @forelse($activity as $entry)
            <tr>
                <td><strong>{{ $entry->description }}</strong></td>
                <td>{{ $entry->user?->name ?? 'System' }}</td>
                <td>{{ $entry->module }}</td>
                <td>{{ $entry->created_at->format('d M Y, g:i A') }}</td>
                <td>{{ $entry->ip_address ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center py-5 text-muted">No system activity recorded yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $activity->links() }}
</div>
@endsection
