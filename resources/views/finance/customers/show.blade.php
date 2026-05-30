@extends('admin.layout.master')

@php
    $route = 'finance.customers';
@endphp

@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تفاصيل العميل</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
            </li>
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">العملاء</a>
            </li>
            <li class="breadcrumb-item text-gray-600">{{$data->name}}</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
        <a href="{{route($route.'.edit', $data->id)}}" class="btn btn-primary me-3">
            <i class="bi bi-pencil-square fs-4 me-1"></i> تعديل
        </a>
        <a href="{{route('finance.customers.wallet.index', $data->id)}}" class="btn btn-info me-3">
            <i class="bi bi-wallet2 fs-4 me-1"></i> المحفظة
        </a>
        <a href="{{route($route.'.index')}}" class="btn btn-light fw-bold">
            <i class="bi bi-arrow-right fs-4 me-1"></i> العودة
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="content flex-column-fluid" id="kt_content">

    <div class="row g-5 g-xl-10 mb-5">

        {{-- Customer Info Card --}}
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">بيانات العميل</span>
                    </h3>
                </div>
                <div class="card-body pt-5">

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">كود العميل</span>
                        <span class="fw-bold fs-6">{{$data->customer_code ?? '—'}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">الاسم</span>
                        <span class="fw-bold fs-6">{{$data->name}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">الهاتف</span>
                        <span class="fw-bold fs-6">{{$data->phone ?? '—'}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">البريد الإلكتروني</span>
                        <span class="fw-bold fs-6">{{$data->email ?? '—'}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">العنوان</span>
                        <span class="fw-bold fs-6">{{$data->address ?? '—'}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">الرقم الضريبي</span>
                        <span class="fw-bold fs-6">{{$data->tax_number ?? '—'}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">تاريخ الانضمام</span>
                        <span class="fw-bold fs-6">{{$data->join_date ? $data->join_date->format('Y-m-d') : '—'}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">حالة الحساب</span>
                        @php
                            $colors = ['active' => 'success', 'inactive' => 'warning', 'blocked' => 'danger'];
                            $labels = ['active' => 'نشط', 'inactive' => 'غير نشط', 'blocked' => 'محظور'];
                        @endphp
                        <span class="badge bg-light-{{$colors[$data->account_status] ?? 'secondary'}}">
                            {{$labels[$data->account_status] ?? $data->account_status}}
                        </span>
                    </div>

                    @if($data->notes)
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="mb-4">
                        <span class="text-gray-600 fw-semibold fs-6 d-block mb-2">ملاحظات</span>
                        <p class="fw-bold fs-6 text-gray-700">{{$data->notes}}</p>
                    </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- Wallet Summary Card --}}
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">حركات المحفظة</span>
                    </h3>
                    <div class="card-toolbar">
                        <a href="{{route('finance.customers.wallet.index', $data->id)}}" class="btn btn-sm btn-light-primary">
                            عرض الكل
                        </a>
                    </div>
                </div>
                <div class="card-body pt-5">

                    {{-- Balance Summary --}}
                    <div class="row g-4 mb-7">
                        <div class="col-md-4">
                            <div class="border border-dashed border-gray-300 rounded p-4 text-center">
                                <span class="fs-2 fw-bold text-success d-block">
                                    {{number_format($data->wallets->sum('credit'), 2)}}
                                </span>
                                <span class="text-gray-600 fs-7">إجمالي الدائن</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-dashed border-gray-300 rounded p-4 text-center">
                                <span class="fs-2 fw-bold text-danger d-block">
                                    {{number_format($data->wallets->sum('debit'), 2)}}
                                </span>
                                <span class="text-gray-600 fs-7">إجمالي المدين</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-dashed border-gray-300 rounded p-4 text-center">
                                <span class="fs-2 fw-bold text-primary d-block">
                                    {{number_format($data->wallet_balance, 2)}}
                                </span>
                                <span class="text-gray-600 fs-7">الرصيد الحالي</span>
                            </div>
                        </div>
                    </div>

                    {{-- Last 10 Transactions --}}
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-200 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="border-0">
                                    <th class="p-0 min-w-100px fw-bold text-gray-500">التاريخ</th>
                                    <th class="p-0 min-w-200px fw-bold text-gray-500">الوصف</th>
                                    <th class="p-0 min-w-80px fw-bold text-gray-500 text-end">مدين</th>
                                    <th class="p-0 min-w-80px fw-bold text-gray-500 text-end">دائن</th>
                                    <th class="p-0 min-w-80px fw-bold text-gray-500 text-end">الرصيد</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data->wallets->sortByDesc('date')->take(10) as $wallet)
                                <tr>
                                    <td>{{$wallet->date->format('Y-m-d')}}</td>
                                    <td>{{$wallet->description ?? '—'}}</td>
                                    <td class="text-end text-danger">{{number_format($wallet->debit, 2)}}</td>
                                    <td class="text-end text-success">{{number_format($wallet->credit, 2)}}</td>
                                    <td class="text-end fw-bold">{{number_format($wallet->balance, 2)}}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">لا توجد حركات مالية</td>
                                </tr>
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

@section('script')
@endsection
