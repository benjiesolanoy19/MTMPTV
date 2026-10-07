@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">STAFF ONBOARDING</div>
        <h1>Complete your Staff Profile</h1>
        <p class="muted">Your staff application has been approved. Complete onboarding to access staff features.</p>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <div><h3>Staff onboarding</h3></div>
    </div>
    <form method="POST" action="{{ route('staff.onboarding.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="position">Staff position</label>
                <input id="position" name="position" class="form-control" value="{{ old('position', $profile->position ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="department">Department</label>
                <input id="department" name="department" class="form-control" value="{{ old('department', $profile->department ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="staff_id">Staff ID</label>
                <input id="staff_id" name="staff_id" class="form-control" value="{{ old('staff_id', $profile->staff_id ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="contact_number">Contact number</label>
                <input id="contact_number" name="contact_number" class="form-control" value="{{ old('contact_number', $profile->contact_number ?? auth()->user()->mobile_number) }}" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="address">Address</label>
                <textarea id="address" name="address" class="form-control" rows="3" required>{{ old('address', $profile->address ?? auth()->user()->address) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="date_of_birth">Date of birth</label>
                <input id="date_of_birth" name="date_of_birth" type="date" class="form-control" value="{{ old('date_of_birth', optional($profile->date_of_birth)->format('Y-m-d')) }}">
            </div>
            <div class="col-12">
                <label class="form-label" for="skills">Skills</label>
                <textarea id="skills" name="skills" class="form-control" rows="3">{{ old('skills', $profile->skills ?? '') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="experience">Work experience</label>
                <textarea id="experience" name="experience" class="form-control" rows="3">{{ old('experience', $profile->experience ?? '') }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="emergency_contact">Emergency contact</label>
                <input id="emergency_contact" name="emergency_contact" class="form-control" value="{{ old('emergency_contact', $profile->emergency_contact ?? '') }}">
            </div>
            <div class="col-12">
                <label class="form-label" for="additional_information">Additional notes</label>
                <textarea id="additional_information" name="additional_information" class="form-control" rows="3">{{ old('additional_information', $profile->additional_information ?? '') }}</textarea>
            </div>
        </div>
        <div class="mt-4 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Complete Staff Profile</button>
        </div>
    </form>
</div>
@endsection
