@extends('layouts.app')

@section('content')
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
            <div id="operatorMapMessage" class="alert alert-warning">Loading map...</div>
            <div id="operatorLocationMap" style="height: 420px; width: 100%; border-radius: 14px; background: #e9ecef;"></div>
        </div>
    </div>
</div>

<script>
    const operatorVehicle = @json($vehicle);
    const latestLocation = @json($latestLocation);
    const startUrl = @json(route('operator.live-location.store'));
    const stopUrl = @json(route('operator.live-location.stop'));
    const apiKey = @json(config('services.google_maps.api_key'));
    let locationWatchId = null;
    let operatorMap;
    let operatorMarker;

    function initOperatorMap() {
        const center = latestLocation && latestLocation.latitude && latestLocation.longitude
            ? { lat: parseFloat(latestLocation.latitude), lng: parseFloat(latestLocation.longitude) }
            : { lat: 14.5995, lng: 120.9842 };

        document.getElementById('operatorMapMessage').className = 'alert alert-secondary d-none';
        operatorMap = new google.maps.Map(document.getElementById('operatorLocationMap'), {
            center,
            zoom: 14,
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
        });

        if (latestLocation && latestLocation.latitude && latestLocation.longitude) {
            new google.maps.Marker({
                map: operatorMap,
                position: { lat: parseFloat(latestLocation.latitude), lng: parseFloat(latestLocation.longitude) },
                title: operatorVehicle?.plate_number || 'Vehicle'
            });
        }

        const statusNode = document.getElementById('locationStatusMessage');
        document.getElementById('startLocationSharingBtn')?.addEventListener('click', function () {
            if (!navigator.geolocation) {
                statusNode.classList.remove('d-none');
                statusNode.classList.add('alert-danger');
                statusNode.textContent = 'This browser does not support geolocation.';
                return;
            }

            statusNode.classList.remove('d-none');
            statusNode.classList.remove('alert-danger');
            statusNode.classList.add('alert-info');
            statusNode.textContent = 'Requesting GPS permission...';

            statusNode.textContent = 'Getting your location...';
            locationWatchId = navigator.geolocation.watchPosition(function (position) {
                statusNode.textContent = 'Sharing location...';
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
                .then(function (payload) {
                    statusNode.classList.remove('alert-info'); statusNode.classList.add('alert-success');
                    statusNode.textContent = 'Location sharing ON. Accuracy: ' + Math.round(position.coords.accuracy) + ' meters.';
                    document.getElementById('currentLatitude').textContent = position.coords.latitude.toFixed(6);
                    document.getElementById('currentLongitude').textContent = position.coords.longitude.toFixed(6);
                    document.getElementById('currentAccuracy').textContent = Math.round(position.coords.accuracy) + ' meters';
                    document.getElementById('currentUpdated').textContent = new Date().toLocaleTimeString();
                    if (operatorMap) {
                        const point = { lat: position.coords.latitude, lng: position.coords.longitude };
                        operatorMap.setCenter(point); operatorMap.setZoom(16);
                        operatorMarker?.setMap(null); operatorMarker = new google.maps.Marker({ map: operatorMap, position: point, title: operatorVehicle?.plate_number || 'Vehicle' });
                    }
                })
                .catch(function () {
                    statusNode.classList.remove('alert-info');
                    statusNode.classList.add('alert-danger');
                    statusNode.textContent = 'Unable to start location sharing.';
                });
            }, function () {
                statusNode.classList.remove('d-none');
                statusNode.classList.add('alert-danger');
                statusNode.textContent = 'Location permission was denied. Enable location access to share GPS data.';
            }, {
                enableHighAccuracy: true,
                timeout: 20000,
                maximumAge: 5000
            });
        });

        document.getElementById('stopLocationSharingBtn')?.addEventListener('click', function () {
            if (locationWatchId !== null) { navigator.geolocation.clearWatch(locationWatchId); locationWatchId = null; }
            fetch(stopUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ vehicle_id: operatorVehicle.id })
            })
            .then(function (response) {
                if (!response.ok) throw new Error('Stop request failed');
                return response.json();
            })
            .then(function (payload) {
                statusNode.classList.remove('alert-info');
                statusNode.classList.add('alert-success');
                statusNode.textContent = payload.message || 'Location sharing stopped.';
                window.location.reload();
            })
            .catch(function () {
                statusNode.classList.remove('alert-info');
                statusNode.classList.add('alert-danger');
                statusNode.textContent = 'Unable to stop location sharing.';
            });
        });
    }

    if (apiKey) {
        const script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=' + apiKey + '&callback=initOperatorMap&v=weekly';
        script.defer = true;
        document.head.appendChild(script);
    } else {
        document.getElementById('operatorMapMessage').textContent = 'Google Maps API key is not configured.';
    }

    window.initOperatorMap = initOperatorMap;
</script>
@endsection
