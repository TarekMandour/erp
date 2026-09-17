@extends('admin.layout.master')
@php $route = 'finance.warehouse-transactions'; $transaction = null; @endphp
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">إضافة حركة مخزنية</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">حركات المخزون</a></li>
            <li class="breadcrumb-item text-gray-600">إضافة</li>
        </ul>
    </div>
</div>
@endsection
@section('content')
<div class="content flex-column-fluid" id="kt_content">
    <form method="POST" action="{{route($route.'.store')}}">
        @csrf
        <div class="card card-flush py-4">
            <div class="card-header">
                <div class="card-title"><h2>حركة مخزنية جديدة</h2></div>
            </div>
            <div class="card-body pt-0">
                @include('finance.warehouse-transactions.form')
            </div>
            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <a href="{{route($route.'.index')}}" class="btn btn-light me-3">الغاء</a>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </div>
    </form>
</div>
@endsection
@section('script')
<script>
$(function () {
    $('#product_id').on('change', function () {
        var productId = $(this).val();
        var $variant = $('#variant_id');
        $variant.html('<option value="">بدون متغير</option>');
        if (!productId) return;
        $.get("{{route($route.'.get-variants')}}", { product_id: productId }, function (variants) {
            variants.forEach(function (v) {
                $variant.append('<option value="' + v.id + '">' + v.sku + '</option>');
            });
        });
    });
});
</script>
@endsection
