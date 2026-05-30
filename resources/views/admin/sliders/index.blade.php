@extends('admin.layout.master')

@php
    $url = 'admin/sliders';
    $route = 'admin.sliders';
    $viewPath = 'admin.sliders';
@endphp

@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('style')
    
@endsection

@section('breadcrumb')
    <!--begin::Toolbar-->
        <div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column me-3">
                <!--begin::Title-->
                <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">سلايدر</h1>
                <!--end::Title-->
                <!--begin::Breadcrumb-->
                <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-gray-600">
                        <a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
                    </li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-gray-600">
                        <a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">سلايدر</a>
                    </li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-gray-600">القائمة</li>
                    <!--end::Item-->
                </ul>
                <!--end::Breadcrumb-->
            </div>
            <!--end::Page title-->
            <!--begin::Actions-->
            <div class="d-flex align-items-center py-2 py-md-1">
                <!--begin::Button-->
                <a href="javascript:;" class="btn btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#kt_modal_add" id="kt_toolbar_primary_button"><i class="ki-duotone ki-plus fs-2"></i> اضف جديد</a>
                <!--end::Button-->
            </div>
            <!--end::Actions-->
        </div>
        <!--end::Toolbar-->
@endsection

@section('content')

<div class="content flex-column-fluid" id="kt_content">
    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" data-kt-db-table-filter="search" id="search" class="form-control form-control-solid w-250px ps-13" placeholder="البحث ..." />
                </div>
                <!--end::Search-->
            </div>
            <!--begin::Card title-->
            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <a href="javascript:;" class="btn btn-icon btn-success me-2" id="btn_export" data-token="{{ csrf_token() }}"><i class="bi bi-file-earmark-arrow-down-fill fs-4"></i></a>
                    <a href="javascript:;" class="btn btn-icon btn-danger me-2" id="btn_delete" data-token="{{ csrf_token() }}"><i class="bi bi-trash-fill fs-4"></i></a>
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
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
                            <th class="text-start">العنوان</th>
                            <th class="text-start">الترتيب</th>
                            <th class="text-start min-w-100px">الاجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 fw-semibold">
                    </tbody>
                </table>
            </div>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->
</div>


<!--begin::Modal - Add-->
<div class="modal fade" id="kt_modal_add" tabindex="-1" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header" id="kt_modal_add_header">
                <!--begin::Modal title-->
                <h2 class="fw-bold">اضف جديد</h2>
                <!--end::Modal title-->
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
                <!--end::Close-->
            </div>
            <!--end::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body px-5 my-7">
                <!--begin::Form-->
                <form id="kt_modal_add_form" class="form" action="{{route($route. '.store')}}" method="POST" enctype="multipart/form-data" >
                    @csrf

                    @include($viewPath. '.form')

                    <!--begin::Actions-->
                    <div class="text-center pt-10">
                        <button type="reset" data-kt-ecommerce-settings-type="cancel" class="btn btn-light me-3">الغاء</button>
                        <button type="submit" class="btn btn-primary" id="kt_account_profile_details_submit">حفظ</button>
                    </div>
                    <!--end::Actions-->
                </form>
                <!--end::Form-->
            </div>
            <!--end::Modal body-->
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>
<!--end::Modal - Add-->

<!--begin::Modal - Add-->
<div class="modal fade" id="kt_modal_update" tabindex="-1" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header" id="kt_modal_update_header">
                <!--begin::Modal title-->
                <h2 class="fw-bold">تعديل البيانات</h2>
                <!--end::Modal title-->
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
                <!--end::Close-->
            </div>
            <!--end::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body px-5 my-7">
                <!--begin::Form-->
                <form id="kt_modal_update_form" class="form" action="{{route($route. '.update')}}" method="POST" enctype="multipart/form-data" >
                    @csrf

                    <div class="datae">
                        @include($viewPath. '.form')
                    </div>
                    <!--begin::Actions-->
                    <div class="text-center pt-10">
                        <button type="reset" data-kt-ecommerce-settings-type="cancel" class="btn btn-light me-3">الغاء</button>
                        <button type="submit" class="btn btn-primary" id="kt_account_profile_details_submit">حفظ</button>
                    </div>
                    <!--end::Actions-->
                </form>
                <!--end::Form-->
            </div>
            <!--end::Modal body-->
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>
<!--end::Modal - Add-->


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
                d.is_active = $('#is_active').val();
            }
        },
        lengthMenu: [
            [10, 25, 50, 100],
            ['10', '25', '50', '100']
        ],
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'checkbox', name: 'checkbox'},
            {data: 'title', name: 'title'},
            {data: 'sort', name: 'sort'},
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

    $("#btn_export").click(function (event) {
        event.preventDefault();


        var params = {
            
        };

        window.location = "{{ route($route.'.export') }}?" + $.param(params);

    });

    $("#btn_delete").click(function (event) {
        event.preventDefault();
        var checkIDs = $("#kt_table_list input:checkbox:checked").map(function () {
            return $(this).val();
        }).get(); // <----

        if (checkIDs.length > 0) {
            var token = $(this).data("token");

            const button = document.getElementById('kt_docs_sweetalert_html');

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
                    $.ajax(
                        {
                            url: "{{route($route.'.delete')}}",
                            type: 'post',
                            dataType: "JSON",
                            data: {
                                "id": checkIDs,
                                "_method": 'post',
                                "_token": token,
                            },
                            success: function (data) {
                                if (data.message == "success") {
                                    table.draw();
                                    toastr.success("", "تم الحذف بنجاح");
                                } else {
                                    toastr.success("", "عفوا لم يتم الحذف");
                                }
                            },
                            fail: function (xhrerrorThrown) {
                                toastr.success("", "عفوا لم يتم الحذف");
                            }
                        });
                } else {
                    toastr.error("", "تم الغاء الحذف");
                }
            });

        } else {
            toastr.error("", "حدد العناصر اولا");
        }
    });

});

function edit_item(id) {
    var id = id ;
    $.ajax({
        type: "GET",
        url: "{{url($url.'/edit').'/'}}"+id,
        data: {"id": id},
        success: function (data) {
            $("#kt_modal_update .datae").html(data);
            $("#kt_modal_update").modal('show');
        }
    })
}

</script>
@endsection