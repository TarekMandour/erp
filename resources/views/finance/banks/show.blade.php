@extends('admin.layout.master')
@php $route = 'finance.banks'; @endphp
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تفاصيل البنك</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">البنوك</a></li>
            <li class="breadcrumb-item text-gray-600">{{$data->name}}</li>
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
    <div class="row g-5 mb-5">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <h3 class="card-title"><span class="card-label fw-bold text-gray-900">بيانات البنك</span></h3>
                </div>
                <div class="card-body pt-5">
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">الاسم</span>
                        <span class="fw-bold fs-6">{{$data->name}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">عدد الحسابات</span>
                        <span class="badge bg-light-primary fw-bold fs-6">{{$data->accounts->count()}}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <h3 class="card-title"><span class="card-label fw-bold text-gray-900">الحسابات البنكية</span></h3>
                    <div class="card-toolbar">
                        <a href="{{route('finance.bank_accounts.create')}}?bank_id={{$data->id}}" class="btn btn-sm btn-dark">
                            <i class="ki-duotone ki-plus fs-2"></i> حساب جديد
                        </a>
                    </div>
                </div>
                <div class="card-body pt-5">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle fs-6">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase">
                                    <th>رقم الحساب</th>
                                    <th>العملة</th>
                                    <th>الرصيد</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data->accounts as $account)
                                <tr>
                                    <td class="fw-semibold">{{$account->account_number}}</td>
                                    <td class="text-muted">{{$account->currency}}</td>
                                    <td class="fw-bold text-{{$account->balance >= 0 ? 'success' : 'danger'}}">
                                        {{number_format($account->balance, 2)}}
                                    </td>
                                    <td>
                                        <a href="{{route('finance.bank_accounts.show', $account->id)}}" class="btn btn-xs btn-icon btn-info"><i class="bi bi-eye fs-5"></i></a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-5">لا توجد حسابات</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
