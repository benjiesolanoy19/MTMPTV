@extends('layouts.app')

@section('content')
@include('partials.leaflet')
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
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <input id="vehicleSearch" class="form-control form-control-sm" placeholder="Search vehicle..." style="max-width: 180px;">
            <select id="vehicleFilter" class="form-select form-select-sm" style="max-width: 120px;"><option value="all">All</option><option value="online">Online</option><option value="offline">Offline</option></select>
            <button type="button" id="myLocationBtn" class="btn btn-sm btn-outline-primary"><i class="bi bi-crosshair"></i> My location</button>
            <button type="button" id="refreshMapBtn" class="btn btn-sm btn-primary"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        </div>
    </div>

    <div id="mapMessage" class="alert alert-secondary d-none"></div>
    <div class="alert alert-warning d-none leaflet-load-error mb-3">Map library could not be loaded. Check your internet connection and reload the page.</div>
    <div id="liveMap" class="leaflet-map" style="min-height: 430px; height: 540px;"></div>
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
        vehicles: @json($vehicles),
        refreshUrl: @json(route('live-map.data'))
    };
</script>
<script>
    (function () {
        'use strict';

        const defaults = window.mapDefaults;
        let liveMap = null;
        let markers = [];
        let userMarker = null;
        let listenersBound = false;
        let tileWarningShown = false;

        function showMapMessage(type, text) {
            const node = document.getElementById('mapMessage');
            node.className = 'alert alert-' + type;
            node.textContent = text;
        }

        function hideMapMessage() {
            document.getElementById('mapMessage').className = 'alert alert-secondary d-none';
        }

        function escapeHtml(value) {
            const text = value === null || value === undefined ? '' : String(value);
            return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function popupHtml(vehicle) {
            return '<div style="min-width:180px"><strong>' + escapeHtml(vehicle.plate_number || vehicle.vehicle_code) + '</strong><br>' +
                '<small>' + escapeHtml(vehicle.vehicle_type || 'Vehicle') + '</small><br>' +
                '<span>' + escapeHtml(vehicle.operator_name) + '</span><br>' +
                '<span>' + escapeHtml(vehicle.status || 'offline') + '</span></div>';
        }

        function renderVehicleMarkers(vehicles, fitView) {
            if (!liveMap) return;

            markers.forEach(function (marker) { marker.remove(); });
            markers = [];

            const validVehicles = vehicles.filter(function (vehicle) {
                return Number.isFinite(parseFloat(vehicle.latitude)) && Number.isFinite(parseFloat(vehicle.longitude));
            });

            if (!validVehicles.length) {
                showMapMessage('warning', 'No vehicles have a valid recent GPS location yet.');
                return;
            }

            hideMapMessage();

            validVehicles.forEach(function (vehicle) {
                const marker = L.marker([parseFloat(vehicle.latitude), parseFloat(vehicle.longitude)], {
                    title: vehicle.plate_number || vehicle.vehicle_code,
                }).addTo(liveMap);

                marker.bindPopup(popupHtml(vehicle));
                markers.push(marker);
            });

            if (fitView && markers.length) {
                liveMap.fitBounds(L.featureGroup(markers).getBounds().pad(0.15), { maxZoom: defaults.zoom });
            }
        }

        function renderFilteredMarkers() {
            const search = document.getElementById('vehicleSearch').value.toLowerCase();
            const filter = document.getElementById('vehicleFilter').value;

            renderVehicleMarkers(mapConfig.vehicles.filter(function (vehicle) {
                const label = (vehicle.plate_number || vehicle.vehicle_code || '').toLowerCase();
                return (!search || label.indexOf(search) !== -1) && (filter === 'all' || vehicle.status === filter);
            }), false);
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
                        renderVehicleMarkers(payload.vehicles, true);
                    }
                })
                .catch(function () {
                    console.error('Map refresh failed');
                });
        }

        function locateMe() {
            if (!navigator.geolocation) {
                showMapMessage('warning', 'This browser does not support location services.');
                return;
            }
            if (!liveMap) return;

            showMapMessage('info', 'Requesting your current location...');
            navigator.geolocation.getCurrentPosition(function (position) {
                const point = [position.coords.latitude, position.coords.longitude];

                if (userMarker) {
                    userMarker.setLatLng(point);
                } else {
                    userMarker = L.circleMarker(point, {
                        radius: 9, color: '#168b87', fillColor: '#168b87', fillOpacity: 0.95, weight: 2,
                    }).addTo(liveMap);
                }
                userMarker.bindPopup('My current location<br>Accuracy: approximately ' + Math.round(position.coords.accuracy) + ' meters').openPopup();

                liveMap.setView(point, defaults.locationZoom);
                showMapMessage('success', 'Your current location is shown. Accuracy: approximately ' + Math.round(position.coords.accuracy) + ' meters.');
            }, function (error) {
                if (error.code === error.PERMISSION_DENIED) {
                    showMapMessage('warning', 'Location permission was denied. Please allow location access in your browser.');
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    showMapMessage('warning', 'Your location could not be determined.');
                } else if (error.code === error.TIMEOUT) {
                    showMapMessage('warning', 'Location request timed out. Please try again.');
                } else {
                    showMapMessage('warning', 'Your location could not be determined.');
                }
            }, { enableHighAccuracy: true, maximumAge: 0, timeout: 15000 });
        }

        function bindControls() {
            if (listenersBound) return;
            listenersBound = true;

            document.getElementById('refreshMapBtn').addEventListener('click', refreshVehicleLocations);
            document.getElementById('vehicleSearch').addEventListener('input', renderFilteredMarkers);
            document.getElementById('vehicleFilter').addEventListener('change', renderFilteredMarkers);
            document.getElementById('myLocationBtn').addEventListener('click', locateMe);

            document.querySelectorAll('[data-vehicle-id]').forEach(function (row) {
                row.addEventListener('click', function () {
                    const lat = parseFloat(this.dataset.lat);
                    const lng = parseFloat(this.dataset.lng);
                    if (liveMap && Number.isFinite(lat) && Number.isFinite(lng)) {
                        liveMap.setView([lat, lng], Math.max(liveMap.getZoom(), 15));
                    }
                });
            });
        }

        function initLiveMap() {
            const mapEl = document.getElementById('liveMap');
            if (!mapEl) return;

            if (!window.L) {
                document.querySelectorAll('.leaflet-load-error').forEach(function (node) { node.classList.remove('d-none'); });
                showMapMessage('danger', 'Map library could not be loaded. Check your internet connection.');
                return;
            }

            liveMap = L.map('liveMap', { zoomControl: true }).setView(defaults.center, defaults.zoom);

            L.tileLayer(defaults.tileUrl, { maxZoom: 19, attribution: defaults.attribution })
                .on('tileerror', function () {
                    if (tileWarningShown) return;
                    tileWarningShown = true;
                    showMapMessage('warning', 'Map tiles could not be loaded. Check your internet connection.');
                })
                .addTo(liveMap);

            renderVehicleMarkers(mapConfig.vehicles, true);
            bindControls();

            window.setInterval(refreshVehicleLocations, 15000);
            window.addEventListener('resize', function () {
                if (liveMap) liveMap.invalidateSize();
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initLiveMap);
        } else {
            initLiveMap();
        }
    })();
</script>
@endsection