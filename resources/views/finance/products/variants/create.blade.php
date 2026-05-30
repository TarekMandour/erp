@extends('admin.layout.master')

@php
    $route = 'finance.products.variants';
    $viewPath = 'finance.products.variants';
@endphp

@section('css')
@endsection

@section('style')
    
@endsection

@section('breadcrumb')
<!--begin::Toolbar-->
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <!--begin::Page title-->
    <div class="page-title d-flex flex-column me-3">
        <!--begin::Title-->
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">المنتجات</h1>
        <!--end::Title-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <!--begin::Item-->
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route('finance.products.index')}}" class="text-gray-600 text-hover-primary">المنتجات</a>
            </li>
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route($route.'.index', $productId)}}" class="text-gray-600 text-hover-primary">انواع المنتجات</a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-gray-600">اضف جديد</li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
    <!--end::Page title-->
</div>
<!--end::Toolbar-->
@endsection

@section('content')


    <!--begin::Form-->
    <form action="{{route($route. '.store', $productId)}}" method="POST" enctype="multipart/form-data" id="kt_account_profile_details_form" class="form d-flex flex-column flex-lg-row">
        @csrf

        @include($viewPath. '.form')

    </form>



@endsection

@section('script')
<script src="{{asset('dash/assets/plugins/custom/formrepeater/formrepeater.bundle.js')}}"></script>
<script>
    $('#kt_docs_repeater_basic').repeater({
        initEmpty: false,

        defaultValues: {
            'text-input': 'foo'
        },

        show: function () {
            $(this).slideDown();
        },

        hide: function (deleteElement) {
            $(this).slideUp(deleteElement);
        }
    });

    $('#generateSku').click(function() {
        $.ajax({
            url: '{{ route("finance.products.variants.generate-sku", $productId) }}',
            method: 'GET',
            data: {

            },
            success: function(response) {
                if (response.success) {
                    $('#sku').val(response.sku);
                }
            }
        });
    });
</script>
@endsection