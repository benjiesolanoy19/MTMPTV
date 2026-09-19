@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">LIVE TRANSPORT MAP</div>
        <h1>Live Transport Map</h1>
        <p class="muted">Authorized active vehicles and their latest known location.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card h-100">
            <span class="stat-icon blue"><i class="bi bi-truck"></i></span>
            <div>
                <small>Active vehicles</small>
                <strong>{{ count($vehicles) }}</strong>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card h-100">
            <span class="stat-icon teal"><i class="bi bi-wifi"></i></span>
            <div>
                <small>Online</small>
                <strong>{{ $onlineCount }}</strong>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card h-100">
            <span class="stat-icon coral"><i class="bi bi-circle-fill"></i></span>
            <div>
                <small>Offline</small>
                <strong>{{ $offlineCount }}</strong>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <div>
            <h3>Map</h3>
            <p class="muted">Live vehicle markers and status updates.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <input id="vehicleSearch" class="form-control form-control-sm" placeholder="Search vehicle..." style="max-width: 180px;">
            <select id="vehicleFilter" class="form-select form-select-sm" style="max-width: 120px;"><option value="all">All</option><option value="online">Online</option><option value="offline">Offline</option></select>
            <button class="btn btn-sm btn-outline-primary" id="myLocationBtn"><i class="bi bi-crosshair"></i> My location</button>
            <label class="form-check form-switch small mb-0">
                <input class="form-check-input" type="checkbox" id="trafficLayerToggle">
                <span class="form-check-label">Traffic</span>
            </label>
            <button class="btn btn-sm btn-primary" id="refreshMapBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        </div>
    </div>

    <div id="mapMessage" class="alert alert-info">Loading map...</div>
    <div id="liveMap" style="height: 540px; width: 100%; border-radius: 14px; overflow: hidden; background: #e9ecef;"></div>
</div>

<div class="panel mt-4">
    <div class="panel-head">
        <div>
            <h3>Vehicle list</h3>
            <p class="muted">Latest known status for each vehicle.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Vehicle</th>
                    <th>Operator</th>
                    <th>Status</th>
                    <th>Last updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehicles as $vehicle)
                    <tr class="cursor-pointer" data-vehicle-id="{{ $vehicle['id'] }}" data-lat="{{ $vehicle['latitude'] ?? '' }}" data-lng="{{ $vehicle['longitude'] ?? '' }}">
                        <td>{{ $vehicle['plate_number'] ?? $vehicle['vehicle_code'] }}</td>
                        <td>{{ $vehicle['operator_name'] }}</td>
                        <td>
                            @if(($vehicle['status'] ?? 'offline') === 'online')
                                <span class="badge bg-success-subtle text-success">Online</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Offline</span>
                            @endif
                        </td>
                        <td>{{ $vehicle['last_updated'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-5 text-muted">No tracked vehicles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    const mapConfig = {
        apiKey: @json($apiKey),
        vehicles: @json($vehicles),
        refreshUrl: @json(route('live-map.data'))
    };
</script>
<script>
    let liveMap;
    let trafficLayer;
    let markers = [];

    function initLiveMap() {
        const mapEl = document.getElementById('liveMap');
        if (!mapEl) return;
        document.getElementById('mapMessage').className = 'alert alert-secondary d-none';

        const defaultCenter = { lat: 14.5995, lng: 120.9842 };
        liveMap = new google.maps.Map(mapEl, {
            center: defaultCenter,
            zoom: 12,
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
        });

        trafficLayer = new google.maps.TrafficLayer();
        if (document.getElementById('trafficLayerToggle').checked) {
            trafficLayer.setMap(liveMap);
        }

        renderVehicleMarkers(mapConfig.vehicles);

        document.getElementById('trafficLayerToggle').addEventListener('change', function () {
            if (this.checked) {
                trafficLayer.setMap(liveMap);
            } else {
                trafficLayer.setMap(null);
            }
        });

        document.getElementById('refreshMapBtn').addEventListener('click', refreshVehicleLocations);
        document.getElementById('vehicleSearch').addEventListener('input', renderFilteredMarkers);
        document.getElementById('vehicleFilter').addEventListener('change', renderFilteredMarkers);
        document.getElementById('myLocationBtn').addEventListener('click', function () {
            if (!navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition(function (position) {
                const point = { lat: position.coords.latitude, lng: position.coords.longitude };
                liveMap.setCenter(point); liveMap.setZoom(16);
                new google.maps.Marker({ map: liveMap, position: point, title: 'My location', icon: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png' });
            }, function () {
                const message = document.getElementById('mapMessage');
                message.className = 'alert alert-warning'; message.textContent = 'Unable to obtain your current location.';
            }, { enableHighAccuracy: true, maximumAge: 0, timeout: 15000 });
        });
        document.querySelectorAll('[data-vehicle-id]').forEach(function (row) {
            row.addEventListener('click', function () {
                const lat = parseFloat(this.dataset.lat);
                const lng = parseFloat(this.dataset.lng);
                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    liveMap.setCenter({ lat, lng });
                    liveMap.setZoom(14);
                }
            });
        });
    }

    function renderFilteredMarkers() {
        const search = document.getElementById('vehicleSearch').value.toLowerCase();
        const filter = document.getElementById('vehicleFilter').value;
        renderVehicleMarkers(mapConfig.vehicles.filter(function (vehicle) {
            const label = (vehicle.plate_number || vehicle.vehicle_code || '').toLowerCase();
            return (!search || label.includes(search)) && (filter === 'all' || vehicle.status === filter);
        }));
    }

    function renderVehicleMarkers(vehicles) {
        if (!liveMap) return;

        markers.forEach(marker => marker.setMap(null));
        markers = [];

        const validVehicles = vehicles.filter(vehicle => Number.isFinite(parseFloat(vehicle.latitude)) && Number.isFinite(parseFloat(vehicle.longitude)));
        if (!validVehicles.length) {
            const message = document.getElementById('mapMessage');
            message.className = 'alert alert-warning'; message.textContent = 'No vehicles have a valid recent GPS location yet.';
            return;
        }
        document.getElementById('mapMessage').className = 'alert alert-secondary d-none';

        validVehicles.forEach(function (vehicle) {
            const marker = new google.maps.Marker({
                position: { lat: parseFloat(vehicle.latitude), lng: parseFloat(vehicle.longitude) },
                map: liveMap,
                title: vehicle.plate_number || vehicle.vehicle_code,
                animation: google.maps.Animation.DROP,
            });

            const infoWindow = new google.maps.InfoWindow({
                content: '<div style="min-width:180px"><strong>' + (vehicle.plate_number || vehicle.vehicle_code) + '</strong><br>' +
                    '<small>' + (vehicle.vehicle_type || 'Vehicle') + '</small><br>' +
                    '<span>' + vehicle.operator_name + '</span><br>' +
                    '<span>' + (vehicle.status || 'offline') + '</span></div>'
            });

            marker.addListener('click', function () {
                infoWindow.open({ anchor: marker, map: liveMap });
            });

            markers.push(marker);
        });

        const first = validVehicles[0];
        liveMap.setCenter({ lat: parseFloat(first.latitude), lng: parseFloat(first.longitude) });
    }

    function refreshVehicleLocations() {
        if (!mapConfig.refreshUrl) return;

        fetch(mapConfig.refreshUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) {
                if (!response.ok) throw new Error('Unable to refresh map data.');
                return response.json();
            })
            .then(function (payload) {
                if (payload.vehicles) {
                    mapConfig.vehicles = payload.vehicles;
                    renderVehicleMarkers(payload.vehicles);
                }
            })
            .catch(function () {
                console.error('Map refresh failed');
            });
    }

    window.initLiveMap = initLiveMap;
    if (!mapConfig.apiKey) {
        document.getElementById('mapMessage').className = 'alert alert-warning';
        document.getElementById('mapMessage').textContent = 'Google Maps API key is not configured.';
    } else {
        const mapScript = document.createElement('script');
        mapScript.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(mapConfig.apiKey) + '&callback=initLiveMap&v=weekly';
        mapScript.async = true; mapScript.defer = true;
        mapScript.onerror = function () { document.getElementById('mapMessage').className = 'alert alert-danger'; document.getElementById('mapMessage').textContent = 'Google Maps could not be loaded. Check the API key, billing, and API restrictions.'; };
        document.head.appendChild(mapScript);
    }
    setInterval(refreshVehicleLocations, 15000);
</script>
@endsection
