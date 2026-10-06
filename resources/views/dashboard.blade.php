@extends('layouts.app')
@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">{{ strtoupper(now()->format('l, d M Y')) }}</div>
        <h1>{{ now()->hour < 12 ? 'Good morning' : (now()->hour < 18 ? 'Good afternoon' : 'Good evening') }}, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
        <p class="muted">Here is today's transport operations overview.</p>
    </div>
    <div class="page-actions"><button class="btn btn-light border"><i data-lucide="download" class="me-1" aria-hidden="true"></i> Export snapshot</button></div>
</div>
<div class="stat-grid">
    <div class="stat-card"><span class="stat-icon blue"><i data-lucide="contact" aria-hidden="true"></i></span><div><small>Total operators</small><strong>{{ number_format($stats['operators']) }}</strong><span class="trend">Registered records</span></div></div>
    <div class="stat-card"><span class="stat-icon teal"><i data-lucide="truck" aria-hidden="true"></i></span><div><small>Active vehicles</small><strong>{{ number_format($stats['vehicles']) }}</strong><span class="trend">Fleet registry</span></div></div>
    <div class="stat-card"><span class="stat-icon amber"><i data-lucide="hourglass" aria-hidden="true"></i></span><div><small>Pending applications</small><strong>{{ number_format($stats['pending']) }}</strong><span class="trend warning">Needs review</span></div></div>
    <div class="stat-card"><span class="stat-icon coral"><i data-lucide="calendar-days" aria-hidden="true"></i></span><div><small>Expiring in 30 days</small><strong>{{ number_format($stats['expiring']) }}</strong><span class="trend warning">Attention required</span></div></div>
</div>
<div class="row g-4 mt-1">
    <div class="col-lg-7"><div class="panel"><div class="panel-head"><div><h3>Application pipeline</h3><p class="muted">Current application status distribution</p></div><a href="{{ route('applications.index') }}" class="text-link">View all <i data-lucide="arrow-up-right" aria-hidden="true"></i></a></div><div class="pipeline">@foreach(['Pending'=>'amber','Under Review'=>'blue','Approved'=>'teal','Rejected'=>'coral'] as $status=>$color)<div class="pipeline-row"><span class="dot {{ $color }}"></span><span>{{ $status }}</span><strong>{{ $applicationStatuses[$status] ?? 0 }}</strong><div class="bar"><i class="{{ $color }}" style="width:{{ min(100, (($applicationStatuses[$status] ?? 0) / max(1, $applicationStatuses->sum())) * 100) }}%"></i></div></div>@endforeach</div></div></div>
    <div class="col-lg-5"><div class="panel"><div class="panel-head"><div><h3>Expiration watch</h3><p class="muted">Franchises needing attention</p></div><i data-lucide="bell" class="text-warning" aria-hidden="true"></i></div>@forelse($alerts as $alert)<div class="alert-row"><div><strong>{{ $alert->franchise_number }}</strong><small>{{ $alert->operator->full_name }}</small></div><span class="badge badge-warning">{{ $alert->expiry_date->diffForHumans() }}</span></div>@empty<div class="empty"><i data-lucide="circle-check" aria-hidden="true"></i><span>No upcoming expirations</span></div>@endforelse</div></div>
</div>
<div class="row g-4 mt-1">
    <div class="col-md-4"><div class="mini-panel"><span class="stat-icon teal"><i data-lucide="award" aria-hidden="true"></i></span><div><small>Active franchises</small><strong>{{ number_format($stats['franchises']) }}</strong></div></div></div>
    <div class="col-md-4"><div class="mini-panel"><span class="stat-icon blue"><i data-lucide="clipboard-check" aria-hidden="true"></i></span><div><small>Active permits</small><strong>{{ number_format($stats['permits']) }}</strong></div></div></div>
    <div class="col-md-4"><div class="mini-panel"><span class="stat-icon coral"><i data-lucide="wallet" aria-hidden="true"></i></span><div><small>Unpaid penalties</small><strong>₱{{ number_format($stats['unpaid'], 2) }}</strong></div></div></div>
</div>
@endsection
