@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">ACCOUNT</div>
        <h1>Notifications</h1>
        <p class="muted">Updates relevant to your vehicle ownership and applications.</p>
    </div>
</div>

<div class="panel">
    @forelse($notifications as $notification)
        <div class="border rounded p-3 mb-2">
            <div class="fw-semibold">{{ $notification->title }}</div>
            <div class="small text-muted">{{ $notification->message }}</div>
        </div>
    @empty
        <div class="empty"><span>No notifications found.</span></div>
    @endforelse
    {{ $notifications->links() }}
</div>
@endsection
