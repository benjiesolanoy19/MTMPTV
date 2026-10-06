<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Too many requests | Transit Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/auth.css') }}" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
</head>
<body class="login-page">
<main class="login-art">
    <div class="seal"><i data-lucide="signpost-big" class="" aria-hidden="true"></i></div>
    <div class="eyebrow">LOCAL GOVERNMENT UNIT</div>
    <h1>Transit Desk</h1>
    <p>Municipal tricycle and public transport permitting, franchise management, and violation tracking.</p>
    <div class="art-footer">OPERATIONS PORTAL <span>&bull;</span> v1.0</div>
</main>
<section class="login-panel" aria-label="Too many requests">
    <div class="login-box">
        <div class="eyebrow">REQUEST LIMIT</div>
        <h2>Too many requests.</h2>
        <p class="muted">
            You have sent too many requests in a short period, so this action was temporarily paused.
            No data was changed.
        </p>
        @if ($seconds > 0)
            <div class="alert alert-warning py-2">
                Try again in about {{ $seconds }} second{{ $seconds === 1 ? '' : 's' }}.
            </div>
        @endif
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-primary" href="{{ route('login') }}"><i data-lucide="log-in" class="me-1" aria-hidden="true"></i> Back to sign in</a>
            <a class="btn btn-outline-secondary" href="{{ route('register') }}"><i data-lucide="user-round-plus" class="me-1" aria-hidden="true"></i> Registration page</a>
        </div>
        <p class="login-note"><i data-lucide="shield-check" class="me-1" aria-hidden="true"></i> This limit protects the portal against automated abuse.</p>
    </div>
</section>
<script>document.addEventListener('DOMContentLoaded',()=>window.lucide?.createIcons({attrs:{'stroke-width':1.8}}));</script>
</body>
</html>