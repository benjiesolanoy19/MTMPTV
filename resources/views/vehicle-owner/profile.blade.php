@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">VEHICLE OWNER</div>
        <h1>My profile</h1>
        <p class="muted">Manage your account details and contact information.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel">
            <h3 class="mb-4">Personal information</h3>
            <form method="POST" action="{{ route('vehicle-owner.profile.update') }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Full name</label>
                        <input name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile number</label>
                        <input name="mobile_number" class="form-control" value="{{ old('mobile_number', $user->mobile_number) }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3" required>{{ old('address', $user->address) }}</textarea>
                    </div>
                </div>
                <button class="btn btn-primary mt-4">Save profile</button>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <h3 class="mb-3">Owner information</h3>
            <dl class="row small">
                <dt class="col-6 text-muted">Owner ID</dt>
                <dd class="col-6">{{ $owner->operator_code }}</dd>
                <dt class="col-6 text-muted">Registration date</dt>
                <dd class="col-6">{{ $owner->created_at->format('d M Y') }}</dd>
                <dt class="col-6 text-muted">Account status</dt>
                <dd class="col-6">{{ ucfirst($user->status) }}</dd>
                <dt class="col-6 text-muted">Username</dt>
                <dd class="col-6">{{ $user->username }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection
