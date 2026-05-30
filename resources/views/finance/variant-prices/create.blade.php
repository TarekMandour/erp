@extends('admin.layout.master')
@php $route = 'finance.variant-prices'; $data = null; $variants = collect(); @endphp
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">إضافة قاعدة سعر</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">قواعد الأسعار</a></li>
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
                <div class="card-title"><h2>قاعدة سعر جديدة</h2></div>
            </div>
            <div class="card-body pt-0">
                @include('finance.variant-prices.form')
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
    var getVariantsUrl = "{{ route($route.'.get-variants') }}";

    // Load variants on product change
    $('#product_id').on('change', function () {
        var productId = $(this).val();
        $('#variant_id').empty().append('<option value="">-- اختر المتغير --</option>');
        $('#base-price-hint').hide();
        $('#price').val('');
        if (!productId) return;

        $.get(getVariantsUrl, {product_id: productId}, function (res) {
            $.each(res, function (i, v) {
                $('#variant_id').append('<option value="' + v.id + '" data-price="' + v.selling_price + '">' + v.label + '</option>');
            });
        });
    });

    // Show base price on variant select
    $('#variant_id').on('change', function () {
        var price = $(this).find(':selected').data('price');
        if (price !== undefined && price !== '') {
            $('#base-price-val').text(parseFloat(price).toFixed(2));
            $('#base-price-hint').show();
            if (!$('#price').val()) $('#price').val(parseFloat(price).toFixed(2));
        } else {
            $('#base-price-hint').hide();
        }
    });

    // Add tier
    $('#add-tier').on('click', function () {
        var tpl = $('#tier-template').html();
        $('#tiers-container').append(tpl);
    });

    // Remove tier
    $(document).on('click', '.remove-tier', function () {
        $(this).closest('.tier-row').remove();
    });
});
</script>
@endsection
