@extends('layouts.app')

@section('content')
@include('partials.leaflet')
<div class="page-head">
    <div>
        <div class="eyebrow">LOCATION</div>
        <h1>Live Location</h1>
        <p class="muted">Share your current GPS position for your assigned vehicle.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="panel">
            <h3 class="mb-3">Assigned vehicle</h3>
            <dl class="row small">
                <dt class="col-5 text-muted">Vehicle</dt>
                <dd class="col-7">{{ $vehicle?->plate_number ?? 'No vehicle assigned' }}</dd>
                <dt class="col-5 text-muted">Location Sharing</dt>
                <dd class="col-7"><span class="badge {{ $sharingEnabled ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $sharingEnabled ? 'ON' : 'OFF' }}</span></dd>
                <dt class="col-5 text-muted">Latitude</dt>
                <dd class="col-7" id="currentLatitude">{{ $latestLocation?->latitude ?? '—' }}</dd>
                <dt class="col-5 text-muted">Longitude</dt>
                <dd class="col-7" id="currentLongitude">{{ $latestLocation?->longitude ?? '—' }}</dd>
                <dt class="col-5 text-muted">Accuracy</dt>
                <dd class="col-7" id="currentAccuracy">{{ $latestLocation?->accuracy ? round($latestLocation->accuracy).' meters' : '—' }}</dd>
                <dt class="col-5 text-muted">Last updated</dt>
                <dd class="col-7" id="currentUpdated">{{ $latestLocation?->recorded_at ? $latestLocation->recorded_at->diffForHumans() : '—' }}</dd>
                <dt class="col-5 text-muted">GPS status</dt>
                <dd class="col-7">{{ $latestLocation?->status ?? 'offline' }}</dd>
            </dl>

            @if($vehicle)
                <div class="d-flex gap-2 flex-wrap mt-3">
                    <button class="btn btn-primary" id="startLocationSharingBtn">Start Location Sharing</button>
                    <button class="btn btn-outline-secondary" id="stopLocationSharingBtn">Stop Location Sharing</button>
                </div>
                <div class="alert alert-light mt-3 mb-0 d-none" id="locationStatusMessage"></div>
            @else
                <div class="alert alert-warning mt-3 mb-0">No assigned vehicle was found for this operator profile.</div>
            @endif
        </div>
    </div>

    <div class="col-lg-7">
        <div class="panel">
            <h3 class="mb-3">Current map</h3>
            <div id="operatorMapMessage" class="alert alert-secondary d-none"></div>
            <div class="alert alert-warning d-none leaflet-load-error mb-3">Map library could not be loaded. Check your internet connection and reload the page.</div>
            <div id="operatorLocationMap" class="leaflet-map" style="min-height: 430px; height: 420px;"></div>
        </div>
    </div>
</div>

<script>
    const operatorVehicle = @json($vehicle);
    const latestLocation = @json($latestLocation);
    const startUrl = @json(route('operator.live-location.store'));
    const stopUrl = @json(route('operator.live-location.stop'));
    const mapSettings = window.mapDefaults;

    let operatorMap = null;
    let operatorMarker = null;
    let locationWatchId = null;
    let controlsBound = false;

    function showOperatorMapMessage(type, text) {
        const node = document.getElementById('operatorMapMessage');
        node.className = 'alert alert-' + type;
        node.textContent = text;
    }

    function setStatus(node, type, text) {
        node.classList.remove('d-none', 'alert-info', 'alert-success', 'alert-danger');
        node.classList.add('alert-' + type);
        node.textContent = text;
    }

    function hasSavedLocation() {
        return latestLocation
            && Number.isFinite(parseFloat(latestLocation.latitude))
            && Number.isFinite(parseFloat(latestLocation.longitude));
    }

    function initOperatorMap() {
        if (operatorMap) return;

        if (!window.L) {
            document.querySelectorAll('.leaflet-load-error').forEach(function (node) { node.classList.remove('d-none'); });
            showOperatorMapMessage('danger', 'Map library could not be loaded. Check your internet connection.');
            return;
        }

        const center = hasSavedLocation()
            ? [parseFloat(latestLocation.latitude), parseFloat(latestLocation.longitude)]
            : mapSettings.center;

        operatorMap = L.map('operatorLocationMap', { zoomControl: true }).setView(center, mapSettings.zoom);

        L.tileLayer(mapSettings.tileUrl, { maxZoom: 19, attribution: mapSettings.attribution })
            .on('tileerror', function () {
                showOperatorMapMessage('warning', 'Map tiles could not be loaded. Check your internet connection.');
            })
            .addTo(operatorMap);

        if (hasSavedLocation()) {
            operatorMarker = L.marker(center, { title: operatorVehicle?.plate_number || 'Vehicle' }).addTo(operatorMap);
        }

        window.addEventListener('resize', function () {
            if (operatorMap) operatorMap.invalidateSize();
        });
    }

    function updateOperatorMarker(latitude, longitude) {
        if (!operatorMap) return;
        const point = [latitude, longitude];

        if (operatorMarker) {
            operatorMarker.setLatLng(point);
        } else {
            operatorMarker = L.marker(point, { title: operatorVehicle?.plate_number || 'Vehicle' }).addTo(operatorMap);
        }

        operatorMap.setView(point, mapSettings.locationZoom);
    }

    function startLocationSharing(statusNode) {
        if (!navigator.geolocation) {
            setStatus(statusNode, 'danger', 'This browser does not support geolocation.');
            return;
        }

        if (!operatorVehicle) {
            setStatus(statusNode, 'danger', 'No vehicle is assigned to this operator profile.');
            return;
        }

        setStatus(statusNode, 'info', 'Requesting GPS permission...');

        locationWatchId = navigator.geolocation.watchPosition(function (position) {
            fetch(startUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    vehicle_id: operatorVehicle.id,
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    speed: position.coords.speed || 0,
                    heading: position.coords.heading || 0,
                })
            })
            .then(function (response) {
                if (!response.ok) throw new Error('Location request failed');
                return response.json();
            })
            .then(function () {
                setStatus(statusNode, 'success', 'Location sharing ON. Accuracy: approximately ' + Math.round(position.coords.accuracy) + ' meters.');
                document.getElementById('currentLatitude').textContent = position.coords.latitude.toFixed(6);
                document.getElementById('currentLongitude').textContent = position.coords.longitude.toFixed(6);
                document.getElementById('currentAccuracy').textContent = Math.round(position.coords.accuracy) + ' meters';
                document.getElementById('currentUpdated').textContent = new Date().toLocaleTimeString();
                updateOperatorMarker(position.coords.latitude, position.coords.longitude);
            })
            .catch(function () {
                setStatus(statusNode, 'danger', 'Unable to start location sharing.');
            });
        }, function (error) {
            if (error.code === error.PERMISSION_DENIED) {
                setStatus(statusNode, 'danger', 'Location permission was denied. Enable location access to share GPS data.');
            } else if (error.code === error.POSITION_UNAVAILABLE) {
                setStatus(statusNode, 'danger', 'Your location could not be determined.');
            } else if (error.code === error.TIMEOUT) {
                setStatus(statusNode, 'danger', 'Location request timed out. Please try again.');
            } else {
                setStatus(statusNode, 'danger', 'Unable to obtain your current location.');
            }
        }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 5000 });
    }

    function stopLocationSharing(statusNode) {
        if (locationWatchId !== null) {
            navigator.geolocation.clearWatch(locationWatchId);
            locationWatchId = null;
        }

        fetch(stopUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ vehicle_id: operatorVehicle ? operatorVehicle.id : null })
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Stop request failed');
            return response.json();
        })
        .then(function (payload) {
            setStatus(statusNode, 'success', payload.message || 'Location sharing stopped.');
            window.location.reload();
        })
        .catch(function () {
            setStatus(statusNode, 'danger', 'Unable to stop location sharing.');
        });
    }

    function bindOperatorControls() {
        if (controlsBound) return;
        controlsBound = true;

        const statusNode = document.getElementById('locationStatusMessage');
        if (!statusNode) return;

        document.getElementById('startLocationSharingBtn')?.addEventListener('click', function () {
            startLocationSharing(statusNode);
        });

        document.getElementById('stopLocationSharingBtn')?.addEventListener('click', function () {
            stopLocationSharing(statusNode);
        });
    }

    function bootOperatorMap() {
        initOperatorMap();
        bindOperatorControls();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootOperatorMap);
    } else {
        bootOperatorMap();
    }
</script>
@endsection