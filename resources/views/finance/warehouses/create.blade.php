@extends('admin.layout.master')
@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
@endsection
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">المستودعات</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route('finance.warehouses.index')}}" class="text-gray-600 text-hover-primary">المستودعات</a></li>
            <li class="breadcrumb-item text-gray-600">إضافة جديد</li>
        </ul>
    </div>
</div>
@endsection
@section('content')
<div class="content flex-column-fluid" id="kt_content">
    <form method="POST" action="{{route('finance.warehouses.store')}}">
        @csrf
        <div class="d-flex flex-column flex-lg-row">
            <div class="flex-lg-row-fluid">
                @include('finance.warehouses.form')
            </div>
        </div>
    </form>
</div>
@endsection
@section('script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script>
$(function () {
    var map = L.map('warehouse-map').setView([24.7136, 46.6753], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    var drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    var drawControl = new L.Control.Draw({
        draw: {
            polyline: false, circle: false, circlemarker: false,
            marker: false, rectangle: false,
            polygon: {
                allowIntersection: false,
                showArea: true,
                shapeOptions: { color: '#3B82F6', fillOpacity: 0.2 }
            }
        },
        edit: { featureGroup: drawnItems }
    });
    map.addControl(drawControl);

    // Restore polygon if old() or edit mode
    var savedStr = $('#polygon_coords').val();
    if (savedStr) {
        try {
            var saved = JSON.parse(savedStr);
            if (saved && saved.length >= 3) {
                var poly = L.polygon(saved.map(function(c){ return [c[0], c[1]]; }), { color: '#3B82F6', fillOpacity: 0.2 });
                drawnItems.addLayer(poly);
                map.fitBounds(poly.getBounds());
                updateInfo(saved.length);
            }
        } catch(e) {}
    }

    map.on(L.Draw.Event.CREATED, function(e) {
        drawnItems.clearLayers();
        drawnItems.addLayer(e.layer);
        var coords = e.layer.getLatLngs()[0].map(function(ll) {
            return [parseFloat(ll.lat.toFixed(6)), parseFloat(ll.lng.toFixed(6))];
        });
        $('#polygon_coords').val(JSON.stringify(coords));
        updateInfo(coords.length);
    });

    map.on(L.Draw.Event.EDITED, function(e) {
        e.layers.eachLayer(function(layer) {
            var coords = layer.getLatLngs()[0].map(function(ll) {
                return [parseFloat(ll.lat.toFixed(6)), parseFloat(ll.lng.toFixed(6))];
            });
            $('#polygon_coords').val(JSON.stringify(coords));
            updateInfo(coords.length);
        });
    });

    map.on(L.Draw.Event.DELETED, function() {
        $('#polygon_coords').val('');
        updateInfo(0);
    });

    $('#btn_clear_location').on('click', function() {
        drawnItems.clearLayers();
        $('#polygon_coords').val('');
        updateInfo(0);
    });

    function updateInfo(count) {
        if (count >= 3) {
            $('#polygon-info').removeClass('alert-info').addClass('alert-success')
                .html('<i class="bi bi-check-circle-fill me-1"></i> تم تحديد منطقة بـ ' + count + ' نقطة');
        } else {
            $('#polygon-info').removeClass('alert-success').addClass('alert-info')
                .html('<i class="bi bi-info-circle me-1"></i> استخدم أداة رسم المضلع <i class="bi bi-pentagon"></i> لتحديد منطقة المستودع على الخريطة');
        }
    }
});
</script>
@endsection
