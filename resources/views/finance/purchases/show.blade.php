@extends('admin.layout.master')
@php $route = 'finance.purchases'; @endphp
@section('css')
<style>
/* ── Screen: print-area wrapper ──────────────────────── */
#purchase-invoice {
    background: #fff;
    color: #000;
    font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
    direction: rtl;
}

/* ── Print styles ────────────────────────────────────── */
@media print {
    /* Hide everything except the invoice */
    body * { visibility: hidden !important; }
    #purchase-invoice,
    #purchase-invoice * { visibility: visible !important; }
    #purchase-invoice {
        position: fixed !important;
        top: 0; left: 0; right: 0; bottom: 0;
        width: 100%;
        margin: 0;
        padding: 24px;
        font-size: 12px;
        box-shadow: none !important;
        border: none !important;
    }
    .no-print { display: none !important; }
    table { width: 100% !important; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    .badge { border: 1px solid #ccc !important; background: transparent !important; color: #000 !important; }
}
</style>
@endsection

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">فاتورة شراء</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">المشتريات</a></li>
            <li class="breadcrumb-item text-gray-600">{{$data->purchase_number}}</li>
        </ul>
    </div>
    <div class="d-flex align-items-center gap-3 py-2 no-print">
        <button onclick="window.print()" class="btn btn-success fw-bold">
            <i class="ki-duotone ki-printer fs-3 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            طباعة
        </button>
        <a href="{{route($route.'.edit', $data->id)}}" class="btn btn-primary fw-bold">
            <i class="ki-duotone ki-pencil fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
            تعديل
        </a>
        <a href="{{route($route.'.index')}}" class="btn btn-light fw-bold">
            <i class="ki-duotone ki-arrow-right fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
            العودة
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="content flex-column-fluid" id="kt_content">
    <div class="card card-flush shadow-sm">
        <div class="card-body p-9" id="purchase-invoice">

            {{-- ── Invoice Header ──────────────────────────────── --}}
            <div class="d-flex justify-content-between align-items-start mb-8">
                {{-- Company info --}}
                <div>
                    @if($settings && $settings->getMedia('logo')->count())
                        <img src="{{$settings->getFirstMediaUrl('logo')}}" alt="{{$settings->append_name}}" class="mb-3" style="max-height:70px; max-width:200px;">
                    @endif
                    @if($settings)
                        <div class="fw-bolder fs-4 text-gray-900">{{$settings->append_name}}</div>
                        @if($settings->phone ?? false)
                            <div class="text-gray-600 fs-7">هاتف: {{$settings->phone}}</div>
                        @endif
                        @if($settings->email ?? false)
                            <div class="text-gray-600 fs-7">{{$settings->email}}</div>
                        @endif
                        @if($settings->address ?? false)
                            <div class="text-gray-600 fs-7">{{$settings->address}}</div>
                        @endif
                    @endif
                </div>

                {{-- Invoice title + number --}}
                <div class="text-end">
                    <div class="fs-1 fw-bolder text-gray-900 mb-2">أمر شراء</div>
                    <div class="fs-5 fw-bold text-primary">{{$data->purchase_number}}</div>
                    <div class="mt-3 text-gray-600 fs-7">
                        <span class="fw-semibold">التاريخ:</span> {{$data->date->format('Y/m/d')}}
                    </div>
                    @if($data->due_date)
                    <div class="text-gray-600 fs-7">
                        <span class="fw-semibold">الاستحقاق:</span> {{$data->due_date->format('Y/m/d')}}
                    </div>
                    @endif
                    <div class="mt-2">
                        <span class="badge {{$data->status_badge}} fs-7">{{$data->status_label}}</span>
                    </div>
                </div>
            </div>

            <div class="separator separator-dashed mb-7"></div>

            {{-- ── Supplier + Warehouse ────────────────────────── --}}
            <div class="row mb-8">
                <div class="col-md-6">
                    <div class="bg-light rounded p-5">
                        <div class="fw-bold text-gray-700 fs-7 text-uppercase mb-3">بيانات المورد</div>
                        @if($data->supplier)
                            <div class="fw-bolder fs-5 text-gray-900">
                                {{$data->supplier->company_name ?: $data->supplier->name}}
                            </div>
                            @if($data->supplier->company_name && $data->supplier->name)
                                <div class="text-gray-600 fs-7">{{$data->supplier->name}}</div>
                            @endif
                            @if($data->supplier->phone ?? false)
                                <div class="text-gray-600 fs-7">هاتف: {{$data->supplier->phone}}</div>
                            @endif
                            @if($data->supplier->email ?? false)
                                <div class="text-gray-600 fs-7">{{$data->supplier->email}}</div>
                            @endif
                            @if($data->supplier->address ?? false)
                                <div class="text-gray-600 fs-7">{{$data->supplier->address}}</div>
                            @endif
                            @if($data->supplier->tax_number ?? false)
                                <div class="text-gray-600 fs-7">الرقم الضريبي: {{$data->supplier->tax_number}}</div>
                            @endif
                        @else
                            <div class="text-muted">—</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="bg-light rounded p-5">
                        <div class="fw-bold text-gray-700 fs-7 text-uppercase mb-3">بيانات الفاتورة</div>
                        <table class="table table-sm table-borderless mb-0 fs-7">
                            <tr>
                                <td class="text-gray-600 fw-semibold pe-4">المستودع</td>
                                <td class="fw-bold text-gray-900">{{$data->warehouse?->name ?? '—'}}</td>
                            </tr>
                            <tr>
                                <td class="text-gray-600 fw-semibold pe-4">نوع الدفع</td>
                                <td class="fw-bold text-gray-900">{{\App\Models\Finance\Purchase::$paymentTypeLabels[$data->payment_type] ?? $data->payment_type}}</td>
                            </tr>
                            <tr>
                                <td class="text-gray-600 fw-semibold pe-4">المدفوع</td>
                                <td class="fw-bold text-success">{{number_format((float)$data->paid, 2)}} ر.س</td>
                            </tr>
                            <tr>
                                <td class="text-gray-600 fw-semibold pe-4">المتبقي</td>
                                <td class="fw-bold {{(float)$data->remaining > 0 ? 'text-danger' : 'text-success'}}">
                                    {{number_format((float)$data->remaining, 2)}} ر.س
                                </td>
                            </tr>
                            <tr>
                                <td class="text-gray-600 fw-semibold pe-4">تاريخ الإنشاء</td>
                                <td class="text-gray-700">{{$data->created_at->format('Y/m/d H:i')}}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── Items Table ──────────────────────────────────── --}}
            <div class="table-responsive mb-7">
                <table class="table table-bordered align-middle fs-7 mb-0" style="border-color:#dee2e6;">
                    <thead class="table-dark">
                        <tr class="fw-bold text-white text-center">
                            <th class="text-start">#</th>
                            <th class="text-start">المنتج</th>
                            <th class="text-start">المتغير</th>
                            <th>الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>الخصم</th>
                            <th>ض%</th>
                            <th>الضريبة</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data->items as $index => $item)
                        <tr>
                            <td>{{$index + 1}}</td>
                            <td>
                                <div class="fw-bold text-gray-900">{{$item->product?->name ?? '—'}}</div>
                                @if($item->product?->sku)
                                    <div class="text-muted fs-8">{{$item->product->sku}}</div>
                                @endif
                            </td>
                            <td class="text-gray-600">
                                {{$item->variant?->sku ?? '—'}}
                            </td>
                            <td class="text-center fw-semibold">{{number_format((float)$item->quantity, 3)}}</td>
                            <td class="text-center">{{number_format((float)$item->unit_cost, 2)}}</td>
                            <td class="text-center text-danger">{{number_format((float)$item->discount, 2)}}</td>
                            <td class="text-center">{{number_format((float)$item->tax_rate, 2)}}%</td>
                            <td class="text-center text-warning fw-semibold">
                                @php
                                    $lineBase = (float)$item->quantity * (float)$item->unit_cost - (float)$item->discount;
                                    $lineTax  = $lineBase * (float)$item->tax_rate / 100;
                                @endphp
                                {{number_format($lineTax, 2)}}
                            </td>
                            <td class="text-center fw-bold text-gray-900">{{number_format((float)$item->total, 2)}}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">لا توجد بنود</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ── Totals ───────────────────────────────────────── --}}
            <div class="row justify-content-end mb-7">
                <div class="col-md-5 col-lg-4">
                    <table class="table table-sm table-bordered fs-7 mb-0" style="border-color:#dee2e6;">
                        <tr>
                            <td class="text-gray-600 fw-semibold bg-light">المجموع الفرعي</td>
                            <td class="text-end fw-bold">{{number_format((float)$data->subtotal, 2)}} ر.س</td>
                        </tr>
                        <tr>
                            <td class="text-gray-600 fw-semibold bg-light">الخصم</td>
                            <td class="text-end fw-bold text-danger">{{number_format((float)$data->discount, 2)}} ر.س</td>
                        </tr>
                        <tr>
                            <td class="text-gray-600 fw-semibold bg-light">الضريبة</td>
                            <td class="text-end fw-bold text-warning">{{number_format((float)$data->tax, 2)}} ر.س</td>
                        </tr>
                        <tr class="fs-6">
                            <td class="fw-bolder bg-light">الإجمالي</td>
                            <td class="text-end fw-bolder text-success fs-5">{{number_format((float)$data->total, 2)}} ر.س</td>
                        </tr>
                        <tr>
                            <td class="text-gray-600 fw-semibold bg-light">المدفوع</td>
                            <td class="text-end fw-bold text-primary">{{number_format((float)$data->paid, 2)}} ر.س</td>
                        </tr>
                        <tr>
                            <td class="fw-bold bg-light">المتبقي</td>
                            <td class="text-end fw-bolder {{(float)$data->remaining > 0 ? 'text-danger' : 'text-success'}}">
                                {{number_format((float)$data->remaining, 2)}} ر.س
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- ── Notes ───────────────────────────────────────── --}}
            @if($data->notes)
            <div class="bg-light rounded p-5 mb-7">
                <div class="fw-bold text-gray-700 fs-7 mb-2">الملاحظات</div>
                <div class="text-gray-800 fs-7">{{$data->notes}}</div>
            </div>
            @endif

            {{-- ── Footer ──────────────────────────────────────── --}}
            <div class="separator separator-dashed my-5"></div>
            <div class="d-flex justify-content-between align-items-center text-gray-500 fs-8">
                <span>تاريخ الطباعة: {{now()->format('Y/m/d H:i')}}</span>
                <span>{{$settings?->append_name ?? ''}}</span>
            </div>

        </div>
    </div>
</div>
@endsection
