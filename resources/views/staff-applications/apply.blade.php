@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">STAFF APPLICATION</div>
        <h1>Apply as Staff</h1>
        <p class="muted">Submit your details for administrator review.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h3>Staff application</h3>
                    <p class="muted">Provide the information below so an administrator can review your application.</p>
                </div>
            </div>
            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('staff-application.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="full_name">Full name</label>
                        <input id="full_name" name="full_name" class="form-control" value="{{ old('full_name', auth()->user()->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_number">Contact number</label>
                        <input id="contact_number" name="contact_number" class="form-control" value="{{ old('contact_number', auth()->user()->mobile_number) }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3" required>{{ old('address', auth()->user()->address) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="date_of_birth">Date of birth</label>
                        <input id="date_of_birth" name="date_of_birth" type="date" class="form-control" value="{{ old('date_of_birth') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="preferred_position">Preferred staff position</label>
                        <input id="preferred_position" name="preferred_position" class="form-control" value="{{ old('preferred_position') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="department">Department / assignment</label>
                        <input id="department" name="department" class="form-control" value="{{ old('department') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="skills">Relevant skills</label>
                        <textarea id="skills" name="skills" class="form-control" rows="3">{{ old('skills') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="experience">Previous experience</label>
                        <textarea id="experience" name="experience" class="form-control" rows="3">{{ old('experience') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="reason">Reason for applying</label>
                        <textarea id="reason" name="reason" class="form-control" rows="3" required>{{ old('reason') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="additional_information">Additional information</label>
                        <textarea id="additional_information" name="additional_information" class="form-control" rows="3">{{ old('additional_information') }}</textarea>
                    </div>
                </div>
                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Submit application</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
