@extends('admin.layout.master')

@php
    $route = 'finance.customers.wallet';
    $viewPath = 'finance.customers.wallet';
@endphp

@section('css')
@endsection

@section('style')
@endsection

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">محفظة العميل</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
            </li>
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route('finance.customers.index')}}" class="text-gray-600 text-hover-primary">العملاء</a>
            </li>
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route($route.'.index', $customer->id)}}" class="text-gray-600 text-hover-primary">المحفظة</a>
            </li>
            <li class="breadcrumb-item text-gray-600">تعديل البيانات</li>
        </ul>
    </div>
</div>
@endsection

@section('content')
<form action="{{route($route.'.update', $customer->id)}}" method="POST" id="kt_account_profile_details_form" class="form d-flex flex-column flex-lg-row">
    @csrf
    <input type="hidden" name="id" value="{{$data->id}}" />
    <input type="hidden" name="customer_id" value="{{$customer->id}}" />
    @include($viewPath.'.form')
</form>
@endsection
