@extends('admin.layout.master')
@php $route = 'finance.sales_returns'; @endphp

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">مرتجع مبيعات جديد</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">مرتجعات المبيعات</a></li>
            <li class="breadcrumb-item text-gray-600">جديد</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
        <a href="{{route($route.'.index')}}" class="btn btn-light fw-bold">رجوع</a>
    </div>
</div>
@endsection

@section('content')
<div class="content flex-column-fluid" id="kt_content">
    @foreach(['success','warning','error'] as $type)
        @if(session($type))
        <div class="alert alert-{{$type === 'error' ? 'danger' : $type}} alert-dismissible fade show mb-5">
            {{session($type)}}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    @endforeach
    @if($errors->any())
    <div class="alert alert-danger mb-5">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul>
    </div>
    @endif

@if(!$order)
    {{-- ── اختيار الطلب الأصلي ───────────────────────────────── --}}
    <div class="card">
        <div class="card-body">
            <h4 class="fw-bold mb-5">اختر الطلب الأصلي</h4>
            <form method="GET" action="{{route($route.'.create')}}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label required">رقم طلب البيع</label>
                    <input type="text" name="order_number" class="form-control form-control-solid" placeholder="SO-0001" required autofocus>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-dark fw-bold">متابعة</button>
                </div>
            </form>
            <div class="text-muted fs-7 mt-4">المرتجع مستند مستقل يُنشأ من طلب بيع مؤكد، ولا يغيّر الطلب الأصلي.</div>
        </div>
    </div>
@else
    @php
        $payLabels = \App\Models\Finance\Order::$paymentTypeLabels;
        $bankLike  = in_array($order->payment_type, ['wallet','bank_online','bank_direct'], true);
    @endphp

    <div class="card mb-5">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-3"><div class="text-gray-600">الطلب</div><div class="fw-bold fs-5"><a href="{{route('finance.orders.show', $order->id)}}">{{$order->order_number}}</a></div></div>
                <div class="col-md-3"><div class="text-gray-600">العميل</div><div class="fw-bold fs-5">{{$order->customer?->name}}</div></div>
                <div class="col-md-2"><div class="text-gray-600">طريقة الدفع</div><div class="fw-bold fs-5">{{$payLabels[$order->payment_type] ?? $order->payment_type}}</div></div>
                <div class="col-md-2"><div class="text-gray-600">إجمالي الطلب</div><div class="fw-bold fs-5">{{number_format((float)$order->total, 2)}}</div></div>
                <div class="col-md-2"><div class="text-gray-600">المدفوع</div><div class="fw-bold fs-5">{{number_format((float)$order->paid, 2)}}</div></div>
            </div>
        </div>
    </div>

    @if($hasOffer)
    <div class="alert alert-warning mb-5">
        <strong>هذا الطلب استُخدم فيه عرض{{ $order->offer ? ' («' . $order->offer->name . '»)' : '' }}:</strong>
        لا يمكن إرجاعه جزئياً، يجب إرجاع كل بنوده بكامل كمياتها في مرتجع واحد.
        @if($blockedByPrior) <div class="fw-bold mt-1">سبق تسجيل مرتجع مؤكد لهذا الطلب، فلا يمكن إنشاء مرتجع آخر له.</div> @endif
    </div>
    @endif

    @php $anyRemaining = collect($ctx['rows'])->contains(fn($r) => $r['remaining'] > 0.0005); @endphp
    @if(!$anyRemaining)
    <div class="alert alert-info">تم إرجاع كل كميات هذا الطلب سابقاً.</div>
    @elseif(!$blockedByPrior)
    <form method="POST" action="{{route($route.'.store')}}" id="return-form">
        @csrf
        <input type="hidden" name="order_id" value="{{$order->id}}">

        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title fw-bold">البنود المرتجعة</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle fs-6">
                        <thead class="bg-light-dark">
                            <tr class="fw-bold text-gray-800 text-center">
                                <th>#</th><th class="text-start">المنتج</th><th>المتغير</th>
                                <th>المباع</th><th>سبق إرجاعه</th><th>المتاح</th>
                                <th>سعر الوحدة</th><th style="width:150px">الكمية المرتجعة</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($ctx['rows'] as $id => $row)
                            @php $it = $row['item']; @endphp
                            <tr class="text-center">
                                <td>{{$loop->iteration}}</td>
                                <td class="text-start fw-bold">{{$it->product?->name}} @if($it->product?->sku)<div class="text-gray-500 fs-7">{{$it->product->sku}}</div>@endif</td>
                                <td>{{$it->variant?->sku ?? '—'}}</td>
                                <td>{{rtrim(rtrim(number_format($row['sold'], 3, '.', ''), '0'), '.')}}</td>
                                <td>{{rtrim(rtrim(number_format($row['returned'], 3, '.', ''), '0'), '.') ?: '0'}}</td>
                                <td class="fw-bold">{{rtrim(rtrim(number_format($row['remaining'], 3, '.', ''), '0'), '.') ?: '0'}}</td>
                                <td>{{number_format((float)$it->unit_price, 2)}}</td>
                                <td>
                                    @if($row['remaining'] > 0.0005)
                                    <input type="number" step="0.001" min="0" max="{{$row['remaining']}}"
                                           name="quantities[{{$id}}]" class="form-control form-control-solid text-center qty-input"
                                           value="{{ old('quantities.'.$id, $hasOffer ? $row['remaining'] : '') }}"
                                           @if($hasOffer) readonly @endif>
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label required">تاريخ المرتجع</label>
                                <input type="date" name="date" class="form-control form-control-solid" value="{{old('date', now()->toDateString())}}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">المبلغ المردود فعلياً للعميل</label>
                                <input type="number" step="0.01" min="0" name="paid" id="paid" class="form-control form-control-solid" value="{{old('paid')}}" placeholder="يُحسب تلقائياً">
                                <div class="text-muted fs-7 mt-1">
                                    الدفع الفوري: الافتراضي هو الإجمالي حتى حد المدفوع. الآجل/الأقساط/الشيك: صفر ويُسجَّل رصيداً للعميل في محفظته.
                                    @if(in_array($order->payment_type, ['credit','installments']))
                                    <br>الرد يخرج من الصندوق.
                                    @endif
                                </div>
                            </div>
                            @if((float)$order->shipping_cost > 0 && !$hasOffer)
                            <div class="col-12">
                                <div class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="include_shipping" id="include_shipping" value="1" @checked(old('include_shipping'))>
                                    <label class="form-check-label" for="include_shipping">إرجاع تكلفة الشحن ({{number_format((float)$order->shipping_cost, 2)}}) — تُرجَع تلقائياً إذا أكمل المرتجع كل الطلب</label>
                                </div>
                            </div>
                            @endif
                            <div class="col-12">
                                <label class="form-label">ملاحظات</label>
                                <textarea name="notes" rows="2" class="form-control form-control-solid">{{old('notes')}}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4">ملخص المرتجع <span class="badge badge-light-info" id="full-badge" style="display:none">يكمل الطلب</span></h5>
                        <div id="preview-error" class="alert alert-danger py-2" style="display:none"></div>
                        <table class="table table-sm fs-6 fw-semibold mb-0">
                            <tr><td class="text-gray-600">المجموع الفرعي</td><td class="text-end" id="p-subtotal">0.00</td></tr>
                            <tr><td class="text-gray-600">الخصم</td><td class="text-end text-danger" id="p-discount">0.00</td></tr>
                            <tr><td class="text-gray-600">حصة الكوبون</td><td class="text-end text-danger" id="p-coupon">0.00</td></tr>
                            <tr><td class="text-gray-600">خصم العرض</td><td class="text-end text-danger" id="p-offer">0.00</td></tr>
                            <tr><td class="text-gray-600">الضريبة</td><td class="text-end text-warning" id="p-tax">0.00</td></tr>
                            <tr><td class="text-gray-600">الشحن</td><td class="text-end" id="p-shipping">0.00</td></tr>
                            <tr class="bg-light-dark fs-5"><td class="fw-bolder">إجمالي المرتجع</td><td class="text-end fw-bolder text-success" id="p-total">0.00</td></tr>
                            <tr><td class="text-gray-600">المردود فعلياً</td><td class="text-end" id="p-paid">0.00</td></tr>
                            <tr><td class="text-gray-600">يخفض ذمة العميل / رصيد له</td><td class="text-end" id="p-recv">0.00</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-6">
            <button type="submit" class="btn btn-success fw-bold" id="submit-btn"
                    onclick="return confirm('تأكيد المرتجع؟ سيُرحَّل فوراً (مخزون + محفظة + قيود) ولا يُعدَّل، ويمكن إلغاؤه لاحقاً.')">
                تأكيد المرتجع
            </button>
        </div>
    </form>
    @endif
@endif
</div>
@endsection

@if($order && $anyRemaining && !$blockedByPrior)
@section('script')
<script>
$(function () {
    var timer = null, paidTouched = {{ old('paid') !== null && old('paid') !== '' ? 'true' : 'false' }};
    var fmt = function (n) { return Number(n || 0).toFixed(2); };

    $('#paid').on('input', function () { paidTouched = ($(this).val() !== ''); recvUpdate(); });

    function recvUpdate() {
        var total = parseFloat($('#p-total').text()) || 0;
        var paid = $('#paid').val() !== '' ? parseFloat($('#paid').val()) || 0 : parseFloat($('#p-paid').data('default') || 0);
        $('#p-paid').text(fmt(paid));
        $('#p-recv').text(fmt(total - paid));
    }

    function preview() {
        $.ajax({
            url: "{{ route($route.'.preview') }}", method: 'POST',
            data: $('#return-form').serialize() + '&_token={{ csrf_token() }}',
            success: function (r) {
                $('#preview-error').hide();
                var h = r.header;
                $('#p-subtotal').text(fmt(h.subtotal)); $('#p-discount').text(fmt(h.discount));
                $('#p-coupon').text(fmt(h.coupon_discount)); $('#p-offer').text(fmt(h.offer_discount));
                $('#p-tax').text(fmt(h.tax)); $('#p-shipping').text(fmt(h.shipping_cost));
                $('#p-total').text(fmt(h.total));
                $('#p-paid').data('default', r.default_refund);
                $('#full-badge').toggle(!!r.is_full);
                if (!paidTouched) { $('#paid').attr('placeholder', fmt(r.default_refund)); }
                recvUpdate();
            },
            error: function (x) {
                var m = x.responseJSON && x.responseJSON.message ? x.responseJSON.message : 'تعذّر حساب المرتجع';
                $('#preview-error').text(m).show();
            }
        });
    }

    function schedule() {
        clearTimeout(timer);
        var any = false;
        $('.qty-input').each(function () { if (parseFloat($(this).val()) > 0) any = true; });
        if (!any) { $('#preview-error').hide(); return; }
        timer = setTimeout(preview, 250);
    }

    $('.qty-input, #include_shipping').on('input change', schedule);
    schedule();
});
</script>
@endsection
@endif
