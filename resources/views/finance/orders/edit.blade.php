@extends('admin.layout.master')
@php $route = 'finance.orders'; @endphp
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تعديل طلب</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">الطلبات</a></li>
            <li class="breadcrumb-item text-gray-600">تعديل: {{$data->order_number}}</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
        <a href="{{route($route.'.show', $data->id)}}" class="btn btn-light-success fw-bold">
            <i class="ki-duotone ki-printer fs-3 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            عرض وطباعة
        </a>
    </div>
</div>
@endsection
@section('content')
<div class="content flex-column-fluid" id="kt_content">
    <form method="POST" action="{{route($route.'.update')}}">
        @csrf
        <input type="hidden" name="id" value="{{$data->id}}">
        <input type="hidden" name="order_number" value="{{$data->order_number}}">
        @include('finance.orders.form')
    </form>
</div>
@endsection
@include('finance.orders._orders_js')
