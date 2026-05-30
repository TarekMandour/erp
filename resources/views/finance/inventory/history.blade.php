@extends('admin.layout.master')
@php $route = 'finance.inventory'; @endphp
@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">المخزون</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">المخزون</a></li>
            <li class="breadcrumb-item text-gray-600">سجل حركات: {{$item->product->name}}</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
        <a href="{{route($route.'.adjust-item', $item->id)}}" class="btn btn-primary me-2">
            <i class="bi bi-plus-slash-minus me-1"></i> تعديل مخزون
        </a>
        <a href="{{route($route.'.index')}}" class="btn btn-light">رجوع</a>
    </div>
</div>
@endsection
@section('content')
<div class="content flex-column-fluid" id="kt_content">

    {{-- Info Card --}}
    <div class="card mb-5">
        <div class="card-body py-4">
            <div class="d-flex flex-wrap gap-7">
                <div>
                    <div class="text-muted fs-7 mb-1">المنتج</div>
                    <div class="fw-bold fs-6">{{$item->product->name}}</div>
                    <div class="text-muted fs-7">SKU: {{$item->variant ? $item->variant->sku : $item->product->sku}}</div>
                </div>
                @if($item->variant)
                <div>
                    <div class="text-muted fs-7 mb-1">النوع</div>
                    @php $attrs = $item->variant->attributes ?? []; @endphp
                    <span class="badge badge-light-info fs-7">{{ is_array($attrs) ? implode(' / ', array_values($attrs)) : $item->variant->sku }}</span>
                </div>
                @endif
                <div>
                    <div class="text-muted fs-7 mb-1">المستودع</div>
                    <div class="fw-bold fs-6">{{$item->warehouse->name}}</div>
                </div>
                <div>
                    <div class="text-muted fs-7 mb-1">الكمية الحالية</div>
                    <div class="fw-bold fs-4 text-primary">{{ number_format((float)$item->quantity, 3) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transactions Table --}}
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title"><h2>سجل الحركات</h2></div>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover table-row-dashed fs-6 gy-5" id="kt_table_history">
                    <thead>
                        <tr class="text-start text-dark bg-light-dark fw-bold fs-5 text-uppercase gs-0">
                            <th class="w-50px">#</th>
                            <th>نوع العملية</th>
                            <th>الكمية</th>
                            <th>تكلفة الوحدة</th>
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
    $('#kt_table_history').DataTable({
        processing: false, searching: false, serverSide: true, pageLength: 25, sort: false,
        language: {"loadingRecords": "انتظر لحظات ..."},
        ajax: { url: "{{ route($route.'.history', $item->id) }}" },
        columns: [
            {data: 'DT_RowIndex', orderable: false},
            {data: 'type_badge', orderable: false},
            {data: 'qty_display', orderable: false},
            {data: 'cost_display', orderable: false},
            {data: 'notes', orderable: false},
            {data: 'date_display', orderable: false},
        ]
    });
});
</script>
@endsection
