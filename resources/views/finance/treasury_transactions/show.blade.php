@extends('admin.layout.master')
@php $route = 'finance.treasury_transactions'; @endphp
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تفاصيل حركة الخزنة</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">حركات الخزنة</a></li>
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
                    <h3 class="card-title"><span class="card-label fw-bold text-gray-900">بيانات الحركة</span></h3>
                </div>
                <div class="card-body pt-5">
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">الخزنة</span>
                        <a href="{{route('finance.treasuries.show', $data->treasury_id)}}" class="fw-bold fs-6 text-primary">
                            {{$data->treasury->name}}
                        </a>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">نوع المعاملة</span>
                        <span class="badge badge-light-{{$data->type === 'deposit' ? 'success' : 'danger'}} fw-bold fs-6">
                            {{$data->type_name}}
                        </span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">المبلغ</span>
                        <span class="fw-bold fs-4 text-{{$data->type === 'deposit' ? 'success' : 'danger'}}">
                            {{$data->type === 'withdraw' ? '-' : '+'}}{{number_format($data->amount, 2)}} {{$data->treasury->currency}}
                        </span>
                    </div>
                    @if($data->description)
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">البيان</span>
                        <span class="fw-semibold fs-6">{{$data->description}}</span>
                    </div>
                    @endif
                    @if($data->reference_type || $data->reference_id)
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">المرجع</span>
                        <span class="fw-semibold fs-6">{{$data->reference_type}} {{$data->reference_id}}</span>
                    </div>
                    @endif
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">التاريخ</span>
                        <span class="fw-semibold fs-6">{{$data->created_at->format('Y-m-d H:i')}}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
