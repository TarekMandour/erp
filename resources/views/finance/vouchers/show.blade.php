@extends('admin.layout.master')
@php $route = 'finance.vouchers'; @endphp
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تفاصيل السند</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">السندات</a></li>
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
                    <h3 class="card-title">
                        <span class="card-label fw-bold text-gray-900">بيانات السند #{{$data->id}}</span>
                    </h3>
                </div>
                <div class="card-body pt-5">
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">نوع السند</span>
                        <span class="badge badge-light-{{$data->type === 'payment' ? 'danger' : 'success'}} fw-bold fs-6">
                            {{$data->type_name}}
                        </span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">نوع الدفع</span>
                        <span class="fw-bold fs-6">{{$data->payment_type_name}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">نوع الطرف</span>
                        <span class="fw-bold fs-6">{{$data->party_type_name}}</span>
                    </div>
                    @if($data->party_type === 'customer' && $data->party_id && isset($customers[$data->party_id]))
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">العميل</span>
                        <span class="fw-bold fs-6">{{$customers[$data->party_id]->name}} ({{$customers[$data->party_id]->customer_code}})</span>
                    </div>
                    @elseif($data->party_type === 'supplier' && $data->party_id && isset($suppliers[$data->party_id]))
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">المورد</span>
                        <span class="fw-bold fs-6">{{$suppliers[$data->party_id]->name}} — {{$suppliers[$data->party_id]->company_name}}</span>
                    </div>
                    @elseif($data->party_type === 'other' && $data->party_name)
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">اسم الطرف</span>
                        <span class="fw-bold fs-6">{{$data->party_name}}</span>
                    </div>
                    @endif
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">المبلغ الإجمالي</span>
                        <strong class="fs-4 text-{{$data->type === 'payment' ? 'danger' : 'success'}}">
                            {{number_format($data->total_amount, 2)}}
                        </strong>
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
                    @if($data->creator)
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">بواسطة</span>
                        <span class="fw-semibold fs-6 text-muted">{{$data->creator->name}}</span>
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
