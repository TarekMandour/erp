@extends('admin.layout.master')
@php $route = 'finance.inventory.transfers'; @endphp
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تفاصيل التحويل</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route('finance.inventory.index')}}" class="text-gray-600 text-hover-primary">المخزون</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">تحويلات المخزون</a></li>
            <li class="breadcrumb-item text-gray-600">{{$transfer->reference}}</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
        <a href="{{route($route.'.index')}}" class="btn btn-light fw-bold me-2">
            <i class="bi bi-arrow-right me-1"></i> العودة للقائمة
        </a>
        @if($transfer->status === 'pending')
        <form method="POST" action="{{route($route.'.cancel', $transfer->id)}}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-danger fw-bold" onclick="return confirm('هل تريد إلغاء هذا التحويل؟')">
                <i class="bi bi-x-circle me-1"></i> إلغاء التحويل
            </button>
        </form>
        @endif
    </div>
</div>
@endsection
@section('content')
<div class="content flex-column-fluid" id="kt_content">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-5">
        {{session('success')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card card-flush mb-6">
        <div class="card-header">
            <div class="card-title">
                <h2 class="me-3">تفاصيل التحويل</h2>
                {!! $transfer->status_badge !!}
            </div>
        </div>
        <div class="card-body pt-4">
            <div class="row g-5">

                <div class="col-md-4">
                    <div class="border rounded p-4 h-100">
                        <div class="fw-bold text-gray-600 mb-2 fs-7">رقم المرجع</div>
                        <div class="fw-bolder fs-4 text-primary">{{$transfer->reference}}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-4 h-100 d-flex flex-column align-items-start">
                        <div class="fw-bold text-gray-600 mb-2 fs-7">المستودع المصدر</div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-buildings fs-4 text-danger me-2"></i>
                            <span class="fw-bolder fs-5">{{$transfer->fromWarehouse->name ?? '—'}}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-4 h-100 d-flex flex-column align-items-start">
                        <div class="fw-bold text-gray-600 mb-2 fs-7">المستودع الوجهة</div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-buildings fs-4 text-success me-2"></i>
                            <span class="fw-bolder fs-5">{{$transfer->toWarehouse->name ?? '—'}}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-4 h-100">
                        <div class="fw-bold text-gray-600 mb-2 fs-7">المنتج</div>
                        <div class="fw-bolder fs-5">{{$transfer->product->name ?? '—'}}</div>
                        @if($transfer->product)
                        <div class="text-muted fs-7 mt-1">SKU: {{$transfer->product->sku}}</div>
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-4 h-100">
                        <div class="fw-bold text-gray-600 mb-2 fs-7">النوع / المتغير</div>
                        @if($transfer->variant)
                            @php $attrs = $transfer->variant->attributes ?? []; @endphp
                            <span class="badge badge-light-info fs-6">
                                {{ is_array($attrs) ? implode(' / ', array_values($attrs)) : $transfer->variant->sku }}
                            </span>
                        @else
                            <span class="text-muted">بدون نوع</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-4 h-100">
                        <div class="fw-bold text-gray-600 mb-2 fs-7">الكمية المحولة</div>
                        <div class="fw-bolder fs-3 text-dark">
                            {{number_format((float)$transfer->quantity, 3)}}
                            @if($transfer->unitConversion)
                                <span class="fs-6 text-muted ms-1">{{$transfer->unitConversion->target_unit}}</span>
                            @elseif($transfer->unit)
                                <span class="fs-6 text-muted ms-1">{{$transfer->unit->name}}</span>
                            @elseif($transfer->product && $transfer->product->unit)
                            <span class="fs-6 text-muted ms-1">{{$transfer->product->unit->name}}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-4 h-100">
                        <div class="fw-bold text-gray-600 mb-2 fs-7">تاريخ الإنشاء</div>
                        <div class="fw-semibold">{{$transfer->created_at->format('Y-m-d H:i')}}</div>
                    </div>
                </div>

                @if($transfer->completed_at)
                <div class="col-md-4">
                    <div class="border rounded p-4 h-100">
                        <div class="fw-bold text-gray-600 mb-2 fs-7">تاريخ التنفيذ</div>
                        <div class="fw-semibold text-success">{{$transfer->completed_at->format('Y-m-d H:i')}}</div>
                    </div>
                </div>
                @endif

                @if($transfer->cancelled_at)
                <div class="col-md-4">
                    <div class="border rounded p-4 h-100">
                        <div class="fw-bold text-gray-600 mb-2 fs-7">تاريخ الإلغاء</div>
                        <div class="fw-semibold text-danger">{{$transfer->cancelled_at->format('Y-m-d H:i')}}</div>
                    </div>
                </div>
                @endif

                @if($transfer->notes)
                <div class="col-md-12">
                    <div class="border rounded p-4">
                        <div class="fw-bold text-gray-600 mb-2 fs-7">ملاحظات</div>
                        <div class="fw-semibold">{{$transfer->notes}}</div>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>

    @if($transfer->status === 'completed')
    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title"><h3>حركات المخزون المرتبطة</h3></div>
        </div>
        <div class="card-body pt-4">
            <div class="table-responsive">
                <table class="table table-row-bordered table-hover gy-3">
                    <thead>
                        <tr class="fw-bold fs-6 text-gray-800">
                            <th>المستودع</th>
                            <th>النوع</th>
                            <th>الكمية</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $ref = $transfer->reference;
                            $transactions = \App\Models\Finance\InventoryTransaction::where('product_id', $transfer->product_id)
                                ->where('variant_id', $transfer->variant_id)
                                ->where('notes', 'like', "%{$ref}%")
                                ->with('warehouse')
                                ->orderBy('id')
                                ->get();
                        @endphp
                        @forelse($transactions as $tx)
                        <tr>
                            <td>{{$tx->warehouse->name ?? '—'}}</td>
                            <td>
                                @if($tx->type === 'out')
                                <span class="badge badge-light-danger">صادر (خصم)</span>
                                @else
                                <span class="badge badge-light-success">وارد (إضافة)</span>
                                @endif
                            </td>
                            <td>{{number_format((float)$tx->quantity, 3)}}</td>
                            <td>{{$tx->created_at->format('Y-m-d H:i')}}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted">لا توجد حركات</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
