@extends('admin.layout.master')
@php $route = 'finance.inventory'; @endphp
@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تقرير المنتج</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">المخزون</a></li>
            <li class="breadcrumb-item text-gray-600">تقرير: {{$product->name}}</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
        <a href="{{route($route.'.index')}}" class="btn btn-light">
            <i class="bi bi-arrow-right me-1"></i> رجوع للمخزون
        </a>
    </div>
</div>
@endsection
@section('content')
<div class="content flex-column-fluid" id="kt_content">

    {{-- ── Card 1: Product Info ─────────────────────────── --}}
    <div class="card mb-5">
        <div class="card-header border-0 pt-6">
            <div class="card-title"><h2>بيانات المنتج</h2></div>
            <div class="card-toolbar">
                @if($product->status === 'active')
                    <span class="badge badge-light-success fs-7">نشط</span>
                @else
                    <span class="badge badge-light-danger fs-7">غير نشط</span>
                @endif
            </div>
        </div>
        <div class="card-body py-4">
            <div class="row g-5">
                <div class="col-md-6">
                    <table class="table table-borderless fs-6">
                        <tr>
                            <td class="text-muted w-150px">اسم المنتج</td>
                            <td class="fw-bold">{{$product->name}}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">الرمز (SKU)</td>
                            <td class="fw-semibold font-monospace">{{$product->sku}}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">الفئة</td>
                            <td>{{$product->category->name ?? '—'}}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">العلامة التجارية</td>
                            <td>{{$product->brand->name ?? '—'}}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">وحدة القياس</td>
                            <td>{{$product->unit->name ?? '—'}}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless fs-6">
                        <tr>
                            <td class="text-muted w-150px">سعر الشراء</td>
                            <td class="fw-bold text-info">{{number_format((float)$product->purchase_price, 2)}} ر.س</td>
                        </tr>
                        <tr>
                            <td class="text-muted">سعر البيع</td>
                            <td class="fw-bold text-success">{{number_format((float)$product->selling_price, 2)}} ر.س</td>
                        </tr>
                        <tr>
                            <td class="text-muted">نسبة الضريبة</td>
                            <td>{{number_format((float)$product->tax_rate, 2)}}%</td>
                        </tr>
                        <tr>
                            <td class="text-muted">حد التنبيه</td>
                            <td>
                                @if($product->alert_quantity > 0)
                                    <span class="badge badge-light-warning">{{$product->alert_quantity}}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">له متغيرات</td>
                            <td>
                                @if($product->has_variants)
                                    <span class="badge badge-light-primary">نعم ({{$product->variants->count()}})</span>
                                @else
                                    <span class="badge badge-light-secondary">لا</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Card 2: Summary Stats ────────────────────────── --}}
    @php
        $warehouseCount = $stockRows->groupBy('warehouse_id')->count();
        $lowCount       = $stockRows->filter(function($r) use ($product) {
            $qty   = (float)$r->quantity;
            $alert = $r->variant ? (int)($r->variant->alert_quantity ?? 0) : (int)($product->alert_quantity ?? 0);
            return $qty > 0 && $alert > 0 && $qty <= $alert;
        })->count();
        $outCount = $stockRows->where('quantity', '<=', 0)->count();
    @endphp
    <div class="row g-5 mb-5">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body text-center py-5">
                    <div class="fs-1 fw-bolder text-primary">{{number_format($totalStock, 3)}}</div>
                    <div class="text-muted fs-7 mt-1">إجمالي المخزون</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body text-center py-5">
                    <div class="fs-1 fw-bolder text-dark">{{$warehouseCount}}</div>
                    <div class="text-muted fs-7 mt-1">عدد المستودعات</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body text-center py-5">
                    <div class="fs-1 fw-bolder text-warning">{{$lowCount}}</div>
                    <div class="text-muted fs-7 mt-1">مخزون منخفض</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body text-center py-5">
                    <div class="fs-1 fw-bolder text-danger">{{$outCount}}</div>
                    <div class="text-muted fs-7 mt-1">نفذ المخزون</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Card 3: Stock Per Warehouse ──────────────────── --}}
    <div class="card mb-5">
        <div class="card-header border-0 pt-6">
            <div class="card-title"><h2>المخزون حسب المستودع</h2></div>
        </div>
        <div class="card-body py-4">
            @if($stockRows->isEmpty())
                <div class="text-center text-muted py-5">لا يوجد مخزون لهذا المنتج</div>
            @else
            <div class="table-responsive">
                <table class="table table-hover table-row-dashed fs-6 gy-3">
                    <thead>
                        <tr class="text-start text-dark bg-light-dark fw-bold fs-7 text-uppercase gs-0">
                            <th>المستودع</th>
                            <th>المتغير</th>
                            <th class="text-center">الكمية (وحدة أساسية)</th>
                            <th>الكمية بالوحدات المحولة</th>
                            <th class="text-center">الحالة</th>
                            <th class="text-end">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 fw-semibold">
                        @foreach($stockRows as $stock)
                        @php
                            $qty   = (float)$stock->quantity;
                            $alert = $stock->variant
                                ? (int)($stock->variant->alert_quantity ?? 0)
                                : (int)($product->alert_quantity ?? 0);
                            if ($qty <= 0) {
                                $badge = '<span class="badge badge-light-danger">نفذ</span>';
                            } elseif ($alert > 0 && $qty <= $alert) {
                                $badge = '<span class="badge badge-light-warning">منخفض</span>';
                            } else {
                                $badge = '<span class="badge badge-light-success">متاح</span>';
                            }
                            $variantLabel = '—';
                            if ($stock->variant) {
                                $attrs = $stock->variant->attributes ?? [];
                                $variantLabel = is_array($attrs) && count($attrs)
                                    ? implode(' / ', array_values($attrs))
                                    : $stock->variant->sku;
                            }
                            // applicable unit conversions for this row
                            $applicableUcs = $product->unitConversions->filter(function($uc) use ($stock) {
                                if ($uc->variant_id === null) return true;
                                return $stock->variant_id && $uc->variant_id == $stock->variant_id;
                            });
                        @endphp
                        <tr>
                            <td class="fw-bold">{{$stock->warehouse->name ?? '—'}}</td>
                            <td>
                                @if($stock->variant)
                                    <span class="badge badge-light-info">{{$variantLabel}}</span>
                                @else
                                    <span class="text-muted">بدون نوع</span>
                                @endif
                            </td>
                            <td class="text-center fw-bold fs-5">
                                {{number_format($qty, 3)}}
                                @if($product->unit)
                                    <small class="text-muted d-block fw-normal">{{$product->unit->name}}</small>
                                @endif
                            </td>
                            <td>
                                @if($applicableUcs->isEmpty())
                                    <span class="text-muted fs-7">—</span>
                                @else
                                    @foreach($applicableUcs as $uc)
                                    @php
                                        $rate     = (float)$uc->conversion_rate;
                                        $ucQty    = $rate > 0 ? $qty / $rate : 0;
                                        $decimals = (int)$uc->decimal_places;
                                    @endphp
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge badge-light-primary">{{$uc->targetUnit->name}}</span>
                                        <span class="fw-bold">{{number_format($ucQty, $decimals)}}</span>
                                        <small class="text-muted">(× {{rtrim(rtrim((string)$uc->conversion_rate,'0'),'.')}} {{$uc->baseUnit->name}})</small>
                                    </div>
                                    @endforeach
                                @endif
                            </td>
                            <td class="text-center">{!! $badge !!}</td>
                            <td class="text-end">
                                <a href="{{route($route.'.adjust-item', $stock->id)}}"
                                   class="btn btn-xs btn-icon btn-primary me-1" title="تعديل المخزون">
                                    <i class="bi bi-plus-slash-minus fs-5"></i>
                                </a>
                                <a href="{{route($route.'.history', $stock->id)}}"
                                   class="btn btn-xs btn-icon btn-info" title="سجل الحركات">
                                    <i class="bi bi-clock-history fs-5"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Card 4: Unit Conversions ──────────────────────── --}}
    @if($product->unitConversions->isNotEmpty())
    <div class="card mb-5">
        <div class="card-header border-0 pt-6">
            <div class="card-title"><h2>تحويلات الوحدات</h2></div>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover table-row-dashed fs-6 gy-3">
                    <thead>
                        <tr class="text-start text-dark bg-light-dark fw-bold fs-7 text-uppercase gs-0">
                            <th>الوحدة الأساسية</th>
                            <th>الوحدة الهدف</th>
                            <th class="text-center">معامل التحويل</th>
                            <th class="text-center">الكمية الإجمالية</th>
                            <th class="text-center">افتراضي</th>
                            <th class="text-center">كسور مسموحة</th>
                            <th>المتغير</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 fw-semibold">
                        @foreach($product->unitConversions as $uc)
                        @php
                            $rate = (float)$uc->conversion_rate;
                            // use variant-specific stock if conversion is variant-scoped
                            if ($uc->variant_id) {
                                $baseQty = $stockRows->where('variant_id', $uc->variant_id)->sum(fn($r) => (float)$r->quantity);
                            } else {
                                $baseQty = $totalStock;
                            }
                            $ucTotalQty = $rate > 0 ? $baseQty / $rate : 0;
                        @endphp
                        <tr>
                            <td>{{$uc->baseUnit->name}}</td>
                            <td class="fw-bold">{{$uc->targetUnit->name}}</td>
                            <td class="text-center">×&nbsp;{{rtrim(rtrim((string)$uc->conversion_rate,'0'),'.')}}</td>
                            <td class="text-center">
                                <span class="fw-bold fs-5">{{number_format($ucTotalQty, (int)$uc->decimal_places)}}</span>
                                <small class="text-muted d-block">من {{number_format($baseQty, 3)}} {{$uc->baseUnit->name}}</small>
                            </td>
                            <td class="text-center">
                                @if($uc->is_default)
                                    <span class="badge badge-light-success">نعم</span>
                                @else
                                    <span class="badge badge-light-secondary">لا</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($uc->allow_fractions)
                                    <span class="badge badge-light-info">نعم ({{$uc->decimal_places}} خانات)</span>
                                @else
                                    <span class="badge badge-light-secondary">لا</span>
                                @endif
                            </td>
                            <td>
                                @if($uc->variant)
                                    @php $va = $uc->variant->attributes ?? []; @endphp
                                    <span class="badge badge-light-info">
                                        {{is_array($va) && count($va) ? implode(' / ', array_values($va)) : $uc->variant->sku}}
                                    </span>
                                @else
                                    <span class="text-muted">عام</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Card 5: Transactions DataTable ──────────────────── --}}
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title"><h2>سجل جميع حركات المخزون</h2></div>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover table-row-dashed fs-6 gy-5" id="kt_table_product_txn">
                    <thead>
                        <tr class="text-start text-dark bg-light-dark fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-50px">#</th>
                            <th>نوع العملية</th>
                            <th>المستودع</th>
                            <th>المتغير</th>
                            <th class="text-center">الكمية</th>
                            <th class="text-center">تكلفة الوحدة</th>
                            <th>ملاحظات</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 fw-semibold"></tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
@section('script')
<script src="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.js')}}"></script>
<script>
$(function () {
    $('#kt_table_product_txn').DataTable({
        processing: false,
        searching: false,
        serverSide: true,
        pageLength: 25,
        order: [],
        language: {"loadingRecords": "انتظر لحظات ..."},
        ajax: { url: "{{ route($route.'.product-report', $product->id) }}" },
        columns: [
            {data: 'DT_RowIndex', orderable: false},
            {data: 'type_badge', orderable: false},
            {data: 'warehouse_name', orderable: false},
            {data: 'variant_info', orderable: false},
            {data: 'qty_display', orderable: false, className: 'text-center'},
            {data: 'cost_display', orderable: false, className: 'text-center'},
            {data: 'notes', orderable: false},
            {data: 'date_display', orderable: false},
        ]
    });
});
</script>
@endsection
