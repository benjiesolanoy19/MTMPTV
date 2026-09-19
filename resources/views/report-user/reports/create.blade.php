@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">REPORTING</div>
        <h1>Create report</h1>
        <p class="muted">Choose the incident location directly on the map.</p>
    </div>
</div>

<form method="POST" action="{{ route('reports.store') }}" class="row g-4" id="reportForm">
    @csrf
    <div class="col-lg-5">
        <div class="panel">
            <label class="form-label">Report type</label>
            <input name="report_type" class="form-control mb-3" value="{{ old('report_type') }}" required>
            <label class="form-label">Date submitted</label>
            <input type="date" name="date_submitted" class="form-control mb-3" value="{{ old('date_submitted', now()->toDateString()) }}" required>
            <label class="form-label">Location name</label>
            <input name="location" class="form-control mb-3" value="{{ old('location') }}" placeholder="Optional landmark or street">
            <input type="hidden" name="latitude" id="reportLatitude" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" id="reportLongitude" value="{{ old('longitude') }}">
            <div class="small text-muted mb-3" id="reportCoordinates">No map location selected.</div>
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="6" required>{{ old('description') }}</textarea>
            <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-send me-1"></i> Submit report</button>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <div><h3 class="mb-1">Select location</h3><p class="muted mb-0">Click the map or use your current GPS position.</p></div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="reportMyLocation"><i class="bi bi-crosshair me-1"></i> My location</button>
            </div>
            <div id="reportMapMessage" class="alert alert-info">Loading map...</div>
            <div id="reportMap" style="height: 480px; width: 100%; border-radius: 14px; overflow: hidden; background: #e9ecef;"></div>
        </div>
    </div>
</form>

<script>
    const reportMapApiKey = @json($apiKey);
    let reportMap;
    let reportMarker;
    const reportMapMessage = document.getElementById('reportMapMessage');

    function setReportLocation(latitude, longitude) {
        document.getElementById('reportLatitude').value = latitude.toFixed(8);
        document.getElementById('reportLongitude').value = longitude.toFixed(8);
        document.getElementById('reportCoordinates').textContent = 'Latitude: ' + latitude.toFixed(6) + ' | Longitude: ' + longitude.toFixed(6);
        reportMarker?.setMap(null);
        reportMarker = new google.maps.Marker({ map: reportMap, position: { lat: latitude, lng: longitude }, title: 'Report location', draggable: true });
        reportMarker.addListener('dragend', event => setReportLocation(event.latLng.lat(), event.latLng.lng()));
    }

    function initReportMap() {
        reportMapMessage.className = 'alert alert-secondary d-none';
        reportMap = new google.maps.Map(document.getElementById('reportMap'), { center: { lat: 0, lng: 0 }, zoom: 2, mapTypeControl: true, streetViewControl: false, fullscreenControl: true });
        reportMap.addListener('click', event => setReportLocation(event.latLng.lat(), event.latLng.lng()));
    }

    document.getElementById('reportMyLocation').addEventListener('click', function () {
        if (!navigator.geolocation) { reportMapMessage.className = 'alert alert-danger'; reportMapMessage.textContent = 'This browser does not support geolocation.'; return; }
        reportMapMessage.className = 'alert alert-info'; reportMapMessage.textContent = 'Getting your current location...';
        navigator.geolocation.getCurrentPosition(position => { const point = { lat: position.coords.latitude, lng: position.coords.longitude }; reportMap.setCenter(point); reportMap.setZoom(16); setReportLocation(point.lat, point.lng); reportMapMessage.className = 'alert alert-success'; reportMapMessage.textContent = 'Current location selected. Accuracy: ' + Math.round(position.coords.accuracy) + ' meters.'; }, () => { reportMapMessage.className = 'alert alert-danger'; reportMapMessage.textContent = 'Unable to obtain your location. Check browser permission and HTTPS requirements.'; }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    });

    if (!reportMapApiKey) { reportMapMessage.className = 'alert alert-warning'; reportMapMessage.textContent = 'Google Maps API key is not configured.'; }
    else { window.initReportMap = initReportMap; const script = document.createElement('script'); script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(reportMapApiKey) + '&callback=initReportMap&v=weekly'; script.async = true; script.defer = true; script.onerror = () => { reportMapMessage.className = 'alert alert-danger'; reportMapMessage.textContent = 'Google Maps could not be loaded. Check the API key, billing, and API restrictions.'; }; document.head.appendChild(script); }
</script>
@endsection