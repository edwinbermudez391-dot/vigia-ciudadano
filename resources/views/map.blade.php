@extends('layouts.app')

@section('title', 'Mapa de Incidencias — Vigía Ciudadano')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
    <style>
        #full-map { height: calc(100vh - 80px); min-height: 500px; width: 100%; z-index: 1; }
        .map-legend { background: white; padding: 12px 16px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); line-height: 1.6; font-size: 13px; }
        .map-legend h4 { margin: 0 0 8px 0; font-weight: 700; font-size: 14px; color: #062e24; }
        .map-legend-item { display: flex; align-items: center; gap: 8px; margin: 4px 0; }
        .map-legend-dot { width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.3); flex-shrink: 0; }
        .map-popup-photo { width: 200px; height: 130px; object-fit: cover; border-radius: 8px 8px 0 0; }
        .map-popup-body { padding: 10px 12px; }
        .map-popup-ticket { font-family: monospace; font-size: 11px; color: #597268; font-weight: 600; }
        .map-popup-title { font-size: 14px; font-weight: 700; color: #062e24; margin: 4px 0; line-height: 1.3; }
        .map-popup-category { font-size: 12px; color: #1f5142; }
        .map-popup-badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 6px; }
        .map-popup-link { display: block; text-align: center; margin-top: 8px; padding: 6px 12px; background: #087b5f; color: white; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 600; transition: background 0.2s; }
        .map-popup-link:hover { background: #05664e; }
        .marker-cluster-custom { background-clip: padding-box; border-radius: 50%; }
        .map-stats { position: absolute; top: 12px; left: 55px; z-index: 1000; background: white; padding: 8px 16px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-size: 13px; font-weight: 600; color: #062e24; }
        .map-stats span { color: #087b5f; }
        .map-filter-bar { position: absolute; bottom: 16px; left: 50%; transform: translateX(-50%); z-index: 1000; display: flex; gap: 6px; background: white; padding: 6px; border-radius: 14px; box-shadow: 0 4px 16px rgba(0,0,0,0.18); }
        .map-filter-btn { padding: 6px 14px; border-radius: 10px; border: 1px solid #d5e8e0; background: white; color: #062e24; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
        .map-filter-btn:hover { background: #f0faf5; border-color: #087b5f; }
        .map-filter-btn.active { background: #087b5f; color: white; border-color: #087b5f; }
        .leaflet-popup-content-wrapper { border-radius: 12px !important; padding: 0 !important; overflow: hidden; }
        .leaflet-popup-content { margin: 0 !important; width: 224px !important; }
        .leaflet-popup-tip { box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
    </style>
@endpush

@section('content')
<div class="relative">
    {{-- Estadísticas del mapa --}}
    <div class="map-stats" id="map-stats">
        📍 <span id="map-count">0</span> incidencias activas
    </div>

    {{-- Contenedor del mapa --}}
    <div id="full-map" role="application" aria-label="Mapa interactivo de incidencias ciudadanas"></div>

    {{-- Barra de filtros por estado --}}
    <div class="map-filter-bar" id="map-filters">
        <button type="button" class="map-filter-btn active" data-filter="all">Todos</button>
        <button type="button" class="map-filter-btn" data-filter="reported" style="--dot:#eab308">🟡 Reportados</button>
        <button type="button" class="map-filter-btn" data-filter="in_progress" style="--dot:#3b82f6">🔵 En progreso</button>
        <button type="button" class="map-filter-btn" data-filter="under_inspection" style="--dot:#8b5cf6">🟣 Inspección</button>
        <button type="button" class="map-filter-btn" data-filter="rework" style="--dot:#ef4444">🔴 Retrabajo</button>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
(function () {
    'use strict';

    const mapEl = document.getElementById('full-map');
    const statsEl = document.getElementById('map-count');
    const filtersEl = document.getElementById('map-filters');
    const apiUrl = '{{ url("/api/v1/reports/map-data") }}';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Inicializar mapa centrado en Santander de Quilichao
    const map = L.map('full-map', {
        zoomControl: true,
        scrollWheelZoom: true,
    }).setView([3.0095, -76.4849], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    // Clustering de marcadores
    const clusterGroup = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        iconCreateFunction: function (cluster) {
            const count = cluster.getChildCount();
            let size = 'small';
            let dim = 40;
            if (count >= 10) { size = 'medium'; dim = 50; }
            if (count >= 50) { size = 'large'; dim = 60; }
            return L.divIcon({
                html: '<div style="width:' + dim + 'px;height:' + dim + 'px;border-radius:50%;background:#087b5f;color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);">' + count + '</div>',
                className: 'marker-cluster-custom',
                iconSize: L.point(dim, dim),
            });
        }
    });

    let allMarkers = [];
    let activeFilter = 'all';

    // Crear marcador con color personalizado
    function createMarkerIcon(color) {
        return L.divIcon({
            html: '<div style="width:28px;height:28px;border-radius:50%;background:' + color + ';border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;">'
                + '<div style="width:10px;height:10px;border-radius:50%;background:white;opacity:0.8;"></div>'
                + '</div>',
            className: '',
            iconSize: L.point(28, 28),
            iconAnchor: L.point(14, 14),
            popupAnchor: L.point(0, -16),
        });
    }

    // Construir popup HTML
    function buildPopup(report) {
        const photoHtml = report.photo_url
            ? '<img src="' + escapeAttr(report.photo_url) + '" alt="Foto del reporte" class="map-popup-photo" loading="lazy">'
            : '<div style="width:200px;height:80px;background:#f0faf5;display:flex;align-items:center;justify-content:center;border-radius:8px 8px 0 0;"><span style="font-size:28px;">📷</span></div>';

        const statusBgColors = {
            'reported': '#fef9c3',
            'in_progress': '#dbeafe',
            'under_inspection': '#ede9fe',
            'rework': '#fee2e2',
            'closed': '#dcfce7',
        };
        const statusTextColors = {
            'reported': '#854d0e',
            'in_progress': '#1e40af',
            'under_inspection': '#5b21b6',
            'rework': '#991b1b',
            'closed': '#166534',
        };

        const bgColor = statusBgColors[report.status] || '#f1f5f9';
        const textColor = statusTextColors[report.status] || '#475569';

        return '<div>'
            + photoHtml
            + '<div class="map-popup-body">'
            + '<p class="map-popup-ticket">' + escapeHtml(report.ticket_code) + '</p>'
            + '<p class="map-popup-title">' + escapeHtml(report.title) + '</p>'
            + '<p class="map-popup-category">📁 ' + escapeHtml(report.category_name) + '</p>'
            + '<span class="map-popup-badge" style="background:' + bgColor + ';color:' + textColor + ';">'
            + escapeHtml(report.status_label)
            + '</span>'
            + '<a href="' + escapeAttr(report.detail_url) + '" class="map-popup-link">Ver detalle completo →</a>'
            + '</div>'
            + '</div>';
    }

    // Cargar datos del mapa
    async function loadMapData() {
        try {
            const res = await fetch(apiUrl, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });

            if (!res.ok) {
                console.error('Error cargando datos del mapa:', res.status);
                return;
            }

            const json = await res.json();
            const reports = json.data || [];

            statsEl.textContent = reports.length;

            // Limpiar marcadores anteriores
            clusterGroup.clearLayers();
            allMarkers = [];

            reports.forEach(function (report) {
                const marker = L.marker([report.latitude, report.longitude], {
                    icon: createMarkerIcon(report.marker_color),
                });

                marker.bindPopup(buildPopup(report), {
                    maxWidth: 224,
                    minWidth: 224,
                    closeButton: true,
                });

                marker.reportData = report;
                allMarkers.push(marker);
                clusterGroup.addLayer(marker);
            });

            map.addLayer(clusterGroup);

            // Ajustar vista si hay reportes
            if (reports.length > 0) {
                const bounds = clusterGroup.getBounds();
                if (bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
                }
            }
        } catch (err) {
            console.error('Error de red al cargar el mapa:', err);
        }
    }

    // Filtros por estado
    filtersEl.addEventListener('click', function (e) {
        const btn = e.target.closest('.map-filter-btn');
        if (!btn) return;

        const filter = btn.dataset.filter;
        if (filter === activeFilter) return;

        activeFilter = filter;

        // Actualizar botones activos
        filtersEl.querySelectorAll('.map-filter-btn').forEach(function (b) {
            b.classList.toggle('active', b.dataset.filter === filter);
        });

        // Filtrar marcadores
        clusterGroup.clearLayers();
        let visibleCount = 0;

        allMarkers.forEach(function (marker) {
            const show = filter === 'all' || marker.reportData.status === filter;
            if (show) {
                clusterGroup.addLayer(marker);
                visibleCount++;
            }
        });

        statsEl.textContent = visibleCount;
    });

    // Utilidades
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    function escapeAttr(s) {
        return escapeHtml(s);
    }

    // Cargar datos al iniciar
    loadMapData();
})();
</script>
@endpush
