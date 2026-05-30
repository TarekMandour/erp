@extends('admin.layout.master')
@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">المستودعات</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route('finance.warehouses.index')}}" class="text-gray-600 text-hover-primary">المستودعات</a></li>
            <li class="breadcrumb-item text-gray-600">تفاصيل</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
        <a href="{{route('finance.warehouses.edit', $data->id)}}" class="btn btn-primary me-2">
            <i class="bi bi-pencil-square me-1"></i> تعديل
        </a>
        <a href="{{route('finance.warehouses.index')}}" class="btn btn-light">رجوع</a>
    </div>
</div>
@endsection
@section('content')
<div class="content flex-column-fluid" id="kt_content">
    <div class="card mb-5">
        <div class="card-header">
            <div class="card-title"><h2>بيانات المستودع</h2></div>
        </div>
        <div class="card-body">
            <div class="row mb-5">
                <div class="col-md-3 fw-bold text-gray-600">اسم المستودع</div>
                <div class="col-md-9">{{$data->name}}</div>
            </div>
            <div class="row mb-5">
                <div class="col-md-3 fw-bold text-gray-600">الحالة</div>
                <div class="col-md-9">
                    @if($data->is_active)
                        <span class="badge badge-light-success fw-bold">مفعل</span>
                    @else
                        <span class="badge badge-light-danger fw-bold">غير مفعل</span>
                    @endif
                </div>
            </div>
            @if($data->location && is_array($data->location) && count($data->location) >= 3)
            <div class="row mb-5">
                <div class="col-md-3 fw-bold text-gray-600">منطقة المستودع</div>
                <div class="col-md-9">
                    <span class="badge badge-light-primary fw-bold">
                        <i class="bi bi-bounding-box me-1"></i>{{ count($data->location) }} نقطة
                    </span>
                </div>
            </div>
            @endif
        </div>
    </div>

    @if($data->location && is_array($data->location) && count($data->location) >= 3)
    <div class="card">
        <div class="card-header">
            <div class="card-title"><h2><i class="bi bi-bounding-box text-primary me-2"></i>منطقة المستودع على الخريطة</h2></div>
        </div>
        <div class="card-body">
            <div id="warehouse-map" style="height: 450px; border-radius: 8px;"></div>
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-body text-center py-10 text-muted">
            <i class="bi bi-bounding-box fs-1 d-block mb-3"></i>
            لم يتم تحديد منطقة لهذا المستودع
        </div>
    </div>
    @endif
</div>
@endsection
@section('script')
@if($data->location && is_array($data->location) && count($data->location) >= 3)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(function () {
    var coords = @json($data->location);
    var latLngs = coords.map(function(c) { return [c[0], c[1]]; });

    var polygon = L.polygon(latLngs, { color: '#3B82F6', fillOpacity: 0.2 });

    var map = L.map('warehouse-map');

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    polygon.addTo(map)
        .bindPopup('<b>{{ addslashes($data->name) }}</b>')
        .openPopup();

    map.fitBounds(polygon.getBounds(), { padding: [30, 30] });
});
</script>
@endif
@endsection
