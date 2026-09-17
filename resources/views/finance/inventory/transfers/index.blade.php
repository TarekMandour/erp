@extends('admin.layout.master')
@php $route = 'finance.inventory.transfers'; @endphp
@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تحويلات المخزون</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route('finance.inventory.index')}}" class="text-gray-600 text-hover-primary">المخزون</a></li>
            <li class="breadcrumb-item text-gray-600">تحويلات المخزون</li>
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
                            <label class="form-label fw-semibold">المستودع المصدر :</label>
                            <select class="form-select form-select-solid" id="ffrom">
                                <option value="">الكل</option>
                                @foreach($warehouses as $w)
                                <option value="{{$w->id}}">{{$w->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label fw-semibold">المستودع الوجهة :</label>
                            <select class="form-select form-select-solid" id="fto">
                                <option value="">الكل</option>
                                @foreach($warehouses as $w)
                                <option value="{{$w->id}}">{{$w->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label fw-semibold">المنتج :</label>
                            <select class="form-select form-select-solid" id="fproduct">
                                <option value="">الكل</option>
                                @foreach($products as $p)
                                <option value="{{$p->id}}">{{$p->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-10">
                            <label class="form-label fw-semibold">الحالة :</label>
                            <select class="form-select form-select-solid" id="fstatus">
                                <option value="">الكل</option>
                                <option value="completed">مكتمل</option>
                                <option value="pending">معلق</option>
                                <option value="cancelled">ملغي</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-sm btn-light me-2" data-kt-menu-dismiss="true">الغاء</button>
                            <button type="submit" class="btn btn-sm btn-primary" data-kt-menu-dismiss="true">تطبيق</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <a href="javascript:;" class="btn btn-light fw-bold me-2" id="btn_export" title="تصدير">
            <i class="bi bi-file-earmark-arrow-down-fill fs-5 me-1"></i> تصدير
        </a>
        <a href="{{route($route.'.create')}}" class="btn btn-dark fw-bold">
            <i class="ki-duotone ki-plus fs-2"></i> تحويل جديد
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
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5"><span class="path1"></span><span class="path2"></span></i>
                    <input type="text" id="search" class="form-control form-control-solid w-250px ps-13" placeholder="البحث بالمرجع أو المنتج ..." />
                </div>
            </div>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover table-row-dashed fs-6 gy-5" id="kt_table_list">
                    <thead>
                        <tr class="text-start text-dark bg-light-dark fw-bold fs-5 text-uppercase gs-0">
                            <th class="w-50px">#</th>
                            <th>المرجع</th>
                            <th>من المستودع</th>
                            <th>إلى المستودع</th>
                            <th>المنتج</th>
                            <th>النوع</th>
                            <th>الكمية</th>
                            <th>الوحدة</th>
                            <th>الحالة</th>
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
                d.search   = $('#search').val();
                d.ffrom    = $('#ffrom').val();
                d.fto      = $('#fto').val();
                d.fproduct = $('#fproduct').val();
                d.fstatus  = $('#fstatus').val();
            }
        },
        lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
        columns: [
            {data: 'DT_RowIndex',  orderable: false, searchable: false},
            {data: 'reference',    orderable: false},
            {data: 'from_name',    orderable: false},
            {data: 'to_name',      orderable: false},
            {data: 'product_name', orderable: false},
            {data: 'variant_info', orderable: false},
            {data: 'qty_display',  orderable: false},
            {data: 'unit_name',    orderable: false},
            {data: 'status_badge', orderable: false},
            {data: 'date_display', orderable: false},
            {data: 'action',       orderable: false, searchable: false},
        ]
    });

    $('#search').on('keyup', function () { table.draw(); });
    $('#filter-form').on('submit', function (e) { e.preventDefault(); table.ajax.reload(); });
    $('#filter-form').on('reset',  function (e) { e.preventDefault(); $(this).find('select').val(''); table.ajax.reload(); });

    $('#btn_export').on('click', function () {
        window.location.href = "{{ route($route.'.export') }}?" + new URLSearchParams({
            ffrom:    $('#ffrom').val(),
            fto:      $('#fto').val(),
            fproduct: $('#fproduct').val(),
            fstatus:  $('#fstatus').val(),
        }).toString();
    });
});
</script>
@endsection
