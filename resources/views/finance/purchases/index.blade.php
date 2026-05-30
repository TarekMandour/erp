@extends('admin.layout.master')
@php $route = 'finance.purchases'; @endphp
@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">المشتريات</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600">المشتريات</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
        {{-- Filter dropdown --}}
        <div class="me-3">
            <a href="#" class="btn btn-light fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                <i class="ki-duotone ki-filter fs-5 text-gray-500 me-1"><span class="path1"></span><span class="path2"></span></i>الفلتر
            </a>
            <div class="menu menu-sub menu-sub-dropdown w-250px w-md-320px" data-kt-menu="true">
                <div class="px-7 py-5"><div class="fs-5 text-gray-900 fw-bold">الفلتر</div></div>
                <div class="separator border-gray-200"></div>
                <div class="px-7 py-5">
                    <form id="filter-form">
                        <div class="mb-5">
                            <label class="form-label fw-semibold">المورد :</label>
                            <select class="form-select form-select-solid" id="fsupplier">
                                <option value="">الكل</option>
                                @foreach($suppliers as $s)
                                    <option value="{{$s->id}}">{{$s->company_name ?: $s->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label fw-semibold">الحالة :</label>
                            <select class="form-select form-select-solid" id="fstatus">
                                <option value="">الكل</option>
                                @foreach(\App\Models\Finance\Purchase::$statusLabels as $key => $label)
                                    <option value="{{$key}}">{{$label}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label fw-semibold">نوع الدفع :</label>
                            <select class="form-select form-select-solid" id="fpayment">
                                <option value="">الكل</option>
                                @foreach(\App\Models\Finance\Purchase::$paymentTypeLabels as $key => $label)
                                    <option value="{{$key}}">{{$label}}</option>
                                @endforeach
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
                            <button type="reset" class="btn btn-sm btn-light me-2" data-kt-menu-dismiss="true">إلغاء</button>
                            <button type="submit" class="btn btn-sm btn-primary" data-kt-menu-dismiss="true">تطبيق</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        {{-- Import --}}
        <button type="button" class="btn btn-light-success fw-bold me-3" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="ki-duotone ki-file-up fs-3 me-1"><span class="path1"></span><span class="path2"></span></i> استيراد
        </button>
        {{-- Add new --}}
        <a href="{{route($route.'.create')}}" class="btn btn-dark fw-bold">
            <i class="ki-duotone ki-plus fs-2"></i> فاتورة شراء جديدة
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
    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show mb-5">
        {{session('warning')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-5">
        {{session('error')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5"><span class="path1"></span><span class="path2"></span></i>
                    <input type="text" id="search" class="form-control form-control-solid w-250px ps-13" placeholder="البحث برقم الفاتورة ..." />
                </div>
            </div>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover table-row-dashed fs-6 gy-5" id="kt_table_list">
                    <thead>
                        <tr class="text-start text-dark bg-light-dark fw-bold fs-5 text-uppercase gs-0">
                            <th class="w-50px">#</th>
                            <th>رقم الفاتورة</th>
                            <th>المورد</th>
                            <th>المستودع</th>
                            <th>التاريخ</th>
                            <th>نوع الدفع</th>
                            <th>الإجمالي</th>
                            <th>المدفوع</th>
                            <th>المتبقي</th>
                            <th>الحالة</th>
                            <th class="min-w-80px">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 fw-semibold"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">استيراد فواتير شراء من Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{route($route.'.import')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-center mb-5">
                        <i class="ki-duotone ki-information fs-2 me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        <div>
                            يمكنك تحميل
                            <a href="{{route($route.'.template')}}" class="fw-bold text-primary">قالب الملف</a>
                            لمعرفة التنسيق الصحيح.
                        </div>
                    </div>
                    <div class="fv-row mb-0">
                        <label class="form-label required">اختر الملف (xlsx / xls / csv)</label>
                        <input type="file" name="file" class="form-control form-control-solid" accept=".xlsx,.xls,.csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success fw-bold">
                        <i class="ki-duotone ki-file-up fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                        استيراد
                    </button>
                </div>
            </form>
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
                d.search     = $('#search').val();
                d.fsupplier  = $('#fsupplier').val();
                d.fstatus    = $('#fstatus').val();
                d.fpayment   = $('#fpayment').val();
                d.fdate_from = $('#fdate_from').val();
                d.fdate_to   = $('#fdate_to').val();
            }
        },
        lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
        columns: [
            {data: 'DT_RowIndex',     orderable: false, searchable: false},
            {data: 'purchase_number', orderable: false},
            {data: 'supplier_name',   orderable: false},
            {data: 'warehouse_name',  orderable: false},
            {data: 'date',            orderable: false},
            {data: 'payment_label',   orderable: false},
            {data: 'total_fmt',       orderable: false},
            {data: 'paid_fmt',        orderable: false},
            {data: 'remaining_fmt',   orderable: false},
            {data: 'status_badge',    orderable: false},
            {data: 'action',          orderable: false, searchable: false},
        ]
    });

    $('#search').on('keyup', function () { table.draw(); });
    $('#filter-form').on('submit', function (e) { e.preventDefault(); table.ajax.reload(); });
    $('#filter-form').on('reset', function () {
        $(this).find('select').val('');
        $(this).find('input[type=date]').val('');
        table.ajax.reload();
    });
});
</script>
@endsection
