@extends('admin.layout.master')
@php $route = 'finance.opening_balances'; @endphp
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تفاصيل الرصيد الافتتاحي</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">الأرصدة الافتتاحية</a></li>
            <li class="breadcrumb-item text-gray-600">#{{$data->id}}</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
        <a href="{{route($route.'.edit', $data->id)}}" class="btn btn-primary me-3">
            <i class="bi bi-pencil-square fs-4 me-1"></i> تعديل
        </a>
        <a href="{{route($route.'.index')}}" class="btn btn-light fw-bold">
            <i class="bi bi-arrow-right fs-4 me-1"></i> العودة
        </a>
    </div>
</div>
@endsection
@section('content')
<div class="content flex-column-fluid" id="kt_content">
    <div class="row justify-content-center">
        <div class="col-xl-6">
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <h3 class="card-title"><span class="card-label fw-bold text-gray-900">بيانات الرصيد الافتتاحي</span></h3>
                </div>
                <div class="card-body pt-5">
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">الحساب</span>
                        <span class="fw-bold fs-6">
                            {{$data->account->code ?? ''}} - {{$data->account->name ?? '—'}}
                        </span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">مدين</span>
                        <strong class="fs-5 text-success">{{number_format($data->debit, 2)}}</strong>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">دائن</span>
                        <strong class="fs-5 text-danger">{{number_format($data->credit, 2)}}</strong>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">التاريخ</span>
                        <span class="fw-semibold fs-6">{{$data->date->format('Y-m-d')}}</span>
                    </div>
                    @if($data->description)
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">البيان</span>
                        <span class="fw-semibold fs-6">{{$data->description}}</span>
                    </div>
                    @endif
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">تاريخ الإنشاء</span>
                        <span class="fw-semibold fs-6 text-muted">{{$data->created_at->format('Y-m-d H:i')}}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
