@extends('admin.layout.master')

@php
    $route = 'admin.users';
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
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">العملاء</h1>
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
                <a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">العملاء</a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-gray-600">التفاصيل</li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
    <!--end::Page title-->
</div>
<!--end::Toolbar-->
@endsection

@section('content')

<div class="content flex-column-fluid" id="kt_content">

    <div class="card">
        <!--begin::Card body-->
        <div class="card-body p-lg-10">

            <div class="row mb-8">
                <div class="col-xl-3">
                    <div class="symbol symbol-100px">
                        @if ($data->getMedia('profile')->count())
                        <img src="{{$data->getFirstMediaUrl('profile')}}" >
                        @else
                        <img src="{{asset('dash/assets/media/svg/avatars/blank.svg')}}" >
                        @endif
                    </div>
                </div>
                <div class="col-lg-8">
                    
                </div>
            </div>

            <div class="row mb-8">
                <div class="col-xl-2">
                    <div class="fs-6 fw-semibold">الاسم بالكامل :  </div>
                </div>
                <div class="col-lg-9">
                    <div class="fw-bold fs-5">{{$data->name}}</div>
                </div>
            </div>

            <div class="row mb-8">
                <div class="col-xl-2">
                    <div class="fs-6 fw-semibold">رقم الهاتف :</div>
                </div>
                <div class="col-lg-9">
                    <div class="fw-bold fs-5">{{$data->phone}}</div>
                </div>
            </div>

            <div class="row mb-8">
                <div class="col-xl-2">
                    <div class="fs-6 fw-semibold">البريد الالكتروني :</div>
                </div>
                <div class="col-lg-9">
                    <div class="fw-bold fs-5">{{$data->email}}</div>
                </div>
            </div>

        </div>
        <!--end::Card body-->
    </div>

</div>

@endsection

@section('script')
@endsection