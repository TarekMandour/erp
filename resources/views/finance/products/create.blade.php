@extends('admin.layout.master')

@php
    $url = 'admin/finance/products';
    $route = 'finance.products';
    $viewPath = 'finance.products';
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
                <a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">المنتجات</a>
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
    <form action="{{route($route. '.store')}}" method="POST" enctype="multipart/form-data" id="kt_account_profile_details_form" class="form d-flex flex-column flex-lg-row">
        @csrf

        @include($viewPath. '.form')

    </form>



@endsection

@section('script')
<script src="{{asset('dash/assets/plugins/custom/formrepeater/formrepeater.bundle.js')}}"></script>
<script>
    $('#kt_docs_repeater_basic,#kt_docs_repeater_basic_addition').repeater({
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
</script>
@endsection