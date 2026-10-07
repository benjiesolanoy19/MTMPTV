@extends('layouts.app')
@section('content')
@include('partials.leaflet')
@php
    $hasValidReportLocation = $report->latitude !== null && $report->longitude !== null && is_numeric($report->latitude) && is_numeric($report->longitude)
        && (float) $report->latitude >= -90 && (float) $report->latitude <= 90
        && (float) $report->longitude >= -180 && (float) $report->longitude <= 180;
@endphp
<div class="page-head"><div><a href="{{ url()->previous() }}" class="text-link"><i data-lucide="arrow-left" class="" aria-hidden="true"></i> Reports</a><h1>{{ $report->report_number }}</h1><p class="muted">{{ $report->report_type }} · Submitted {{ $report->date_submitted->format('d M Y') }}</p></div><span class="badge bg-light text-dark fs-6">{{ $report->status }}</span></div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="panel">
            <h3 class="mb-4">Report details</h3>
            <dl class="row small">
                <dt class="col-sm-3 text-muted">Location</dt>
                <dd class="col-sm-9">{{ $report->location ?: 'Not provided' }}</dd>
                <dt class="col-sm-3 text-muted">Latitude</dt>
                <dd class="col-sm-9">{{ $report->latitude !== null ? number_format((float) $report->latitude, 6) : 'Not provided' }}</dd>
                <dt class="col-sm-3 text-muted">Longitude</dt>
                <dd class="col-sm-9">{{ $report->longitude !== null ? number_format((float) $report->longitude, 6) : 'Not provided' }}</dd>
                <dt class="col-sm-3 text-muted">Description</dt>
                <dd class="col-sm-9">{{ $report->description }}</dd>
                <dt class="col-sm-3 text-muted">Processing status</dt>
                <dd class="col-sm-9">{{ $report->processing_status ?: 'Not provided' }}</dd>
                <dt class="col-sm-3 text-muted">Resolution</dt>
                <dd class="col-sm-9">{{ $report->resolution ?: 'No resolution recorded.' }}</dd>
            </dl>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel">
            <h3 class="mb-3">Incident location</h3>
            @if ($hasValidReportLocation)
                <div id="reportLocationStatus" class="small text-muted mb-2"><span class="text-success">●</span> Report location available</div>
                <div id="reportMapMessage" class="alert alert-secondary d-none" role="status" aria-live="polite"></div>
                <div class="alert alert-warning d-none leaflet-load-error mb-3">Map tiles could not be loaded. Open Location in Maps to view the incident location.</div>
                <div id="reportDetailMap" class="leaflet-map" style="height: 360px; min-height: 360px;" aria-label="Incident location map"></div>
                <div class="small text-muted mt-2">Reported Location: {{ $report->location ?: 'Not provided' }}</div>
                <div class="small text-muted">Coordinates: {{ number_format((float) $report->latitude, 6) }}, {{ number_format((float) $report->longitude, 6) }}</div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" id="reportMyLocationBtn" class="btn btn-sm btn-outline-primary"><i data-lucide="navigation" class="me-1" aria-hidden="true"></i> My Location</button>
                    <button type="button" id="reportNavigateBtn" class="btn btn-sm btn-primary"><i data-lucide="map-pinned" class="me-1" aria-hidden="true"></i> Navigate to Report</button>
                </div>
            @else
                <div class="alert alert-secondary mb-0">
                    <strong>Map unavailable</strong>
                    <div class="small mt-1">Valid location coordinates were not provided for this report.</div>
                </div>
            @endif
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel">
            <h3 class="mb-3">Status timeline</h3>
            <p class="small text-muted mb-1">Submitted</p>
            <p>{{ $report->date_submitted->format('d M Y') }}</p>
            <p class="small text-muted mb-1">Last updated</p>
            <p>{{ $report->updated_at->format('d M Y, H:i') }}</p>
        </div>
    </div>
</div>
@if ($hasValidReportLocation)
<script>
    (function () {
        'use strict';

        const mapDefaults = window.mapDefaults || {};
        const reportLatitude = @json((float) $report->latitude);
        const reportLongitude = @json((float) $report->longitude);
        const reportLatLng = [reportLatitude, reportLongitude];
        const mapMessage = document.getElementById('reportMapMessage');
        const locationStatus = document.getElementById('reportLocationStatus');
        const myLocationButton = document.getElementById('reportMyLocationBtn');
        const navigateButton = document.getElementById('reportNavigateBtn');
        const reportMapContainer = document.getElementById('reportDetailMap');

        function setMessage(message, type) {
            if (!mapMessage) return;
            mapMessage.textContent = message;
            mapMessage.className = 'alert alert-' + (type || 'secondary');
            mapMessage.classList.remove('d-none');
        }

        function hideMessage() {
            if (!mapMessage) return;
            mapMessage.classList.add('d-none');
        }

        function initMap() {
            if (!reportMapContainer) return;
            if (!window.L) {
                document.querySelectorAll('.leaflet-load-error').forEach(function (node) {
                    node.classList.remove('d-none');
                });
                return;
            }

            if (reportMapContainer._leaflet_id) {
                return;
            }

            const map = L.map('reportDetailMap', {
                zoomControl: true,
                scrollWheelZoom: true,
                dragging: true,
                attributionControl: true,
            }).setView(reportLatLng, 16);

            const tiles = L.tileLayer(mapDefaults.tileUrl || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: mapDefaults.attribution || '&copy; OpenStreetMap contributors'
            });

            tiles.on('tileerror', function () {
                document.querySelectorAll('.leaflet-load-error').forEach(function (node) {
                    node.classList.remove('d-none');
                });
                setMessage('Map tiles could not be loaded.', 'warning');
            });

            tiles.addTo(map);

            const reportMarker = L.marker(reportLatLng, {
                title: @json($report->location ?: 'Report location')
            }).addTo(map);

            reportMarker.bindPopup(
                '<strong>Reported Incident</strong><br>' +
                @json($report->location ?: 'No landmark provided') + '<br>' +
                'Coordinates: ' + reportLatitude.toFixed(6) + ', ' + reportLongitude.toFixed(6) + '<br>' +
                'Report: ' + @json($report->report_number) + '<br>' +
                'Status: ' + @json($report->status)
            );
            reportMarker.openPopup();

            let userMarker = null;
            let userLocation = null;

            function updateUserLocation(lat, lng) {
                const positionLatLng = [lat, lng];
                if (!userMarker) {
                    userMarker = L.circleMarker(positionLatLng, {
                        radius: 8,
                        color: '#0d6efd',
                        fillColor: '#5aa0ff',
                        fillOpacity: 0.9,
                        weight: 2,
                    }).addTo(map);
                    userMarker.bindPopup('<strong>My Location</strong>');
                } else {
                    userMarker.setLatLng(positionLatLng);
                }

                userLocation = positionLatLng;
                const bounds = L.latLngBounds([positionLatLng, reportLatLng]);
                map.fitBounds(bounds, { padding: [40, 40] });
                if (locationStatus) {
                    locationStatus.innerHTML = '<span class="text-success">●</span> Your location detected';
                }
                setMessage('Your location detected and both points are visible on the map.', 'success');
            }

            function handleGeolocationError(error) {
                const message = 'Unable to access your location. Please allow location permission in your browser.';
                if (locationStatus) {
                    locationStatus.innerHTML = '<span class="text-warning">○</span> Your location unavailable';
                }
                setMessage(message, 'warning');
            }

            if (myLocationButton) {
                myLocationButton.addEventListener('click', function () {
                    if (!navigator.geolocation) {
                        handleGeolocationError();
                        return;
                    }

                    const geoOptions = {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0,
                    };

                    navigator.geolocation.getCurrentPosition(function (position) {
                        updateUserLocation(position.coords.latitude, position.coords.longitude);
                    }, handleGeolocationError, geoOptions);
                });
            }

            if (navigateButton) {
                navigateButton.addEventListener('click', function () {
                    if (!userLocation) {
                        setMessage('Your location is required for navigation. Please allow location access first.', 'warning');
                        if (myLocationButton) {
                            myLocationButton.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                        return;
                    }

                    const googleMapsUrl = 'https://www.google.com/maps/dir/?api=1&origin=' + userLocation[0] + ',' + userLocation[1] + '&destination=' + reportLatitude + ',' + reportLongitude + '&travelmode=driving';
                    window.open(googleMapsUrl, '_blank', 'noopener,noreferrer');
                });
            }

            window.addEventListener('resize', function () {
                window.setTimeout(function () {
                    map.invalidateSize();
                }, 150);
            });

            if (window.matchMedia) {
                const mediaQuery = window.matchMedia('(max-width: 991.98px)');
                const handleSidebarChange = function () {
                    window.setTimeout(function () {
                        map.invalidateSize();
                    }, 150);
                };
                if (mediaQuery.addEventListener) {
                    mediaQuery.addEventListener('change', handleSidebarChange);
                } else if (mediaQuery.addListener) {
                    mediaQuery.addListener(handleSidebarChange);
                }
            }

            if (window.sidebarToggleListener) {
                window.sidebarToggleListener.push(function () {
                    window.setTimeout(function () {
                        map.invalidateSize();
                    }, 200);
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMap);
        } else {
            initMap();
        }
    })();
</script>
@endif
@endsection