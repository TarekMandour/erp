@extends('admin.layout.master')

@php
    $route    = 'finance.journal_entries';
    $viewPath = 'finance.journal_entries';
@endphp

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تعديل القيد المحاسبي</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
            </li>
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">القيود المحاسبية</a>
            </li>
            <li class="breadcrumb-item text-gray-600">{{$data->entry_number}}</li>
        </ul>
    </div>
</div>
@endsection

@section('content')
<form action="{{route($route.'.update')}}" method="POST" id="journal_entry_form" class="form d-flex flex-column flex-lg-row">
    @csrf
    <input type="hidden" name="id" value="{{$data->id}}" />
    @include($viewPath.'.form')
</form>
@endsection

@section('script')
<script>
var accountOptions = `@foreach($accounts as $acc)<option value="{{$acc->id}}">{{$acc->code}} - {{$acc->name}}</option>@endforeach`;
var costCenterOptions = `@foreach($costCenters as $cc)<option value="{{$cc->id}}">{{$cc->name}}</option>@endforeach`;

$(function () {

    var rowIndex = $('#items_body tr.item-row').length;

    function buildRow(idx) {
        return `<tr class="item-row">
            <td>
                <select class="form-select form-select-solid form-select-sm account-select" name="items[${idx}][account_tree_id]" required>
                    <option value="">-- الحساب --</option>
                    ${accountOptions}
                </select>
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control form-control-solid form-control-sm debit-input"
                    name="items[${idx}][debit]" value="0.00" placeholder="0.00" />
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control form-control-solid form-control-sm credit-input"
                    name="items[${idx}][credit]" value="0.00" placeholder="0.00" />
            </td>
            <td>
                <select class="form-select form-select-solid form-select-sm" name="items[${idx}][cost_center_id]">
                    <option value="">--</option>
                    ${costCenterOptions}
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-solid form-control-sm"
                    name="items[${idx}][description]" placeholder="وصف" />
            </td>
            <td>
                <button type="button" class="btn btn-icon btn-sm btn-danger btn-remove-row"><i class="bi bi-trash fs-4"></i></button>
            </td>
        </tr>`;
    }

    function recalcTotals() {
        var totalDebit  = 0;
        var totalCredit = 0;
        $('.debit-input').each(function () { totalDebit  += parseFloat($(this).val()) || 0; });
        $('.credit-input').each(function () { totalCredit += parseFloat($(this).val()) || 0; });
        $('#total_debit').text(totalDebit.toFixed(2));
        $('#total_credit').text(totalCredit.toFixed(2));
        var diff = Math.abs(totalDebit - totalCredit);
        if (diff < 0.001) {
            $('#balance_status').html('<span class="badge bg-light-success fs-7"><i class="bi bi-check-circle me-1"></i>القيد متوازن</span>');
        } else {
            $('#balance_status').html('<span class="badge bg-light-danger fs-7">الفارق: ' + diff.toFixed(2) + '</span>');
        }
    }

    recalcTotals();

    $('#btn_add_row').on('click', function () {
        $('#items_body').append(buildRow(rowIndex++));
        recalcTotals();
    });

    $(document).on('click', '.btn-remove-row', function () {
        if ($('#items_body tr.item-row').length <= 2) {
            Swal.fire({ icon: 'warning', title: 'تنبيه', text: 'يجب أن يحتوي القيد على سطرين على الأقل', confirmButtonText: 'موافق' });
            return;
        }
        $(this).closest('tr').remove();
        recalcTotals();
        $('#items_body tr.item-row').each(function (i) {
            $(this).find('[name]').each(function () {
                var name = $(this).attr('name').replace(/items\[\d+\]/, 'items[' + i + ']');
                $(this).attr('name', name);
            });
        });
        rowIndex = $('#items_body tr.item-row').length;
    });

    $(document).on('input change', '.debit-input, .credit-input', function () {
        recalcTotals();
    });

    $('#journal_entry_form').on('submit', function (e) {
        var d = parseFloat($('#total_debit').text()) || 0;
        var c = parseFloat($('#total_credit').text()) || 0;
        if (Math.abs(d - c) > 0.001) {
            e.preventDefault();
            Swal.fire({ icon: 'error', title: 'خطأ', text: 'يجب أن يكون مجموع المدين مساوياً لمجموع الدائن', confirmButtonText: 'موافق' });
        }
    });
});
</script>
@endsection
