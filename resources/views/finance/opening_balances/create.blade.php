@extends('admin.layout.master')
@php $route = 'finance.opening_balances'; $viewPath = 'finance.opening_balances'; @endphp
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">الأرصدة الافتتاحية</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">الأرصدة الافتتاحية</a></li>
            <li class="breadcrumb-item text-gray-600">اضف جديد</li>
        </ul>
    </div>
</div>
@endsection
@section('content')
<form action="{{route($route.'.store')}}" method="POST" id="kt_account_profile_details_form" class="form d-flex flex-column flex-lg-row">
    @csrf
    @include($viewPath.'.form')
</form>
@endsection
