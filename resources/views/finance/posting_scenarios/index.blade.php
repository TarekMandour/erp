@extends('admin.layout.master')

@php
    $route = 'finance.posting_scenarios';
@endphp

@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">سيناريوهات الترحيل</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
            </li>
            <li class="breadcrumb-item text-gray-600">سيناريوهات الترحيل</li>
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
                    <input type="text" id="search" class="form-control form-control-solid w-250px ps-13" placeholder="البحث بالكود أو الاسم ..." />
                </div>
                <select id="filter_operation_type" class="form-select form-select-solid w-175px">
                    <option value="">كل أنواع العمليات</option>
                    <optgroup label="المبيعات">
                        <option value="sales">مبيعات</option>
                        <option value="sales_return">مرتجع مبيعات</option>
                        <option value="sales_discount">خصم مبيعات</option>
                        <option value="sales_installment">مبيعات بالتقسيط</option>
                    </optgroup>
                    <optgroup label="المشتريات">
                        <option value="purchase">مشتريات</option>
                        <option value="purchase_return">مرتجع مشتريات</option>
                        <option value="purchase_discount">خصم مشتريات</option>
                    </optgroup>
                    <optgroup label="المخزون">
                        <option value="inventory_in">وارد مخزون</option>
                        <option value="inventory_out">صادر مخزون</option>
                        <option value="inventory_transfer">تحويل مخزون</option>
                        <option value="inventory_adjustment">تسوية مخزون</option>
                        <option value="inventory_write_off">إتلاف مخزون</option>
                        <option value="inventory_revaluation">إعادة تقييم مخزون</option>
                    </optgroup>
                    <optgroup label="الصندوق">
                        <option value="cash_deposit">إيداع صندوق</option>
                        <option value="cash_withdraw">سحب صندوق</option>
                        <option value="cash_transfer">تحويل صندوق</option>
                    </optgroup>
                    <optgroup label="البنوك">
                        <option value="bank_deposit">إيداع بنك</option>
                        <option value="bank_withdraw">سحب بنك</option>
                        <option value="bank_transfer">تحويل بنك</option>
                    </optgroup>
                    <optgroup label="الشيكات">
                        <option value="cheque_received">شيك مستلم</option>
                        <option value="cheque_issued">شيك صادر</option>
                        <option value="cheque_cashed">صرف شيك</option>
                    </optgroup>
                    <optgroup label="المحفظة">
                        <option value="wallet_deposit">إيداع محفظة</option>
                        <option value="wallet_withdraw">سحب محفظة</option>
                        <option value="wallet_payment">دفع محفظة</option>
                        <option value="wallet_refund">استرداد محفظة</option>
                    </optgroup>
                    <optgroup label="العملاء">
                        <option value="customer_payment">تحصيل عميل</option>
                        <option value="customer_credit_note">إشعار دائن عميل</option>
                        <option value="customer_debit_note">إشعار مدين عميل</option>
                    </optgroup>
                    <optgroup label="الموردين">
                        <option value="supplier_payment">دفع مورد</option>
                        <option value="supplier_credit_note">إشعار دائن مورد</option>
                        <option value="supplier_debit_note">إشعار مدين مورد</option>
                    </optgroup>
                    <optgroup label="المصروفات والإيرادات">
                        <option value="expense">مصروف</option>
                        <option value="revenue">إيراد</option>
                        <option value="accrued_expense">مصروف مستحق</option>
                        <option value="prepaid_expense">مصروف مدفوع مقدماً</option>
                        <option value="depreciation">إهلاك</option>
                        <option value="amortization">استهلاك</option>
                    </optgroup>
                    <optgroup label="الرواتب">
                        <option value="salary">راتب</option>
                        <option value="salary_advance">سلفة راتب</option>
                        <option value="salary_loan">قرض موظف</option>
                        <option value="overtime">عمل إضافي</option>
                        <option value="bonus">مكافأة</option>
                        <option value="commission">عمولة</option>
                    </optgroup>
                    <optgroup label="الأصول الثابتة">
                        <option value="asset_purchase">شراء أصل</option>
                        <option value="asset_sale">بيع أصل</option>
                        <option value="asset_disposal">استبعاد أصل</option>
                        <option value="asset_depreciation">إهلاك أصل</option>
                    </optgroup>
                    <optgroup label="الضرائب">
                        <option value="vat_input">ضريبة مدخلات</option>
                        <option value="vat_output">ضريبة مخرجات</option>
                        <option value="vat_payment">دفع ضريبة</option>
                        <option value="vat_refund">استرداد ضريبة</option>
                        <option value="income_tax">ضريبة دخل</option>
                        <option value="withholding_tax">خصم المصدر</option>
                    </optgroup>
                    <optgroup label="القروض">
                        <option value="loan_received">استلام قرض</option>
                        <option value="loan_payment">سداد قرض</option>
                        <option value="loan_interest">فوائد قرض</option>
                    </optgroup>
                    <optgroup label="رأس المال">
                        <option value="capital_increase">زيادة رأس المال</option>
                        <option value="capital_decrease">تخفيض رأس المال</option>
                        <option value="dividend_paid">توزيعات أرباح</option>
                    </optgroup>
                    <optgroup label="التحويلات الداخلية">
                        <option value="internal_transfer">تحويل داخلي</option>
                        <option value="cost_allocation">تخصيص تكلفة</option>
                        <option value="profit_transfer">ترحيل أرباح</option>
                    </optgroup>
                    <optgroup label="أرصدة افتتاحية وتسويات">
                        <option value="opening_balance">رصيد افتتاحي</option>
                        <option value="adjustment">تسوية</option>
                        <option value="revaluation">إعادة تقييم</option>
                        <option value="closing_entry">قيد إقفال</option>
                    </optgroup>
                    <optgroup label="عمليات أخرى">
                        <option value="refund">استرداد</option>
                        <option value="write_off">شطب</option>
                        <option value="reversal">عكس قيد</option>
                        <option value="correction">تصحيح</option>
                    </optgroup>
                </select>
                <select id="filter_is_active" class="form-select form-select-solid w-150px">
                    <option value="">كل الحالات</option>
                    <option value="1">نشط</option>
                    <option value="0">متوقف</option>
                </select>
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
                <table class="table align-middle table-hover table-row-dashed text-start fs-6 gy-5" id="kt_table_list">
                    <thead>
                        <tr class="text-start text-dark bg-light-dark fw-bold fs-5 gs-0">
                            <th class="w-40px">#</th>
                            <th class="w-40px">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_list .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>الكود</th>
                            <th>الاسم</th>
                            <th>نوع العملية</th>
                            <th>الأولوية</th>
                            <th>القواعد</th>
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
                d.search           = $('#search').val();
                d.operation_type   = $('#filter_operation_type').val();
                d.is_active        = $('#filter_is_active').val();
            }
        },
        lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
        columns: [
            {data: 'DT_RowIndex',           name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'checkbox',              name: 'checkbox', orderable: false, searchable: false},
            {data: 'code_link',             name: 'code'},
            {data: 'name',                  name: 'name'},
            {data: 'operation_type_label',  name: 'operation_type'},
            {data: 'priority',              name: 'priority'},
            {data: 'rules_count',           name: 'rules_count'},
            {data: 'is_active_badge',       name: 'is_active'},
            {data: 'action',                name: 'action', orderable: false, searchable: false},
        ]
    });

    $('#search').on('keyup', function () { table.draw(); });
    $('#filter_operation_type, #filter_is_active').on('change', function () { table.draw(); });

    $("#btn_export").click(function () {
        var qs = 'operation_type=' + $('#filter_operation_type').val();
        window.location.href = "{{ route($route.'.export') }}?" + qs;
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
