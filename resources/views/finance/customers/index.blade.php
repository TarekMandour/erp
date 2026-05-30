@extends('admin.layout.master')

@php
    $route = 'finance.customers';
    $viewPath = 'finance.customers';
@endphp

@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('style')
@endsection

@section('breadcrumb')
    <div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
        <div class="page-title d-flex flex-column me-3">
            <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">العملاء</h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
                </li>
                <li class="breadcrumb-item text-gray-600">العملاء</li>
                <li class="breadcrumb-item text-gray-600">القائمة</li>
            </ul>
        </div>
        <div class="d-flex align-items-center py-2 py-md-1">
            <div class="me-3">
                <a href="#" class="btn btn-light fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                    <i class="ki-duotone ki-filter fs-5 text-gray-500 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>الفلتر
                </a>
                <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true" id="kt_menu_customers_filter">
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">الفلتر</div>
                    </div>
                    <div class="separator border-gray-200"></div>
                    <div class="px-7 py-5">
                        <form id="filter-form">
                            <div class="row fv-row mb-7">
                                <label class="form-label fw-semibold">البحث :</label>
                                <div>
                                    <input type="text" class="form-control form-control-solid" id="fsearch" placeholder="الاسم / الهاتف / الكود" />
                                </div>
                            </div>
                            <div class="mb-10">
                                <label class="form-label fw-semibold">حالة الحساب :</label>
                                <div>
                                    <select class="form-select form-select-solid" id="account_status" data-kt-select2="true" data-close-on-select="true" data-placeholder="اختر ..." data-dropdown-parent="#kt_menu_customers_filter" data-allow-clear="false">
                                        <option value="">الكل</option>
                                        <option value="active">نشط</option>
                                        <option value="inactive">غير نشط</option>
                                        <option value="blocked">محظور</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="reset" class="btn btn-sm btn-light btn-active-light-primary me-2" data-kt-menu-dismiss="true">الغاء</button>
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
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" data-kt-db-table-filter="search" id="search" class="form-control form-control-solid w-250px ps-13" placeholder="البحث ..." />
                </div>
            </div>
            <div class="card-toolbar">
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <a href="javascript:;" class="btn btn-icon btn-success me-2" id="btn_export" data-token="{{ csrf_token() }}"><i class="bi bi-file-earmark-arrow-down-fill fs-4"></i></a>
                    <a href="javascript:;" class="btn btn-icon btn-danger me-2" id="btn_delete" data-token="{{ csrf_token() }}"><i class="bi bi-trash-fill fs-4"></i></a>
                </div>
            </div>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover table-row-dashed text-start fs-6 gy-5" id="kt_table_list">
                    <thead>
                        <tr class="text-start text-dark bg-light-dark fw-bold fs-5 text-uppercase gs-0">
                            <th class="text-start w-60px">#</th>
                            <th class="text-start w-60px">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_list .form-check-input" value="1" />
                                </div>
                            </th>
                            <th class="text-start">العميل</th>
                            <th class="text-start">التواصل</th>
                            <th class="text-start">الرصيد</th>
                            <th class="text-start">الحالة</th>
                            <th class="text-start">تاريخ الانضمام</th>
                            <th class="text-start min-w-100px">الاجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 fw-semibold">
                    </tbody>
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
        processing: false,
        searching: false,
        serverSide: true,
        pageLength: 10,
        sort: false,
        language: {
            "loadingRecords": "انتظر لحظات ..."
        },
        ajax: {
            url: "{{ route($route.'.index') }}",
            data: function (d) {
                d.search = $('#search').val();
                d.fsearch = $('#fsearch').val();
                d.account_status = $('#account_status').val();
            }
        },
        lengthMenu: [
            [10, 25, 50, 100],
            ['10', '25', '50', '100']
        ],
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'checkbox', name: 'checkbox'},
            {data: 'info', name: 'info'},
            {data: 'contact', name: 'contact'},
            {data: 'wallet_balance', name: 'wallet_balance'},
            {data: 'account_status', name: 'account_status'},
            {data: 'join_date', name: 'join_date'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

    const filterSearch = document.querySelector('[data-kt-db-table-filter="search"]');
    filterSearch.addEventListener('keyup', function (e) {
        table.draw();
    });

    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#filter-form').on('reset', function(e) {
        e.preventDefault();
        $(this).find('input, select').val('');
        table.ajax.reload();
    });

    $("#btn_export").click(function () {
        window.location.href = "{{ route($route.'.export') }}?" + new URLSearchParams({
            account_status: $('#account_status').val(),
            search: $('#fsearch').val()
        }).toString();
    });

    $("#btn_delete").click(function (event) {
        event.preventDefault();
        var checkIDs = $("#kt_table_list input:checkbox:checked").map(function () {
            return $(this).val();
        }).get();

        if (checkIDs.length > 0) {
            var token = $(this).data("token");
            Swal.fire({
                title: 'هل انت متأكد ؟',
                text: "لا يمكن استرجاع البيانات المحذوفه",
                icon: "info",
                buttonsStyling: false,
                showCancelButton: true,
                confirmButtonText: "موافق",
                cancelButtonText: "الغاء",
                customClass: {
                    confirmButton: "btn btn-primary",
                    cancelButton: 'btn btn-danger'
                }
            }).then(function (isConfirm) {
                if (isConfirm.value) {
                    $.ajax({
                        url: "{{route($route.'.delete')}}",
                        type: 'post',
                        dataType: "JSON",
                        data: {
                            "id": checkIDs,
                            "_token": token,
                        },
                        success: function (data) {
                            if (data.status == "success") {
                                table.draw();
                                toastr.success("", data.message);
                            } else {
                                toastr.error("", data.message);
                            }
                        }
                    });
                }
            });
        } else {
            toastr.error("", "حدد العناصر اولا");
        }
    });

});
</script>
@endsection
