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
            <li class="breadcrumb-item text-gray-600">المخزون</li>
            <li class="breadcrumb-item text-gray-600">مستوى المخزون</li>
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
                            <select class="form-select form-select-solid" id="fwarehouse" data-kt-select2="true" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                                <option value="">الكل</option>
                                @foreach($warehouses as $w)
                                <option value="{{$w->id}}">{{$w->name}}</option>
                                @endforeach
                            </select> 
                        </div>
                        <div class="mb-5">
                            <label class="form-label fw-semibold">المنتج :</label>
                            <select class="form-select form-select-solid" id="fproduct" data-kt-select2="true"
                                data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                                <option></option>
                            </select>

                        </div>
                        <div class="mb-10">
                            <label class="form-label fw-semibold">حالة المخزون :</label>
                            <select class="form-select form-select-solid" id="fstock" data-kt-select2="true" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                                <option value="">الكل</option>
                                <option value="ok">متاح</option>
                                <option value="low">منخفض</option>
                                <option value="out">نفذ</option>
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
        <a href="{{route($route.'.transactions')}}" class="btn btn-light fw-bold me-2">
            <i class="bi bi-clock-history fs-5 me-1"></i> سجل الحركات
        </a>
        <a href="{{route($route.'.adjust')}}" class="btn btn-dark fw-bold">
            <i class="ki-duotone ki-plus fs-2"></i> تعديل مخزون
        </a>
    </div>
</div>
@endsection
@section('content')
<div class="content flex-column-fluid" id="kt_content">
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5"><span class="path1"></span><span class="path2"></span></i>
                    <input type="text" data-kt-db-table-filter="search" id="search" class="form-control form-control-solid w-250px ps-13" placeholder="البحث بالمنتج أو SKU ..." />
                </div>
            </div>
            <div class="card-toolbar">
                <div class="d-flex justify-content-end">
                    <a href="javascript:;" class="btn btn-icon btn-success me-2" id="btn_export" data-token="{{ csrf_token() }}" title="تصدير"><i class="bi bi-file-earmark-arrow-down-fill fs-4"></i></a>
                </div>
            </div>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover table-row-dashed fs-6 gy-5" id="kt_table_list">
                    <thead>
                        <tr class="text-start text-dark bg-light-dark fw-bold fs-5 text-uppercase gs-0">
                            <th class="w-50px">#</th>
                            <th class="w-50px">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_list .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>المنتج</th>
                            <th>النوع / المتغير</th>
                            <th>المستودع</th>
                            <th>الكمية</th>
                            <th class="min-w-100px">الاجراءات</th>
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

function initProductSelect(selector, dropdownParent) {
    $(selector).select2({
        placeholder: 'Search product',
        minimumInputLength: 3,
        dropdownParent: dropdownParent, // Important for modals
        ajax: {
            url: '/admin/finance/products/search',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    search: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data.results
                };
            }
        }
    });
}

$(function () {

    initProductSelect('#fproduct');

    var table = $('#kt_table_list').DataTable({
        processing: false, searching: false, serverSide: true, pageLength: 25, sort: false,
        language: {"loadingRecords": "انتظر لحظات ..."},
        ajax: {
            url: "{{ route($route.'.index') }}",
            data: function (d) {
                d.search     = $('#search').val();
                d.fwarehouse = $('#fwarehouse').val();
                d.fproduct   = $('#fproduct').val();
                d.fstock     = $('#fstock').val();
            }
        },
        lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
        columns: [
            {data: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'checkbox', orderable: false, searchable: false},
            {data: 'product_name', orderable: false},
            {data: 'variant_info', orderable: false},
            {data: 'warehouse_name', orderable: false},
            {data: 'quantity_display', orderable: false},
            {data: 'action', orderable: false, searchable: false},
        ]
    });

    document.querySelector('[data-kt-db-table-filter="search"]').addEventListener('keyup', function () { table.draw(); });
    $('#filter-form').on('submit', function(e) { e.preventDefault(); table.ajax.reload(); });
    $('#filter-form').on('reset', function(e) { e.preventDefault(); $(this).find('select').val(''); table.ajax.reload(); });

    $("#btn_export").click(function () {
        window.location.href = "{{ route($route.'.export') }}?" + new URLSearchParams({
            fwarehouse: $('#fwarehouse').val(),
            fproduct:   $('#fproduct').val()
        }).toString();
    });
});


</script>
@endsection
