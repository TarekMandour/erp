@extends('admin.layout.master')
@php $route = 'finance.coupon_usages'; @endphp
@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">استخدامات الكوبونات</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600">استخدامات الكوبونات</li>
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
                            <label class="form-label fw-semibold">الكوبون :</label>
                            <select class="form-select form-select-solid" id="fcoupon">
                                <option value="">الكل</option>
                                @foreach($coupons as $coupon)
                                    <option value="{{$coupon->id}}">{{$coupon->code}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-10">
                            <label class="form-label fw-semibold">العميل :</label>
                            <select class="form-select form-select-solid" id="fcustomer">
                                <option value="">الكل</option>
                                @foreach($customers as $customer)
                                    <option value="{{$customer->id}}">{{$customer->name}}</option>
                                @endforeach
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
        <a href="{{route($route.'.create')}}" class="btn btn-dark fw-bold">
            <i class="ki-duotone ki-plus fs-2"></i> إضافة استخدام
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
                    <input type="text" id="search" class="form-control form-control-solid w-250px ps-13" placeholder="البحث بالكود / العميل / رقم الطلب ..." />
                </div>
            </div>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover table-row-dashed fs-6 gy-5" id="kt_table_list">
                    <thead>
                        <tr class="text-start text-dark bg-light-dark fw-bold fs-5 text-uppercase gs-0">
                            <th class="w-50px">#</th>
                            <th>كود الكوبون</th>
                            <th>العميل</th>
                            <th>رقم الطلب</th>
                            <th>مبلغ الخصم</th>
                            <th>تاريخ الاستخدام</th>
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
                d.search    = $('#search').val();
                d.fcoupon   = $('#fcoupon').val();
                d.fcustomer = $('#fcustomer').val();
            }
        },
        lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
        columns: [
            {data: 'DT_RowIndex',      orderable: false, searchable: false},
            {data: 'coupon_code',      orderable: false},
            {data: 'customer_name',    orderable: false},
            {data: 'order_id',         orderable: false},
            {data: 'discount_display', orderable: false},
            {data: 'used_at_display',  orderable: false},
            {data: 'action',           orderable: false, searchable: false},
        ]
    });

    $('#search').on('keyup', function () { table.draw(); });
    $('#filter-form').on('submit', function (e) { e.preventDefault(); table.ajax.reload(); });
    $('#filter-form').on('reset',  function ()  { $(this).find('select').val(''); table.ajax.reload(); });
});
</script>
@endsection
