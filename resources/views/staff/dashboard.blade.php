@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">STAFF DASHBOARD</div>
        <h1>Welcome, {{ explode(' ', $user->name)[0] }}.</h1>
        <p class="muted">Staff operational overview and tasks.</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card"><span class="stat-icon blue"><i data-lucide="layout-dashboard"></i></span><div><small>Tasks</small><strong>0</strong><span class="trend">Assigned</span></div></div>
    <div class="stat-card"><span class="stat-icon teal"><i data-lucide="clipboard-check"></i></span><div><small>Records</small><strong>0</strong><span class="trend">Managed</span></div></div>
    <div class="stat-card"><span class="stat-icon amber"><i data-lucide="bell"></i></span><div><small>Alerts</small><strong>0</strong><span class="trend">Notifications</span></div></div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-head"><div><h3>Assigned tasks</h3></div></div>
            <div class="empty"><i data-lucide="inbox"></i><span>No assigned tasks yet.</span></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-head"><div><h3>My profile</h3></div></div>
            <div class="alert-row"><div><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></div><span class="badge badge-success">Staff</span></div>
        </div>
    </div>
</div>
@endsection
