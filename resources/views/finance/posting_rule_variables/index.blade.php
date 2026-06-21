@extends('admin.layout.master')

@php $route = 'finance.posting_rule_variables'; @endphp

@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">متغيرات قواعد الترحيل</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600">متغيرات قواعد الترحيل</li>
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
            <div class="card-title d-flex flex-wrap gap-2">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5"><span class="path1"></span><span class="path2"></span></i>
                    <input type="text" id="search" class="form-control form-control-solid w-250px ps-13" placeholder="البحث ..." />
                </div>
                <select id="filter_scenario" class="form-select form-select-solid w-200px">
                    <option value="">كل السيناريوهات</option>
                    @foreach($scenarios as $sc)
                        <option value="{{ $sc->id }}">{{ $sc->code }} - {{ $sc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="card-toolbar">
                <div class="d-flex justify-content-end">
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
                            <th>السيناريو / المجموعة</th>
                            <th>اسم المتغير</th>
                            <th>نوع المصدر</th>
                            <th>قيمة المصدر</th>
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
                d.search      = $('#search').val();
                d.scenario_id = $('#filter_scenario').val();
            }
        },
        lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
        columns: [
            {data: 'DT_RowIndex',        name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'checkbox',           name: 'checkbox', orderable: false, searchable: false},
            {data: 'scenario_info',      name: 'scenario_info', orderable: false},
            {data: 'variable_name',      name: 'variable_name'},
            {data: 'source_type_badge',  name: 'source_type'},
            {data: 'source_value',       name: 'source_value'},
            {data: 'action',             name: 'action', orderable: false, searchable: false},
        ]
    });

    $('#search').on('keyup', function () { table.draw(); });
    $('#filter_scenario').on('change', function () { table.draw(); });

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
                showCancelButton: true,
                confirmButtonText: 'تأكيد الحذف',
                cancelButtonText: 'الغاء',
                customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-secondary' }
            }).then(function (result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route($route.'.delete') }}",
                        type: "POST",
                        data: { _token: token, ids: checkIDs },
                        success: function () {
                            table.draw();
                            Swal.fire({ title: 'تم الحذف', icon: 'success', timer: 1500, showConfirmButton: false });
                        }
                    });
                }
            });
        } else {
            Swal.fire({ title: 'يرجى تحديد عنصر للحذف', icon: 'warning', timer: 1500, showConfirmButton: false });
        }
    });
});
</script>
@endsection
