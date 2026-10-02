@extends('layouts.app')

@section('content')
@include('partials.leaflet')

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
            <input name="location" id="reportLocationName" class="form-control mb-3" value="{{ old('location') }}" placeholder="Optional landmark or street">
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
                <button type="button" class="btn btn-outline-primary btn-sm text-nowrap" id="reportMyLocation"><i class="bi bi-crosshair me-1"></i> My location</button>
            </div>
            <div id="reportMapMessage" class="alert alert-secondary d-none mb-3"></div>
            <div class="alert alert-warning d-none leaflet-load-error mb-3">
                Map library could not be loaded. Check your internet connection and reload the page.
            </div>
            <div id="reportMap" class="leaflet-map" style="min-height: 430px;"></div>
            <div class="small text-muted mt-2" id="reportSelectionSummary">No location selected yet.</div>
        </div>
    </div>
</form>

<script>
    (function () {
        'use strict';

        const defaults = window.mapDefaults;
        const latitudeInput = document.getElementById('reportLatitude');
        const longitudeInput = document.getElementById('reportLongitude');
        const coordinatesLabel = document.getElementById('reportCoordinates');
        const selectionSummary = document.getElementById('reportSelectionSummary');
        const messageNode = document.getElementById('reportMapMessage');
        const form = document.getElementById('reportForm');
        const locateButton = document.getElementById('reportMyLocation');

        const geolocationOptions = { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 };

        let map = null;
        let marker = null;
        let accuracyCircle = null;
        let geolocationMarker = null;
        let tileWarningShown = false;

        function showMessage(type, text) {
            messageNode.className = 'alert alert-' + type + ' mb-3';
            messageNode.textContent = text;
        }

        function hideMessage() {
            messageNode.className = 'alert alert-secondary d-none mb-3';
        }

        function formatCoordinate(value) {
            return Number(value).toFixed(6);
        }

        function validLatitude(value) {
            return Number.isFinite(value) && value >= -90 && value <= 90;
        }

        function validLongitude(value) {
            return Number.isFinite(value) && value >= -180 && value <= 180;
        }

        function applyLocation(latitude, longitude, label, accuracyMeters) {
            latitudeInput.value = Number(latitude).toFixed(8);
            longitudeInput.value = Number(longitude).toFixed(8);
            coordinatesLabel.textContent = 'Latitude: ' + formatCoordinate(latitude) + ' | Longitude: ' + formatCoordinate(longitude);
            selectionSummary.textContent = (accuracyMeters
                ? label + ' · accuracy approximately ' + Math.round(accuracyMeters) + ' meters.'
                : label + ' · Latitude ' + formatCoordinate(latitude) + ', Longitude ' + formatCoordinate(longitude));

            if (!map) return;

            if (marker) {
                marker.setLatLng([latitude, longitude]);
            } else {
                marker = L.marker([latitude, longitude], { draggable: true, title: 'Report location' }).addTo(map);
                marker.on('dragend', function () {
                    const point = marker.getLatLng();
                    clearAccuracy();
                    applyLocation(point.lat, point.lng, 'Location selected');
                });
            }

            if (Number.isFinite(accuracyMeters)) {
                clearAccuracy();
                accuracyCircle = L.circle([latitude, longitude], {
                    radius: accuracyMeters,
                    color: '#2d75b9',
                    weight: 1,
                    fillColor: '#2d75b9',
                    fillOpacity: 0.15,
                }).addTo(map);
            }

            marker.bindPopup(accuracyMeters
                ? 'Location selected<br>Latitude: ' + formatCoordinate(latitude) + '<br>Longitude: ' + formatCoordinate(longitude) + '<br>Accuracy: approximately ' + Math.round(accuracyMeters) + ' meters'
                : 'Location selected<br>Latitude: ' + formatCoordinate(latitude) + '<br>Longitude: ' + formatCoordinate(longitude)).openPopup();
        }

        function clearAccuracy() {
            if (accuracyCircle) {
                accuracyCircle.remove();
                accuracyCircle = null;
            }
            if (geolocationMarker) {
                geolocationMarker.remove();
                geolocationMarker = null;
            }
        }

        function restoreSavedLocation() {
            const latitude = parseFloat(latitudeInput.value);
            const longitude = parseFloat(longitudeInput.value);

            if (!validLatitude(latitude) || !validLongitude(longitude)) return false;

            applyLocation(latitude, longitude, 'Location selected');
            map.setView([latitude, longitude], defaults.locationZoom);
            return true;
        }

        function initMap() {
            if (!window.L) {
                document.querySelectorAll('.leaflet-load-error').forEach(function (node) {
                    node.classList.remove('d-none');
                });
                showMessage('danger', 'Map library could not be loaded. Check your internet connection.');
                locateButton.disabled = true;
                return;
            }

            map = L.map('reportMap', { zoomControl: true, attributionControl: true })
                .setView(defaults.center, defaults.zoom);

            L.tileLayer(defaults.tileUrl, {
                maxZoom: 19,
                attribution: defaults.attribution,
            }).on('tileerror', function () {
                if (tileWarningShown) return;
                tileWarningShown = true;
                showMessage('warning', 'Map tiles could not be loaded. Check your internet connection; you can still select a location.');
            }).addTo(map);

            map.on('click', function (event) {
                clearAccuracy();
                applyLocation(event.latlng.lat, event.latlng.lng, 'Location selected');
                showMessage('success', 'Location selected.');
            });

            map.on('moveend', function () {
                map.invalidateSize();
            });

            if (!restoreSavedLocation()) {
                showMessage('info', 'Click the map to select the incident location.');
            } else {
                showMessage('success', 'Location selected.');
            }
        }

        function locateMe() {
            if (!navigator.geolocation) {
                showMessage('danger', 'This browser does not support location services.');
                return;
            }

            if (!window.L || !map) {
                showMessage('danger', 'The map is still loading. Please try again in a moment.');
                return;
            }

            locateButton.disabled = true;
            showMessage('info', 'Requesting your current location...');

            navigator.geolocation.getCurrentPosition(function (position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;

                clearAccuracy();
                geolocationMarker = L.circleMarker([latitude, longitude], {
                    radius: 8,
                    color: '#168b87',
                    fillColor: '#168b87',
                    fillOpacity: 0.95,
                    weight: 2,
                }).addTo(map).bindPopup('Your current location');

                applyLocation(latitude, longitude, 'Current location selected', position.coords.accuracy);
                map.setView([latitude, longitude], defaults.locationZoom);

                locateButton.disabled = false;
                showMessage('success', 'Current location selected. Accuracy: approximately ' + Math.round(position.coords.accuracy) + ' meters.');
            }, function (error) {
                locateButton.disabled = false;

                if (error.code === error.PERMISSION_DENIED) {
                    showMessage('warning', 'Location permission was denied. Please allow location access in your browser or select a location manually on the map.');
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    showMessage('warning', 'Your location could not be determined. Please select your location manually.');
                } else if (error.code === error.TIMEOUT) {
                    showMessage('warning', 'Location request timed out. Please try again or select your location manually.');
                } else {
                    showMessage('warning', 'Your location could not be determined. Please select your location manually.');
                }
            }, geolocationOptions);
        }

        form.addEventListener('submit', function (event) {
            const latitude = parseFloat(latitudeInput.value);
            const longitude = parseFloat(longitudeInput.value);

            if (!validLatitude(latitude) || !validLongitude(longitude)) {
                event.preventDefault();
                showMessage('danger', 'Please select the incident location on the map.');
                if (map) map.invalidateSize();
                return;
            }

            latitudeInput.value = latitude.toFixed(8);
            longitudeInput.value = longitude.toFixed(8);
        });

        locateButton.addEventListener('click', locateMe);

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMap);
        } else {
            initMap();
        }

        window.addEventListener('resize', function () {
            if (map) map.invalidateSize();
        });
    })();
</script>
@endsection