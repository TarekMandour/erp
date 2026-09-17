@extends('admin.layout.master')
@php $route = 'finance.warehouse-transactions'; @endphp
@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">حركات المخزون</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600">حركات المخزون</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
        <div class="me-3">
            <a href="#" class="btn btn-light fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                <i class="ki-duotone ki-filter fs-5 text-gray-500 me-1"><span class="path1"></span><span class="path2"></span></i>الفلتر
            </a>
            <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true">
                <div class="px-7 py-5"><div class="fs-5 text-gray-900 fw-bold">الفلتر</div></div>
                <div class="separator border-gray-200"></div>
                <div class="px-7 py-5">
                    <form id="filter-form">
                        <div class="mb-5">
                            <label class="form-label fw-semibold">المستودع :</label>
                            <select class="form-select form-select-solid" id="fwarehouse">
                                <option value="">الكل</option>
                                @foreach($warehouses as $w)
                                    <option value="{{$w->id}}">{{$w->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label fw-semibold">النوع :</label>
                            <select class="form-select form-select-solid" id="ftype">
                                <option value="">الكل</option>
                                <option value="in">وارد</option>
                                <option value="out">صادر</option>
                                <option value="transfer_in">تحويل وارد</option>
                                <option value="transfer_out">تحويل صادر</option>
                                <option value="adjustment">تعديل</option>
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label fw-semibold">من تاريخ :</label>
                            <input type="date" class="form-control form-control-solid" id="fdate_from">
                        </div>
                        <div class="mb-10">
                            <label class="form-label fw-semibold">إلى تاريخ :</label>
                            <input type="date" class="form-control form-control-solid" id="fdate_to">
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-sm btn-light me-2" data-kt-menu-dismiss="true">الغاء</button>
                            <button type="submit" class="btn btn-sm btn-primary" data-kt-menu-dismiss="true">تطبيق</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <a href="{{route($route.'.create')}}" class="btn btn-dark fw-bold">
            <i class="ki-duotone ki-plus fs-2"></i> إضافة حركة يدوية
        </a>
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
    <div class="card">
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover table-row-dashed fs-6 gy-5" id="kt_table_list">
                    <thead>
                        <tr class="text-start text-dark bg-light-dark fw-bold fs-5 text-uppercase gs-0">
                            <th class="w-50px">#</th>
                            <th>المستودع</th>
                            <th>المنتج</th>
                            <th>المتغير</th>
                            <th>النوع</th>
                            <th>الكمية</th>
                            <th>الوحدة</th>
                            <th>الرصيد بعد الحركة</th>
                            <th>المصدر</th>
                            <th>التاريخ</th>
                            <th class="min-w-80px">الاجراءات</th>
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
    var table = $('#kt_table_list').DataTable({
        processing: false, searching: false, serverSide: true, pageLength: 25, sort: false,
        language: {"loadingRecords": "انتظر لحظات ..."},
        ajax: {
            url: "{{ route($route.'.index') }}",
            data: function (d) {
                d.fwarehouse = $('#fwarehouse').val();
                d.ftype      = $('#ftype').val();
                d.fdate_from = $('#fdate_from').val();
                d.fdate_to   = $('#fdate_to').val();
            }
        },
        lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
        columns: [
            {data: 'DT_RowIndex',        orderable: false, searchable: false},
            {data: 'warehouse_name',     orderable: false},
            {data: 'product_name',       orderable: false},
            {data: 'variant_info',       orderable: false},
            {data: 'type_badge',         orderable: false},
            {data: 'qty_display',        orderable: false},
            {data: 'unit_name',          orderable: false},
            {data: 'balance_display',    orderable: false},
            {data: 'reference_display',  orderable: false},
            {data: 'date_display',       orderable: false},
            {data: 'action',             orderable: false, searchable: false},
        ]
    });

    $('#filter-form').on('submit', function (e) { e.preventDefault(); table.ajax.reload(); });
    $('#filter-form').on('reset',  function ()  { $(this).find('select, input').val(''); table.ajax.reload(); });
});
</script>
@endsection
