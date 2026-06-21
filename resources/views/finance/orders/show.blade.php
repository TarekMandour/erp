@extends('admin.layout.master')
@php $route = 'finance.orders'; @endphp

@section('css')
<style>
@media print {
    body > *:not(#order-invoice-wrapper) { display: none !important; }
    #order-invoice-wrapper { display: block !important; padding: 0 !important; margin: 0 !important; }
    #kt_header, #kt_toolbar, #kt_footer, #kt_aside, .no-print { display: none !important; }
    #order-invoice { padding: 20px; }
    .page-break { page-break-before: always; }
}
</style>
@endsection

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7 no-print" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تفاصيل طلب البيع</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">الطلبات</a></li>
            <li class="breadcrumb-item text-gray-600">{{$data->order_number}}</li>
        </ul>
    </div>
    <div class="d-flex align-items-center gap-3 py-2">
        <button onclick="window.print()" class="btn btn-dark fw-bold">
            <i class="ki-duotone ki-printer fs-3 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            طباعة
        </button>
        @if(!$data->is_locked)
        <a href="{{route($route.'.edit', $data->id)}}" class="btn btn-light-primary fw-bold">
            <i class="ki-duotone ki-pencil fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
            تعديل
        </a>
        @endif
        <a href="{{route($route.'.index')}}" class="btn btn-light fw-bold">
            <i class="ki-duotone ki-arrow-right fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
            رجوع
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="content flex-column-fluid" id="kt_content">
<div id="order-invoice-wrapper">
<div id="order-invoice" class="bg-white p-10 rounded shadow-sm">

    {{-- ── Company header ─────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-start mb-10 border-bottom pb-6">
        <div>
            @if(!empty($settings['site_logo']))
            <img src="{{asset('storage/'.$settings['site_logo'])}}" alt="logo" style="max-height:70px">
            @endif
        </div>
        <div class="text-end">
            <h2 class="fw-bold text-gray-900 fs-2">{{$settings['site_name'] ?? ''}}</h2>
            @if(!empty($settings['site_phone']))
            <div class="text-gray-600 fs-6"><i class="ki-duotone ki-phone fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>{{$settings['site_phone']}}</div>
            @endif
            @if(!empty($settings['site_email']))
            <div class="text-gray-600 fs-6"><i class="ki-duotone ki-sms fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>{{$settings['site_email']}}</div>
            @endif
            @if(!empty($settings['site_address']))
            <div class="text-gray-600 fs-6">{{$settings['site_address']}}</div>
            @endif
        </div>
    </div>

    {{-- ── Invoice title ──────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-8">
        <div>
            <h1 class="fw-bolder text-gray-900 fs-1">أمر البيع</h1>
            <div class="text-gray-600 fs-5 mt-1">رقم: <span class="fw-bold text-gray-900">{{$data->order_number}}</span></div>
        </div>
        <div class="text-end">
            <span class="badge {{$data->status_badge}} fs-6 py-3 px-5">{{$data->status_label}}</span>
            <div class="text-gray-600 fs-6 mt-2">التاريخ: <span class="fw-bold">{{$data->date?->format('Y-m-d')}}</span></div>
            @if($data->delivery_date)
            <div class="text-gray-600 fs-6">موعد التسليم: <span class="fw-bold">{{$data->delivery_date?->format('Y-m-d')}}</span></div>
            @endif
        </div>
    </div>

    {{-- ── Customer + order info ──────────────────────────────── --}}
    <div class="row mb-8">
        <div class="col-md-6">
            <div class="card border border-gray-300 h-100">
                <div class="card-header bg-light-dark">
                    <h5 class="card-title fw-bold mb-0 text-gray-800">بيانات العميل</h5>
                </div>
                <div class="card-body">
                    <div class="fw-bold text-gray-900 fs-5 mb-1">{{$data->customer?->name}}</div>
                    @if($data->customer?->customer_code)
                    <div class="text-gray-600 fs-6">الكود: {{$data->customer->customer_code}}</div>
                    @endif
                    @if($data->customer?->phone)
                    <div class="text-gray-600 fs-6">الجوال: {{$data->customer->phone}}</div>
                    @endif
                    @if($data->customer?->email)
                    <div class="text-gray-600 fs-6">البريد: {{$data->customer->email}}</div>
                    @endif
                    @if($data->customer?->address)
                    <div class="text-gray-600 fs-6">العنوان: {{$data->customer->address}}</div>
                    @endif
                    @if($data->shipping_address)
                    <div class="text-gray-600 fs-6 mt-1">عنوان الشحن: <span class="text-gray-800">{{$data->shipping_address}}</span></div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border border-gray-300 h-100">
                <div class="card-header bg-light-dark">
                    <h5 class="card-title fw-bold mb-0 text-gray-800">تفاصيل الطلب</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-gray-600 fw-semibold w-50">المستودع</td>
                            <td class="fw-bold text-gray-800">{{$data->warehouse?->name}}</td>
                        </tr>
                        <tr>
                            <td class="text-gray-600 fw-semibold">طريقة الدفع</td>
                            <td class="fw-bold text-gray-800">{{\App\Models\Finance\Order::$paymentTypeLabels[$data->payment_type] ?? $data->payment_type}}</td>
                        </tr>
                        <tr>
                            <td class="text-gray-600 fw-semibold">المدفوع</td>
                            <td class="fw-bold text-success">{{number_format((float)$data->paid, 2)}} ر.س</td>
                        </tr>
                        <tr>
                            <td class="text-gray-600 fw-semibold">المتبقي</td>
                            <td class="fw-bold {{(float)$data->remaining > 0 ? 'text-danger' : 'text-success'}}">{{number_format((float)$data->remaining, 2)}} ر.س</td>
                        </tr>
                        @if((float)$data->shipping_cost > 0)
                        <tr>
                            <td class="text-gray-600 fw-semibold">تكلفة الشحن</td>
                            <td class="fw-bold text-gray-800">{{number_format((float)$data->shipping_cost, 2)}} ر.س</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Items table ────────────────────────────────────────── --}}
    <div class="mb-8">
        <h4 class="fw-bold text-gray-800 mb-4">تفاصيل المنتجات</h4>
        @if($pricingMode === 'inclusive')
        <div class="alert alert-warning py-2 px-4 mb-3 fs-7">
            <i class="ki-duotone ki-information-5 fs-5 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            <strong>السعر شامل الضريبة:</strong> الأسعار المدخلة تشمل قيمة الضريبة — يتم استخراجها تلقائياً.
        </div>
        @else
        <div class="alert alert-info py-2 px-4 mb-3 fs-7">
            <i class="ki-duotone ki-information-5 fs-5 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            <strong>السعر غير شامل الضريبة:</strong> الضريبة مُضافة فوق سعر المنتج.
        </div>
        @endif
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle fs-6">
                <thead class="bg-light-dark">
                    <tr class="fw-bold text-gray-800 text-center">
                        <th>#</th>
                        <th class="text-start">المنتج</th>
                        <th>المتغير</th>
                        <th>الكمية</th>
                        <th>سعر الوحدة</th>
                        <th>السعر قبل الضريبة</th>
                        <th>الخصم</th>
                        <th>ض%</th>
                        <th>الضريبة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data->items as $i => $item)
                    @php
                        $qty      = (float)$item->quantity;
                        $price    = (float)$item->unit_price;
                        $disc     = (float)$item->discount;
                        $taxRate  = (float)$item->tax_rate;
                        if ($pricingMode === 'inclusive') {
                            // السعر قبل الضريبة = price / (1 + rate/100)
                            $unitPriceBefore = $taxRate > 0 ? round($price / (1 + $taxRate / 100), 4) : $price;
                            $gross           = $qty * $price - $disc;
                            $taxAmt          = round($gross * $taxRate / (100 + $taxRate), 2);
                            $rowTot          = $gross;
                        } else {
                            $unitPriceBefore = $price;
                            $gross           = $qty * $price - $disc;
                            $taxAmt          = round($gross * $taxRate / 100, 2);
                            $rowTot          = $gross + $taxAmt;
                        }
                    @endphp
                    <tr>
                        <td class="text-center text-gray-600">{{$i + 1}}</td>
                        <td>
                            <div class="fw-bold text-gray-900">{{$item->product?->name}}</div>
                            @if($item->product?->sku)<div class="text-gray-500 fs-7">{{$item->product->sku}}</div>@endif
                        </td>
                        <td class="text-center text-gray-700">
                            {{$item->variant ? $item->variant->sku : '—'}}
                        </td>
                        <td class="text-center fw-bold">{{number_format($qty, 3)}}</td>
                        <td class="text-center">{{number_format($price, 2)}}</td>
                        <td class="text-center text-gray-600">{{number_format($unitPriceBefore, 2)}}</td>
                        <td class="text-center text-danger">{{number_format($disc, 2)}}</td>
                        <td class="text-center">{{number_format($taxRate, 2)}}%</td>
                        <td class="text-center text-warning">{{number_format($taxAmt, 2)}}</td>
                        <td class="text-center fw-bold text-success">{{number_format($rowTot, 2)}} ر.س</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Totals summary ─────────────────────────────────────── --}}
    <div class="row justify-content-end mb-8">
        <div class="col-md-5">
            <table class="table table-bordered fs-6 fw-semibold">
                <tr>
                    <td class="text-gray-600">المجموع الفرعي</td>
                    <td class="text-end fw-bold">{{number_format((float)$data->subtotal, 2)}} ر.س</td>
                </tr>
                @if((float)$data->discount > 0)
                <tr>
                    <td class="text-gray-600">الخصم الكلي</td>
                    <td class="text-end fw-bold text-danger">- {{number_format((float)$data->discount, 2)}} ر.س</td>
                </tr>
                @endif
                @if((float)$data->tax > 0)
                <tr>
                    <td class="text-gray-600">الضريبة</td>
                    <td class="text-end fw-bold text-warning">{{number_format((float)$data->tax, 2)}} ر.س</td>
                </tr>
                @endif
                @if((float)$data->shipping_cost > 0)
                <tr>
                    <td class="text-gray-600">تكلفة الشحن</td>
                    <td class="text-end fw-bold">{{number_format((float)$data->shipping_cost, 2)}} ر.س</td>
                </tr>
                @endif
                <tr class="bg-light-dark fs-5">
                    <td class="fw-bolder text-gray-900">الإجمالي</td>
                    <td class="text-end fw-bolder text-success fs-4">{{number_format((float)$data->total, 2)}} ر.س</td>
                </tr>
                <tr>
                    <td class="text-gray-600">المدفوع</td>
                    <td class="text-end fw-bold text-success">{{number_format((float)$data->paid, 2)}} ر.س</td>
                </tr>
                <tr>
                    <td class="text-gray-600">المتبقي</td>
                    <td class="text-end fw-bold {{(float)$data->remaining > 0 ? 'text-danger' : 'text-success'}}">{{number_format((float)$data->remaining, 2)}} ر.س</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ── Notes ─────────────────────────────────────────────── --}}
    @if($data->notes)
    <div class="border border-gray-300 rounded p-5 mb-6">
        <h6 class="fw-bold text-gray-700 mb-2">الملاحظات</h6>
        <p class="text-gray-600 mb-0">{{$data->notes}}</p>
    </div>
    @endif

    {{-- ── Print footer ──────────────────────────────────────── --}}
    <div class="text-center text-gray-400 fs-7 border-top pt-4 mt-6">
        تم الطباعة بواسطة النظام — {{now()->format('Y-m-d H:i:s')}}
    </div>

</div>{{-- /#order-invoice --}}
</div>{{-- /#order-invoice-wrapper --}}
</div>{{-- /content --}}
@endsection
