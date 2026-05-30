@extends('admin.layout.master')
@php $route = 'finance.vouchers'; @endphp
@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">السندات</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600">السندات</li>
            <li class="breadcrumb-item text-gray-600">القائمة</li>
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
                        <div class="mb-7">
                            <label class="form-label fw-semibold">نوع السند :</label>
                            <select class="form-select form-select-solid" id="ftype">
                                <option value="">الكل</option>
                                <option value="payment">سند صرف</option>
                                <option value="receipt">سند قبض</option>
                            </select>
                        </div>
                        <div class="mb-7">
                            <label class="form-label fw-semibold">نوع الدفع :</label>
                            <select class="form-select form-select-solid" id="fpayment_type">
                                <option value="">الكل</option>
                                <option value="cash">نقدي</option>
                                <option value="credit">آجل</option>
                                <option value="installments">أقساط</option>
                            </select>
                        </div>
                        <div class="mb-7">
                            <label class="form-label fw-semibold">نوع الطرف :</label>
                            <select class="form-select form-select-solid" id="fparty_type">
                                <option value="">الكل</option>
                                <option value="customer">عميل</option>
                                <option value="supplier">مورد</option>
                                <option value="user">مستخدم</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                        <div class="mb-7">
                            <label class="form-label fw-semibold">من تاريخ :</label>
                            <input type="date" class="form-control form-control-solid" id="date_from" />
                        </div>
                        <div class="mb-10">
                            <label class="form-label fw-semibold">إلى تاريخ :</label>
                            <input type="date" class="form-control form-control-solid" id="date_to" />
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-sm btn-light me-2" data-kt-menu-dismiss="true">الغاء</button>
                            <button type="submit" class="btn btn-sm btn-primary" data-kt-menu-dismiss="true">حفظ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <a href="{{route($route.'.create')}}" class="btn btn-dark fw-bold">
            <i class="ki-duotone ki-plus fs-2"></i> اضف جديد
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
                    <input type="text" data-kt-db-table-filter="search" id="search" class="form-control form-control-solid w-250px ps-13" placeholder="البحث ..." />
                </div>
            </div>
            <div class="card-toolbar">
                <div class="d-flex justify-content-end">
                    <a href="javascript:;" class="btn btn-icon btn-success me-2" id="btn_export" data-token="{{ csrf_token() }}"><i class="bi bi-file-earmark-arrow-down-fill fs-4"></i></a>
                    <a href="javascript:;" class="btn btn-icon btn-danger me-2" id="btn_delete" data-token="{{ csrf_token() }}"><i class="bi bi-trash-fill fs-4"></i></a>
                </div>
            </div>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover table-row-dashed fs-6 gy-5" id="kt_table_list">
                    <thead>
                        <tr class="text-start text-dark bg-light-dark fw-bold fs-5 text-uppercase gs-0">
                            <th class="w-60px">#</th>
                            <th class="w-60px">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_list .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>نوع السند</th>
                            <th>نوع الدفع</th>
                            <th>نوع الطرف</th>
                            <th>المبلغ</th>
                            <th>التاريخ</th>
                            <th>البيان</th>
                            <th>بواسطة</th>
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
$(function () {
    var table = $('#kt_table_list').DataTable({
        processing: false, searching: false, serverSide: true, pageLength: 25, sort: false,
        language: {"loadingRecords": "انتظر لحظات ..."},
        ajax: {
            url: "{{ route($route.'.index') }}",
            data: function (d) {
                d.search        = $('#search').val();
                d.ftype         = $('#ftype').val();
                d.fpayment_type = $('#fpayment_type').val();
                d.fparty_type   = $('#fparty_type').val();
                d.date_from     = $('#date_from').val();
                d.date_to       = $('#date_to').val();
            }
        },
        lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
        columns: [
            {data: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'checkbox', orderable: false, searchable: false},
            {data: 'type_badge', orderable: false},
            {data: 'payment_type_name', orderable: false},
            {data: 'party_type_name', orderable: false},
            {data: 'total_amount', orderable: false},
            {data: 'date'},
            {data: 'description'},
            {data: 'creator_name', orderable: false},
            {data: 'action', orderable: false, searchable: false},
        ]
    });
    document.querySelector('[data-kt-db-table-filter="search"]').addEventListener('keyup', function () { table.draw(); });
    $('#filter-form').on('submit', function(e) { e.preventDefault(); table.ajax.reload(); });
    $('#filter-form').on('reset', function(e) {
        e.preventDefault();
        $(this).find('input, select').val('');
        table.ajax.reload();
    });
    $("#btn_export").click(function () {
        window.location.href = "{{ route($route.'.export') }}?" + new URLSearchParams({
            ftype:         $('#ftype').val(),
            fpayment_type: $('#fpayment_type').val(),
            fparty_type:   $('#fparty_type').val(),
            date_from:     $('#date_from').val(),
            date_to:       $('#date_to').val()
        }).toString();
    });
    $("#btn_delete").click(function (event) {
        event.preventDefault();
        var checkIDs = $("#kt_table_list input:checkbox:checked").map(function () { return $(this).val(); }).get();
        if (checkIDs.length > 0) {
            Swal.fire({
                title: 'هل انت متأكد ؟', text: "لا يمكن استرجاع البيانات المحذوفه", icon: "info",
                buttonsStyling: false, showCancelButton: true, confirmButtonText: "موافق", cancelButtonText: "الغاء",
                customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-light" }
            }).then(function (result) {
                if (result.value) {
                    $.ajax({
                        type: "POST", url: "{{ route($route.'.delete') }}",
                        data: {ids: checkIDs, _token: $(event.target).data("token")},
                        success: function () { table.ajax.reload(); }
                    });
                }
            });
        }
    });
});
</script>
@endsection
