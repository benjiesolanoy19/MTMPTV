<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Too many requests | Transit Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/auth.css') }}" rel="stylesheet">
</head>
<body class="login-page">
<main class="login-art">
    <div class="seal"><i class="bi bi-signpost-2-fill"></i></div>
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
            <a class="btn btn-primary" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-left me-1"></i> Back to sign in</a>
            <a class="btn btn-outline-secondary" href="{{ route('register') }}"><i class="bi bi-person-plus me-1"></i> Registration page</a>
        </div>
        <p class="login-note"><i class="bi bi-shield-check me-1"></i> This limit protects the portal against automated abuse.</p>
    </div>
</section>
</body>
</html>