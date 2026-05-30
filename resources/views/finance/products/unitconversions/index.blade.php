@extends('admin.layout.master')

@php
    $url = 'admin/finance/products/unitconversions';
    $route = 'finance.products.unitconversions';
    $viewPath = 'finance.products.unitconversions';
@endphp

@section('css')
    <link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet"
        type="text/css" />
@endsection

@section('style')

@endsection

@section('breadcrumb')
    <!--begin::Toolbar-->
    <div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
        <!--begin::Page title-->
        <div class="page-title d-flex flex-column me-3">
            <!--begin::Title-->
            <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">محول الوحدات</h1>
            <!--end::Title-->
            <!--begin::Breadcrumb-->
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
                <!--begin::Item-->
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
                </li>
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{route('finance.products.index')}}" class="text-gray-600 text-hover-primary">المنتجات</a>
                </li>
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{route($route . '.index')}}" class="text-gray-600 text-hover-primary">محول الوحدات</a>
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
            <div class="me-3">
                <!--begin::Menu-->
                <a href="#" class="btn btn-light fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                    <i class="ki-duotone ki-filter fs-5 text-gray-500 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>الفلتر</a>
                <!--begin::Menu 1-->
                <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true"
                    id="kt_menu_685c37c8c6d74">
                    <!--begin::Header-->
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">الفلتر</div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Menu separator-->
                    <div class="separator border-gray-200"></div>
                    <!--end::Menu separator-->
                    <!--begin::Form-->
                    <div class="px-7 py-5">
                        <form id="filter-form">
                            <!--begin::Input group-->

                            <div class="mb-10">
                                <!--begin::Label-->
                                <label class="form-label fw-semibold">المنتجات :</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <div>
                                    <select class="form-select form-select-solid" id="product_id" data-kt-select2="true"
                                        data-close-on-select="true" data-placeholder="اختر ..."
                                        data-dropdown-parent="#kt_menu_685c37c8c6d74" data-allow-clear="false">
                                        <option></option>
                                    </select>
                                </div>
                                <!--end::Input-->
                            </div>

                            <div class="mb-10">
                                <!--begin::Label-->
                                <label class="form-label fw-semibold">انواع المنتجات :</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <div>
                                    <select class="form-select form-select-solid" id="variant_id" data-kt-select2="true"
                                        data-close-on-select="true" data-placeholder="اختر ..."
                                        data-dropdown-parent="#kt_menu_685c37c8c6d74" data-allow-clear="false">
                                        <option></option>
                                    </select>
                                </div>
                                <!--end::Input-->
                            </div>

                            <div class="mb-10">
                                <!--begin::Label-->
                                <label class="form-label fw-semibold">الوحدات :
                                    <span class="ms-1" data-bs-toggle="tooltip" title="الفلتر بالوحدة الاساسية">
                                        <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </span>
                                </label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <div>
                                    <select class="form-select form-select-solid" id="base_unit" data-kt-select2="true"
                                        data-close-on-select="true" data-placeholder="اختر ..."
                                        data-dropdown-parent="#kt_menu_685c37c8c6d74" data-allow-clear="false">
                                        <option></option>
                                        @foreach ($filters['units'] as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <!--end::Input-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="reset" class="btn btn-sm btn-light btn-active-light-primary me-2"
                                    data-kt-menu-dismiss="true">الغاء</button>
                                <button type="submit" class="btn btn-sm btn-primary"
                                    data-kt-menu-dismiss="true">حفظ</button>
                            </div>
                            <!--end::Actions-->
                        </form>
                    </div>
                    <!--end::Form-->
                </div>
                <!--end::Menu 1-->
                <!--end::Menu-->
            </div>

            <!--begin::Button-->
            <a href="javascript:;" class="btn btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#kt_modal_add"
                id="kt_toolbar_primary_button"><i class="ki-duotone ki-plus fs-2"></i> اضف جديد</a>
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

                    <!--end::Search-->
                </div>
                <!--begin::Card title-->
                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <!--begin::Toolbar-->
                    <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                        <a href="javascript:;" class="btn btn-icon btn-success me-2" id="btn_export"
                            data-token="{{ csrf_token() }}"><i class="bi bi-file-earmark-arrow-down-fill fs-4"></i></a>
                        <a href="javascript:;" class="btn btn-icon btn-danger me-2" id="btn_delete"
                            data-token="{{ csrf_token() }}"><i class="bi bi-trash-fill fs-4"></i></a>
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
                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                            data-kt-check-target="#kt_table_list .form-check-input" value="1" />
                                    </div>
                                </th>
                                <th class="text-start">المنتج</th>
                                <th class="text-start">الوحدة الاساسية</th>
                                <th class="text-start">الوحدة المستهدفة</th>
                                <th class="text-start">معامل التحويل</th>
                                <th class="text-start">التحويل العكسي</th>
                                <th class="text-start">الافتراضي</th>
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
                    <form id="kt_modal_add_form" class="form" action="{{route($route . '.store')}}" method="POST"
                        enctype="multipart/form-data">
                        @csrf

                        @include($viewPath . '.form')

                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" data-kt-ecommerce-settings-type="cancel"
                                class="btn btn-light me-3">الغاء</button>
                            <button type="submit" class="btn btn-primary"
                                id="kt_account_profile_details_submit">حفظ</button>
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
                    <form id="kt_modal_update_form" class="form" action="{{route($route . '.update')}}" method="POST"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="datae">
                            @include($viewPath . '.form')
                        </div>
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" data-kt-ecommerce-settings-type="cancel"
                                class="btn btn-light me-3">الغاء</button>
                            <button type="submit" class="btn btn-primary"
                                id="kt_account_profile_details_submit">حفظ</button>
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
                    url: "{{ route($route . '.index') }}",
                    data: function (d) {
                        d.product_id = $('#product_id').val();
                        d.variant_id = $('#variant_id').val();
                        d.base_unit = $('#base_unit').val();
                    }
                },
                lengthMenu: [
                    [10, 25, 50, 100],
                    ['10', '25', '50', '100']
                ],
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'checkbox', name: 'checkbox' },
                    { data: 'product', name: 'product' },
                    { data: 'base_unit', name: 'base_unit' },
                    { data: 'target_unit', name: 'target_unit' },
                    { data: 'rate', name: 'rate' },
                    { data: 'rate_reverse', name: 'rate_reverse' },
                    { data: 'is_default', name: 'is_default' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#filter-form').on('submit', function (e) {
                e.preventDefault();
                table.ajax.reload();
            });

            $('#filter-form').on('reset', function (e) {
                e.preventDefault();
                $(this).find('input, select').val('');
                table.ajax.reload();
            });

            $("#btn_export").click(function (event) {
                event.preventDefault();


                var params = {
                    product_id: $('#product_id').val(),
                    variant_id: $('#variant_id').val(),
                    base_unit: $('#base_unit').val()
                };

                window.location = "{{ route($route . '.export') }}?" + $.param(params);

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
                                    url: "{{route($route . '.delete')}}",
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
            var id = id;
            $.ajax({
                type: "GET",
                url: "{{url($url . '/edit') . '/'}}" + id,
                data: { "id": id },
                success: function (data) {
                    $("#kt_modal_update .datae").html(data);
                    $("#kt_modal_update").modal('show');
                    // Initialize Select2 for the loaded Edit form
                    initProductSelect('#kt_modal_update_form .product_id', '#kt_modal_update');
                    initVariantSelect('#kt_modal_update_form .variant_id', '#kt_modal_update');
                    initUnitSelect('#kt_modal_update_form .base_unit', '#kt_modal_update');
                    initUnitSelect('#kt_modal_update_form .target_unit', '#kt_modal_update');
                }
            })
        }



        // Reusable function to initialize product select2
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

            // Initialize variant select2 when product changes
            $(selector).on('change', function () {
                var $form = $(this).closest('form');
                var $variantSelect;

                if ($form.length) {
                    // Try to find by class .variant_id first (for modals)
                    $variantSelect = $form.find('.variant_id');
                    // If not found, check if it's the filter form which uses ID
                    if ($variantSelect.length === 0) {
                        $variantSelect = $form.find('#variant_id');
                    }
                } else {
                    $variantSelect = $('#variant_id');
                }

                if ($variantSelect && $variantSelect.length) {
                    $variantSelect.val(null).trigger('change');
                }
            });
        }

        function initVariantSelect(selector, dropdownParent) {
            $(selector).select2({
                placeholder: 'Select variant',
                dropdownParent: dropdownParent,
                ajax: {
                    url: '/admin/finance/products/list',
                    dataType: 'json',
                    delay: 300,
                    data: function () {
                        // Find the corresponding product ID
                        var $element = $(this); // 'this' inside select2 data callback is usually the options object, NOT the element. 
                        // Actually select2 data callback context is tricky. Better to use the selector variable.

                        var $form = $(selector).closest('form');
                        var productId = null;

                        if ($form.length) {
                            var $productSelect = $form.find('.product_id');
                            if ($productSelect.length === 0) {
                                $productSelect = $form.find('#product_id');
                            }
                            productId = $productSelect.val();
                        } else {
                            productId = $('#product_id').val();
                        }

                        return {
                            product_id: productId
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


        function initUnitSelect(selector, dropdownParent) {
            $(selector).select2({
                placeholder: 'اختر ...',
                dropdownParent: dropdownParent,
                allowClear: false
            });
        }

        $(function () {
            // Initialize Filter Select2
            // Filter uses IDs #product_id and #variant_id
            initProductSelect('#product_id', '#kt_menu_685c37c8c6d74');
            initVariantSelect('#variant_id', '#kt_menu_685c37c8c6d74');
            // Filter unit select2s are initialized via data-kt-select2="true" in HTML or we can init them here:
            // Since they are basic selects, we can leave them or init them. 
            // Better to init them explicitly if we want to be consistent, but let's stick to fixing the modals.

            // Initialize Add Modal Select2
            $('#kt_modal_add').on('shown.bs.modal', function () {
                initProductSelect('#kt_modal_add_form .product_id', '#kt_modal_add');
                initVariantSelect('#kt_modal_add_form .variant_id', '#kt_modal_add');
                initUnitSelect('#kt_modal_add_form .base_unit', '#kt_modal_add');
                initUnitSelect('#kt_modal_add_form .target_unit', '#kt_modal_add');
            });
        });




    </script>
@endsection