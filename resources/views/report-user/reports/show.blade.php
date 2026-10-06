@extends('layouts.app')
@section('content')
@include('partials.leaflet')
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
            @if ($report->latitude !== null && $report->longitude !== null)
                <div id="reportMapMessage" class="alert alert-secondary d-none"></div>
                <div class="alert alert-warning d-none leaflet-load-error mb-3">Map library could not be loaded. Check your internet connection and reload the page.</div>
                <div id="reportDetailMap" class="leaflet-map" style="min-height: 320px; height: 320px;"></div>
                <div class="small text-muted mt-2">OpenStreetMap · Latitude {{ number_format((float) $report->latitude, 6) }}, Longitude {{ number_format((float) $report->longitude, 6) }}</div>
            @else
                <p class="small text-muted mb-0">This report was submitted without map coordinates.</p>
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
@if ($report->latitude !== null && $report->longitude !== null)
<script>
    (function () {
        'use strict';

        const settings = window.mapDefaults;
        const latitude = @json((float) $report->latitude);
        const longitude = @json((float) $report->longitude);

        function initDetailMap() {
            const container = document.getElementById('reportDetailMap');
            if (!container || !window.L) return;

            const map = L.map('reportDetailMap', { zoomControl: true, dragging: false, scrollWheelZoom: false })
                .setView([latitude, longitude], settings.locationZoom);

            L.tileLayer(settings.tileUrl, { maxZoom: 19, attribution: settings.attribution }).addTo(map);

            L.marker([latitude, longitude], { title: @json($report->location ?: 'Report location') })
                .addTo(map)
                .bindPopup(@json($report->report_number).'<br>' + @json($report->location ?: 'No landmark provided') + '<br>Latitude: ' + latitude.toFixed(6) + '<br>Longitude: ' + longitude.toFixed(6))
                .openPopup();

            window.addEventListener('resize', () => map.invalidateSize());
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDetailMap);
        } else {
            initDetailMap();
        }
    })();
</script>
@endif
@endsection