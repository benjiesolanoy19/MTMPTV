@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<div class="page-head">
    <div>
        <a href="{{ route('vehicle-owner.vehicles.show', $vehicle) }}" class="text-link"><i class="bi bi-arrow-left"></i> Vehicle details</a>
        <h1>{{ $vehicle->plate_number }}</h1>
        <p class="muted">Latest vehicle location and status.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="panel">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <h3 class="mb-0">Location status</h3>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="ownerMyLocationBtn"><i class="bi bi-crosshair me-1"></i> Enable My Location</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="ownerStopLocationBtn" disabled><i class="bi bi-stop-circle me-1"></i> Stop</button>
                </div>
            </div>
            <dl class="row small mb-0">
                <dt class="col-5 text-muted">Status</dt>
                <dd class="col-7"><span class="badge {{ $status === 'online' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($status) }}</span></dd>
                <dt class="col-5 text-muted">Latitude</dt>
                <dd class="col-7" id="currentLatitude">{{ $latestLocation?->latitude ?? 'No GPS location available yet.' }}</dd>
                <dt class="col-5 text-muted">Longitude</dt>
                <dd class="col-7" id="currentLongitude">{{ $latestLocation?->longitude ?? 'No GPS location available yet.' }}</dd>
                <dt class="col-5 text-muted">Accuracy</dt>
                <dd class="col-7" id="currentAccuracy">{{ $latestLocation?->accuracy !== null ? $latestLocation->accuracy.' meters' : '—' }}</dd>
                <dt class="col-5 text-muted">Last updated</dt>
                <dd class="col-7" id="currentUpdated">{{ $latestLocation?->recorded_at ? $latestLocation->recorded_at->diffForHumans() : 'Never' }}</dd>
                <dt class="col-5 text-muted">Tracking status</dt>
                <dd class="col-7" id="trackingStatus">Not started</dd>
                <dt class="col-5 text-muted">Operator</dt>
                <dd class="col-7">{{ $vehicle->operator?->full_name ?? '—' }}</dd>
            </dl>
            <div id="ownerGpsMessage" class="alert alert-light mt-3 mb-0">Click Enable My Location to request browser permission.</div>
            <div class="small text-muted mt-2" id="gpsDiagnostic">Browser permission: Not requested</div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <h3 class="mb-3">Map</h3>
            <div id="ownerMapMessage" class="alert alert-info">Loading OpenStreetMap...</div>
            <div id="ownerVehicleMap" style="height: 500px; width: 100%; border-radius: 14px; overflow: hidden; background: #e9ecef;"></div>
        </div>
    </div>
</div>

<script>
    const ownerVehicleLocation = @json($latestLocation);
    const ownerVehicle = @json($vehicle);
    const locationDataUrl = @json(route('vehicle-owner.vehicles.location.data', $vehicle));
    const locationUpdateUrl = @json(route('vehicle-owner.vehicles.location.update', $vehicle));
    let ownerMap;
    let vehicleMarker;
    let currentLocationMarker;
    let trackingId = null;
    let lastSentAt = 0;
    let lastSentPoint = null;
    const gpsOptions = { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 };

    function showMapMessage(type, text) {
        const message = document.getElementById('ownerMapMessage');
        message.className = 'alert alert-' + type;
        message.textContent = text;
    }

    function validPoint(location) {
        return location && Number.isFinite(Number(location.latitude)) && Number.isFinite(Number(location.longitude));
    }

    function renderVehicleLocation(location, centerMap) {
        if (!validPoint(location)) {
            showMapMessage('info', 'No GPS location available yet.');
            return;
        }
        const point = [Number(location.latitude), Number(location.longitude)];
        const accuracy = location.accuracy !== null && location.accuracy !== undefined ? Math.round(location.accuracy) + ' meters' : 'Unavailable';
        const updated = location.recorded_at ? new Date(location.recorded_at).toLocaleString() : 'Unknown';
        const popup = '<strong>' + ownerVehicle.plate_number + '</strong><br>' + ownerVehicle.vehicle_type + '<br>Status: ' + location.status + '<br>Latitude: ' + point[0].toFixed(6) + '<br>Longitude: ' + point[1].toFixed(6) + '<br>Accuracy: ' + accuracy + '<br>Last updated: ' + updated;
        if (!vehicleMarker) {
            vehicleMarker = L.marker(point).addTo(ownerMap);
        } else {
            vehicleMarker.setLatLng(point);
        }
        vehicleMarker.bindPopup(popup);
        if (centerMap) ownerMap.setView(point, 16);
        showMapMessage('success', 'Vehicle location loaded from Transit Desk.');
    }

    function initOwnerMap() {
        ownerMap = L.map('ownerVehicleMap', { zoomControl: true }).setView([0, 0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(ownerMap);
        renderVehicleLocation(ownerVehicleLocation, true);
        setInterval(refreshVehicleLocation, 15000);
    }

    function refreshVehicleLocation() {
        fetch(locationDataUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => { if (!response.ok) throw new Error('Unable to refresh location.'); return response.json(); })
            .then(payload => renderVehicleLocation(payload.location, false))
            .catch(() => showMapMessage('danger', 'Unable to refresh the vehicle location.'));
    }

    function distanceMeters(first, second) {
        if (!first || !second) return Number.POSITIVE_INFINITY;
        const latitudeDelta = (second[0] - first[0]) * 111320;
        const longitudeDelta = (second[1] - first[1]) * 111320 * Math.cos(first[0] * Math.PI / 180);
        return Math.sqrt(latitudeDelta * latitudeDelta + longitudeDelta * longitudeDelta);
    }

    function updateGpsDisplay(position) {
        const latitude = position.coords.latitude;
        const longitude = position.coords.longitude;
        const accuracy = position.coords.accuracy;
        const point = [latitude, longitude];
        document.getElementById('currentLatitude').textContent = latitude.toFixed(6);
        document.getElementById('currentLongitude').textContent = longitude.toFixed(6);
        document.getElementById('currentAccuracy').textContent = Math.round(accuracy) + ' meters';
        document.getElementById('currentUpdated').textContent = new Date(position.timestamp).toLocaleTimeString();
        document.getElementById('trackingStatus').textContent = 'Location tracking active';
        document.getElementById('gpsDiagnostic').textContent = 'Browser permission: Granted | Latitude: ' + latitude.toFixed(6) + ' | Longitude: ' + longitude.toFixed(6) + ' | Accuracy: ' + Math.round(accuracy) + ' meters | Timestamp: ' + new Date(position.timestamp).toLocaleString();
        const message = document.getElementById('ownerGpsMessage');
        message.className = 'alert ' + (accuracy > 100 ? 'alert-warning' : 'alert-success') + ' mt-3 mb-0';
        message.textContent = accuracy > 100 ? 'Location accuracy is low: ' + Math.round(accuracy) + ' meters.' : 'GPS/location accuracy: ' + Math.round(accuracy) + ' meters.';
        if (ownerMap) {
            ownerMap.setView(point, Math.max(ownerMap.getZoom(), 16));
            currentLocationMarker?.setLatLng(point);
            if (!currentLocationMarker) currentLocationMarker = L.circleMarker(point, { radius: 9, color: '#0d6efd', fillColor: '#0d6efd', fillOpacity: 0.9 }).addTo(ownerMap);
            currentLocationMarker.bindPopup('My current location<br>Accuracy: ' + Math.round(accuracy) + ' meters').openPopup();
        }
        const now = Date.now();
        if (now - lastSentAt < 5000 && distanceMeters(lastSentPoint, point) < 20) return;
        lastSentAt = now;
        lastSentPoint = point;
        fetch(locationUpdateUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ latitude, longitude, accuracy }) })
            .then(response => { if (!response.ok) throw new Error('Unable to save location.'); return response.json(); })
            .then(() => { document.getElementById('gpsDiagnostic').textContent += ' | Last server update: ' + new Date().toLocaleTimeString(); })
            .catch(() => { message.className = 'alert alert-danger mt-3 mb-0'; message.textContent = 'GPS was found, but the location could not be saved.'; });
    }

    function handleGpsError(error) {
        const message = document.getElementById('ownerGpsMessage');
        const status = document.getElementById('trackingStatus');
        const diagnostic = document.getElementById('gpsDiagnostic');
        if (error.code === 1) { message.textContent = 'Location permission was denied. Please allow location access in your browser.'; diagnostic.textContent = 'Browser permission: Denied'; }
        else if (error.code === 2) { message.textContent = 'Location information is unavailable.'; diagnostic.textContent = 'Browser permission: Granted, location unavailable'; }
        else if (error.code === 3) { message.textContent = 'Location request timed out. Trying again...'; diagnostic.textContent = 'Browser permission: Granted, request timed out'; }
        else message.textContent = 'Unable to obtain your current location.';
        message.className = 'alert alert-danger mt-3 mb-0';
        status.textContent = 'GPS error';
    }

    document.getElementById('ownerMyLocationBtn').addEventListener('click', function () {
        const message = document.getElementById('ownerGpsMessage');
        if (!navigator.geolocation) {
            message.className = 'alert alert-danger mt-3 mb-0';
            message.textContent = 'Unable to obtain your current location. This browser does not support GPS location.';
            return;
        }

        message.className = 'alert alert-info mt-3 mb-0';
        message.textContent = 'Getting your current location...';
        document.getElementById('trackingStatus').textContent = 'Locating';
        navigator.geolocation.getCurrentPosition(function (position) {
            updateGpsDisplay(position);
            if (trackingId === null) trackingId = navigator.geolocation.watchPosition(updateGpsDisplay, handleGpsError, gpsOptions);
            document.getElementById('ownerMyLocationBtn').disabled = true;
            document.getElementById('ownerStopLocationBtn').disabled = false;
        }, handleGpsError, gpsOptions);
    });

    document.getElementById('ownerStopLocationBtn').addEventListener('click', function () {
        if (trackingId !== null) navigator.geolocation.clearWatch(trackingId);
        trackingId = null;
        document.getElementById('ownerMyLocationBtn').disabled = false;
        document.getElementById('ownerStopLocationBtn').disabled = true;
        document.getElementById('trackingStatus').textContent = 'Tracking stopped';
        document.getElementById('ownerGpsMessage').className = 'alert alert-light mt-3 mb-0';
        document.getElementById('ownerGpsMessage').textContent = 'Location tracking stopped.';
    });

    const leafletScript = document.createElement('script');
    leafletScript.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    leafletScript.onload = initOwnerMap;
    leafletScript.onerror = () => showMapMessage('danger', 'Leaflet could not be loaded. Check your internet connection.');
    document.head.appendChild(leafletScript);
</script>
@endsection
