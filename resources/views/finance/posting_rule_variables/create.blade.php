@extends('admin.layout.master')

@php $route = 'finance.posting_rule_variables'; @endphp

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">إضافة متغير قاعدة ترحيل</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">المتغيرات</a></li>
            <li class="breadcrumb-item text-gray-600">إضافة</li>
        </ul>
    </div>
</div>
@endsection

@section('content')
<div class="content flex-column-fluid" id="kt_content">
    @if($errors->any())
        <div class="alert alert-danger mb-5">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route($route.'.store') }}">
        @csrf
        @include('finance.posting_rule_variables.form')
        <div class="d-flex justify-content-end gap-2 mt-5">
            <a href="{{ route($route.'.index') }}" class="btn btn-secondary">إلغاء</a>
            <button type="submit" class="btn btn-primary">
                <i class="ki-duotone ki-check fs-2"></i> حفظ
            </button>
        </div>
    </form>
</div>
@endsection
