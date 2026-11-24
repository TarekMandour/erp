@extends('admin.layout.master')
@php $route = 'finance.sales_returns'; @endphp

@section('css')
<style>
@media print {
    html, body { height: auto !important; overflow: visible !important; background: #fff !important; }
    body * { visibility: hidden !important; }
    #return-wrapper, #return-wrapper * { visibility: visible !important; }
    #return-wrapper { display: block !important; position: absolute !important; top: 0 !important; right: 0 !important; left: 0 !important; width: 100% !important; margin: 0 !important; padding: 0 !important; background: #fff !important; }
    .no-print { display: none !important; }
    table { width: 100% !important; border-collapse: collapse !important; }
    tr { page-break-inside: avoid !important; }
    @page { size: A4 portrait; margin: 10mm; }
}
</style>
@endsection

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7 no-print" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تفاصيل مرتجع مبيعات</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">مرتجعات المبيعات</a></li>
            <li class="breadcrumb-item text-gray-600">{{$data->return_number}}</li>
        </ul>
    </div>
    <div class="d-flex align-items-center gap-3 py-2">
        <button onclick="window.print()" class="btn btn-dark fw-bold">طباعة</button>
        @if($data->status === 'confirmed')
        <button type="button" class="btn btn-light-danger fw-bold" data-bs-toggle="modal" data-bs-target="#cancelModal">إلغاء المرتجع</button>
        @endif
        <a href="{{route($route.'.index')}}" class="btn btn-light fw-bold">رجوع</a>
    </div>
</div>
@endsection

@section('content')
<div class="content flex-column-fluid" id="kt_content">
    @foreach(['success','warning','error'] as $type)
        @if(session($type))
        <div class="alert alert-{{$type === 'error' ? 'danger' : $type}} alert-dismissible fade show mb-5 no-print">
            {{session($type)}}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    @endforeach
    @if($errors->any())
    <div class="alert alert-danger mb-5 no-print"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul></div>
    @endif

<div id="return-wrapper">
<div class="bg-white p-10 rounded shadow-sm">

    <div class="d-flex justify-content-between align-items-start mb-8 border-bottom pb-6">
        <div>
            @if(!empty($settings['site_logo']))<img src="{{asset('storage/'.$settings['site_logo'])}}" alt="logo" style="max-height:70px">@endif
        </div>
        <div class="text-end">
            <h2 class="fw-bold text-gray-900 fs-2">{{$settings['site_name'] ?? ''}}</h2>
            @if(!empty($settings['site_phone']))<div class="text-gray-600 fs-6">{{$settings['site_phone']}}</div>@endif
            @if(!empty($settings['site_address']))<div class="text-gray-600 fs-6">{{$settings['site_address']}}</div>@endif
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-8">
        <div>
            <h1 class="fw-bolder text-gray-900 fs-1">مرتجع مبيعات</h1>
            <div class="text-gray-600 fs-5 mt-1">رقم: <span class="fw-bold text-gray-900">{{$data->return_number}}</span></div>
            <div class="text-gray-600 fs-5">الطلب الأصلي: <a class="fw-bold" href="{{route('finance.orders.show', $data->order_id)}}">{{$data->order?->order_number}}</a></div>
        </div>
        <div class="text-end">
            <span class="badge {{$data->status_badge}} fs-6 py-3 px-5">{{$data->status_label}}</span>
            <div class="text-gray-600 fs-6 mt-2">التاريخ: <span class="fw-bold">{{$data->date?->format('Y-m-d')}}</span></div>
            @if($data->is_full)<div class="badge badge-light-info mt-2">مرتجع يكمل الطلب</div>@endif
        </div>
    </div>

    <div class="row mb-8">
        <div class="col-md-6">
            <div class="card border border-gray-300 h-100"><div class="card-body">
                <div class="fw-bold text-gray-900 fs-5 mb-1">{{$data->customer?->name}}</div>
                @if($data->customer?->customer_code)<div class="text-gray-600">الكود: {{$data->customer->customer_code}}</div>@endif
                @if($data->customer?->phone)<div class="text-gray-600">الجوال: {{$data->customer->phone}}</div>@endif
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card border border-gray-300 h-100"><div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td class="text-gray-600 w-50">المستودع</td><td class="fw-bold">{{$data->warehouse?->name}}</td></tr>
                    <tr><td class="text-gray-600">طريقة دفع الطلب</td><td class="fw-bold">{{$data->getPaymentTypeLabel()}}</td></tr>
                    <tr><td class="text-gray-600">المردود فعلياً</td><td class="fw-bold text-success">{{number_format((float)$data->paid, 2)}} ر.س</td></tr>
                    <tr><td class="text-gray-600">يخفض ذمة العميل / رصيد له</td><td class="fw-bold">{{number_format($data->receivable_reduction, 2)}} ر.س</td></tr>
                </table>
            </div></div>
        </div>
    </div>

    <h4 class="fw-bold text-gray-800 mb-4">البنود المرتجعة</h4>
    <div class="table-responsive mb-8">
        <table class="table table-bordered align-middle fs-6">
            <thead class="bg-light-dark">
                <tr class="fw-bold text-gray-800 text-center">
                    <th>#</th><th class="text-start">المنتج</th><th>المتغير</th><th>الكمية</th><th>سعر الوحدة</th>
                    <th>الخصم</th><th>حصة الكوبون/العرض</th><th>ض%</th><th>الضريبة</th><th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
            @foreach($data->items as $i => $item)
                <tr class="text-center">
                    <td>{{$i + 1}}</td>
                    <td class="text-start fw-bold">{{$item->product?->name}} @if($item->product?->sku)<div class="text-gray-500 fs-7">{{$item->product->sku}}</div>@endif</td>
                    <td>{{$item->variant?->sku ?? '—'}}</td>
                    <td class="fw-bold">{{number_format((float)$item->quantity, 3)}}</td>
                    <td>{{number_format((float)$item->unit_price, 2)}}</td>
                    <td class="text-danger">{{number_format((float)$item->discount, 2)}}</td>
                    <td class="text-danger">{{number_format((float)$item->invoice_discount, 2)}}</td>
                    <td>{{number_format((float)$item->tax_rate, 2)}}%</td>
                    <td class="text-warning">{{number_format((float)$item->tax, 2)}}</td>
                    <td class="fw-bold text-success">{{number_format((float)$item->total, 2)}} ر.س</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="row justify-content-end mb-8">
        <div class="col-md-5">
            <table class="table table-bordered fs-6 fw-semibold">
                <tr><td class="text-gray-600">المجموع الفرعي</td><td class="text-end fw-bold">{{number_format((float)$data->subtotal, 2)}} ر.س</td></tr>
                @if((float)$data->discount > 0)<tr><td class="text-gray-600">الخصم</td><td class="text-end text-danger">- {{number_format((float)$data->discount, 2)}} ر.س</td></tr>@endif
                @if((float)$data->coupon_discount > 0)<tr><td class="text-gray-600">خصم الكوبون</td><td class="text-end text-danger">- {{number_format((float)$data->coupon_discount, 2)}} ر.س</td></tr>@endif
                @if((float)$data->offer_discount > 0)<tr><td class="text-gray-600">خصم العرض</td><td class="text-end text-danger">- {{number_format((float)$data->offer_discount, 2)}} ر.س</td></tr>@endif
                @if((float)$data->tax > 0)<tr><td class="text-gray-600">الضريبة</td><td class="text-end text-warning">{{number_format((float)$data->tax, 2)}} ر.س</td></tr>@endif
                @if((float)$data->shipping_cost > 0)<tr><td class="text-gray-600">الشحن</td><td class="text-end">{{number_format((float)$data->shipping_cost, 2)}} ر.س</td></tr>@endif
                <tr class="bg-light-dark fs-5"><td class="fw-bolder">إجمالي المرتجع</td><td class="text-end fw-bolder text-success fs-4">{{number_format((float)$data->total, 2)}} ر.س</td></tr>
            </table>
        </div>
    </div>

    @if($data->notes)
    <div class="border border-gray-300 rounded p-5 mb-6"><h6 class="fw-bold text-gray-700 mb-2">الملاحظات</h6><p class="text-gray-600 mb-0">{{$data->notes}}</p></div>
    @endif

    <div class="text-center text-gray-400 fs-7 border-top pt-4 mt-6">تم الطباعة بواسطة النظام — {{now()->format('Y-m-d H:i:s')}}</div>
</div>
</div>

@if($data->status === 'confirmed')
<div class="modal fade no-print" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">إلغاء المرتجع {{$data->return_number}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{route($route.'.cancel')}}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{$data->id}}">
                <div class="modal-body">
                    <div class="alert alert-warning">سيُعكس قيد المرتجع ويُحذف أثره في محفظة العميل، وتخرج الكميات من المستودع، وتعود متاحة للإرجاع من الطلب.</div>
                    <label class="form-label">سبب الإلغاء (اختياري)</label>
                    <textarea name="notes" rows="2" class="form-control form-control-solid"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">رجوع</button>
                    <button type="submit" class="btn btn-danger fw-bold">إلغاء المرتجع</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
</div>
@endsection
