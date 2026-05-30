@extends('admin.layout.master')

@php
    $route = 'finance.trans_account_trees';
@endphp

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تفاصيل القيد</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
            </li>
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">قيود شجرة الحسابات</a>
            </li>
            <li class="breadcrumb-item text-gray-600">#{{$data->id}}</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
        <a href="{{route($route.'.edit', $data->id)}}" class="btn btn-primary me-3">
            <i class="bi bi-pencil-square fs-4 me-1"></i> تعديل
        </a>
        @if($data->account)
        <a href="{{route('finance.account_trees.show', $data->account_id)}}" class="btn btn-info me-3">
            <i class="bi bi-diagram-3 fs-4 me-1"></i> الحساب
        </a>
        @endif
        <a href="{{route($route.'.index')}}" class="btn btn-light fw-bold">
            <i class="bi bi-arrow-right fs-4 me-1"></i> العودة
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="content flex-column-fluid" id="kt_content">

    <div class="row g-5 g-xl-10 mb-5">

        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">بيانات القيد</span>
                    </h3>
                </div>
                <div class="card-body pt-5">

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">رقم القيد</span>
                        <span class="fw-bold fs-6">#{{$data->id}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">الحساب</span>
                        <span class="fw-bold fs-6">
                            @if($data->account)
                                <a href="{{route('finance.account_trees.show', $data->account_id)}}">
                                    {{$data->account->code}} - {{$data->account->name}}
                                </a>
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">مدين</span>
                        <span class="fw-bold fs-4 text-danger">{{number_format($data->debit, 2)}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">دائن</span>
                        <span class="fw-bold fs-4 text-success">{{number_format($data->credit, 2)}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">المرجع</span>
                        <span class="fw-bold fs-6">
                            {{$data->reference_type ? $data->reference_type . ($data->reference_id ? ' #'.$data->reference_id : '') : '—'}}
                        </span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">التاريخ</span>
                        <span class="fw-bold fs-6">{{$data->created_at->format('Y-m-d H:i')}}</span>
                    </div>

                    @if($data->description)
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="mb-4">
                        <span class="text-gray-600 fw-semibold fs-6 d-block mb-2">الوصف</span>
                        <p class="text-gray-800">{{$data->description}}</p>
                    </div>
                    @endif

                </div>
            </div>
        </div>

    </div>

</div>
@endsection
