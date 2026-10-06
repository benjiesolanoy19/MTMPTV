<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create account | Transit Desk</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="{{ asset('css/app.css') }}" rel="stylesheet">
<link href="{{ asset('css/auth.css') }}" rel="stylesheet">
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
</head>
<body class="login-page register-screen auth-signup" style="--school-image: url('{{ asset('images/school-building.jfif') }}')">
@include('auth.partials.animated-frame')
<div class="login-art">
<div class="seal">
<i data-lucide="building-2" aria-hidden="true">
</i>
</div>
<div class="eyebrow">MUNICIPAL OPERATIONS</div>
<h1>Transit Desk</h1>
<div class="brand-subtitle">Municipal Operations</div>
<p>Municipal Tricycle and Public Transport Permitting, Franchise Management, and Violation Tracking.</p>
<div class="art-footer">OPERATIONS PORTAL <span>•</span> v1.0</div>
</div>
<div class="login-panel register-panel">
<div class="register-box">
<div class="mobile-brand"><span class="mobile-seal"><i data-lucide="signpost-big" aria-hidden="true"></i></span><span><strong>Transit Desk</strong><small>Municipal Operations</small></span></div>
<div class="eyebrow">ACCOUNT REGISTRATION</div>
<h2>Create your account.</h2>
<p class="muted mb-4">Use your details to access the municipal transport portal.</p>@if($errors->any())<div class="alert alert-danger py-2">
<ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>@endif<form method="POST" action="{{ route('register.store') }}">@csrf<div class="row g-3">
<div class="col-12">
<label class="form-label" for="name">Full name</label>
<input id="name" name="name" class="form-control" value="{{ old('name') }}" required autofocus>
</div>
<div class="col-md-6">
<label class="form-label" for="username">Username</label>
<input id="username" name="username" class="form-control" value="{{ old('username') }}" autocomplete="username" required>
<div class="form-text">Letters, numbers, dots, hyphens, and underscores.</div>
</div>
<div class="col-md-6">
<label class="form-label" for="role">Account type</label>
<select id="role" name="role" class="form-select" required>
<option value="">Select account type</option>
<option value="viewer" @selected(old('role')==='viewer')>Report user</option>
<option value="operator" @selected(old('role')==='operator')>Operator / Driver</option>
<option value="vehicle_owner" @selected(old('role')==='vehicle_owner')>Vehicle owner</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label" for="email">Email address</label>
<input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}" autocomplete="email" required>
</div>
<div class="col-md-6">
<label class="form-label" for="mobile_number">Mobile number</label>
<input id="mobile_number" name="mobile_number" class="form-control" value="{{ old('mobile_number') }}" placeholder="09171234567" autocomplete="tel" required>
</div>
<div class="col-12">
<label class="form-label" for="address">Address</label>
<textarea id="address" name="address" class="form-control" rows="2" required>{{ old('address') }}</textarea>
</div>
<div class="col-md-6">
<label class="form-label" for="password">Password</label>
<div class="input-group">
<input id="password" name="password" type="password" class="form-control" autocomplete="new-password" required>
<button type="button" class="btn btn-light border" onclick="togglePassword('password', this)" title="Show password" aria-label="Show password">
<i data-lucide="eye" aria-hidden="true">
</i>
</button>
</div>
</div>
<div class="col-md-6">
<label class="form-label" for="password_confirmation">Confirm password</label>
<div class="input-group">
<input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required>
<button type="button" class="btn btn-light border" onclick="togglePassword('password_confirmation', this)" title="Show password" aria-label="Show password">
<i data-lucide="eye" aria-hidden="true">
</i>
</button>
</div>
</div>
</div>
<div class="form-check mt-4">
<input class="form-check-input" type="checkbox" name="terms" value="1" id="terms" @checked(old('terms')) required>
<label class="form-check-label small" for="terms">I agree to the <a href="#terms">Terms and Conditions</a>.</label>
</div>
<div class="form-check mt-2">
<input class="form-check-input" type="checkbox" name="privacy" value="1" id="privacy" @checked(old('privacy')) required>
<label class="form-check-label small" for="privacy">I acknowledge the <a href="#privacy">Privacy Policy</a>.</label>
</div>
<button class="btn btn-primary w-100 py-2 mt-4">Create account <i data-lucide="arrow-right" class="ms-2" aria-hidden="true">
</i>
</button>
</form>
<p class="login-note">Already have an account? <a href="{{ route('login') }}">Login</a>
</p>
</div>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>window.lucide?.createIcons({attrs:{'stroke-width':1.8}}));function togglePassword(id,button){const input=document.getElementById(id);const visible=input.type==='text';input.type=visible?'password':'text';const icon=document.createElement('i');icon.dataset.lucide=visible?'eye':'eye-off';icon.setAttribute('aria-hidden','true');button.replaceChildren(icon);window.lucide?.createIcons({attrs:{'stroke-width':1.8}});button.title=visible?'Show password':'Hide password';button.setAttribute('aria-label',visible?'Show password':'Hide password');}</script>
</body>
</html>
