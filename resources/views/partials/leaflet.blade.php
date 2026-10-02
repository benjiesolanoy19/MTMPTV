{{--
    Shared Leaflet + OpenStreetMap assets.

    Include this partial at the top of any @section that renders a map. It emits the
    Leaflet stylesheet, the Leaflet script, and a window.mapDefaults object holding the
    shared tile layer / default centre values from config('services.map').

    No API key is required. Tiles are served by OpenStreetMap.
--}}
@php
    $leafletMapDefaults = [
        'tileUrl' => config('services.map.tile_url'),
        'attribution' => config('services.map.attribution'),
        'center' => [config('services.map.center_latitude'), config('services.map.center_longitude')],
        'zoom' => config('services.map.default_zoom'),
        'locationZoom' => config('services.map.location_zoom'),
    ];
@endphp
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    window.mapDefaults = {{ Illuminate\Support\Js::from($leafletMapDefaults) }};

    window.addEventListener('error', function () {
        if (window.L) return;
        document.querySelectorAll('.leaflet-load-error').forEach(function (node) {
            node.classList.remove('d-none');
        });
    }, { capture: true });
</script>
<style>
    .leaflet-map {
        width: 100%;
        min-height: 430px;
        height: 100%;
        border-radius: 14px;
        overflow: hidden;
        background: #e9ecef;
    }
    @media (max-width: 575.98px) {
        .leaflet-map { min-height: 340px; }
    }
    .leaflet-container { font: inherit; }
</style>