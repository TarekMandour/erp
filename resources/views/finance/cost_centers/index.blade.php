@extends('admin.layout.master')

@php
    $route = 'finance.cost_centers';
@endphp

@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">مراكز التكلفة</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
            </li>
            <li class="breadcrumb-item text-gray-600">مراكز التكلفة</li>
            <li class="breadcrumb-item text-gray-600">القائمة</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
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
                        <tr class="text-start text-dark bg-light-dark fw-bold fs-5 gs-0">
                            <th class="w-40px">#</th>
                            <th class="w-40px">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_list .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>الاسم / الكود</th>
                            <th>المركز الأب</th>
                            <th>الحالة</th>
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
        processing: false,
        searching: false,
        serverSide: true,
        pageLength: 25,
        sort: false,
        language: { "loadingRecords": "انتظر لحظات ..." },
        ajax: {
            url: "{{ route($route.'.index') }}",
            data: function (d) {
                d.search = $('#search').val();
            }
        },
        lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
        columns: [
            {data: 'DT_RowIndex',  name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'checkbox',     name: 'checkbox', orderable: false, searchable: false},
            {data: 'info',         name: 'name'},
            {data: 'parent_name',  name: 'parent_name'},
            {data: 'is_active',    name: 'is_active'},
            {data: 'action',       name: 'action', orderable: false, searchable: false},
        ]
    });

    document.querySelector('[data-kt-db-table-filter="search"]').addEventListener('keyup', function () {
        table.draw();
    });

    $("#btn_export").click(function () {
        window.location.href = "{{ route($route.'.export') }}";
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
                customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-light" }
            }).then(function (result) {
                if (result.value) {
                    $.ajax({
                        type: "POST",
                        url: "{{ route($route.'.delete') }}",
                        data: {ids: checkIDs, _token: token},
                        success: function () { table.ajax.reload(); }
                    });
                }
            });
        }
    });
});
</script>
@endsection
