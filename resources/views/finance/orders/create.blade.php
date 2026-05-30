@extends('admin.layout.master')
@php $route = 'finance.orders'; @endphp
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">إضافة طلب جديد</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">الطلبات</a></li>
            <li class="breadcrumb-item text-gray-600">إضافة طلب</li>
        </ul>
    </div>
</div>
@endsection
@section('content')
<div class="content flex-column-fluid" id="kt_content">
    <form method="POST" action="{{route($route.'.store')}}">
        @csrf
        @include('finance.orders.form')
    </form>
</div>
@endsection
@include('finance.orders._orders_js')
