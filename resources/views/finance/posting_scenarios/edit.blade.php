@extends('admin.layout.master')

@php $route = 'finance.posting_scenarios'; @endphp

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تعديل سيناريو: {{ $data->code }}</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">سيناريوهات الترحيل</a></li>
            <li class="breadcrumb-item text-gray-600">تعديل</li>
        </ul>
    </div>
</div>
@endsection

@section('content')
<div class="content flex-column-fluid" id="kt_content">
    @if($errors->any())
        <div class="alert alert-danger mb-5">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route($route.'.update') }}" id="scenario_form">
        @csrf
        <input type="hidden" name="id" value="{{ $data->id }}" />
        @include('finance.posting_scenarios.form')
        <div class="d-flex justify-content-end gap-2 mt-5">
            <a href="{{ route($route.'.show', $data->id) }}" class="btn btn-secondary">إلغاء</a>
            <button type="submit" class="btn btn-primary">
                <i class="ki-duotone ki-check fs-2"></i> حفظ التعديلات
            </button>
        </div>
    </form>
</div>
@endsection

@section('script')
<script>
$(function () {
    var rowIndex = {{ $data->rules->count() }};

    // ── Amount-field map per operation type ──────────────────────────────────
    var AMOUNT_FIELD_MAP = {
        sales:               [{v:'subtotal',l:'المجموع الفرعي'},{v:'discount',l:'الخصم'},{v:'tax',l:'الضريبة'},{v:'shipping_cost',l:'تكلفة الشحن'},{v:'total',l:'الإجمالي'},{v:'paid',l:'المدفوع'}],
        sales_return:        [{v:'subtotal',l:'المجموع الفرعي'},{v:'discount',l:'الخصم'},{v:'tax',l:'الضريبة'},{v:'shipping_cost',l:'تكلفة الشحن'},{v:'total',l:'الإجمالي'},{v:'paid',l:'المدفوع'}],
        sales_discount:      [{v:'subtotal',l:'المجموع الفرعي'},{v:'discount',l:'الخصم'},{v:'tax',l:'الضريبة'},{v:'shipping_cost',l:'تكلفة الشحن'},{v:'total',l:'الإجمالي'},{v:'paid',l:'المدفوع'}],
        sales_installment:   [{v:'subtotal',l:'المجموع الفرعي'},{v:'discount',l:'الخصم'},{v:'tax',l:'الضريبة'},{v:'shipping_cost',l:'تكلفة الشحن'},{v:'total',l:'الإجمالي'},{v:'paid',l:'المدفوع'}],
        purchase:            [{v:'subtotal',l:'المجموع الفرعي'},{v:'discount',l:'الخصم'},{v:'tax',l:'الضريبة'},{v:'total',l:'الإجمالي'},{v:'paid',l:'المدفوع'}],
        purchase_return:     [{v:'subtotal',l:'المجموع الفرعي'},{v:'discount',l:'الخصم'},{v:'tax',l:'الضريبة'},{v:'total',l:'الإجمالي'},{v:'paid',l:'المدفوع'}],
        purchase_discount:   [{v:'subtotal',l:'المجموع الفرعي'},{v:'discount',l:'الخصم'},{v:'tax',l:'الضريبة'},{v:'total',l:'الإجمالي'},{v:'paid',l:'المدفوع'}],
        inventory_in:        [{v:'quantity',l:'الكمية'},{v:'unit_cost',l:'تكلفة الوحدة'}],
        inventory_out:       [{v:'quantity',l:'الكمية'},{v:'unit_cost',l:'تكلفة الوحدة'}],
        inventory_transfer:  [{v:'quantity',l:'الكمية'}],
        inventory_adjustment:[{v:'quantity',l:'الكمية'},{v:'unit_cost',l:'تكلفة الوحدة'}],
        inventory_write_off: [{v:'quantity',l:'الكمية'},{v:'unit_cost',l:'تكلفة الوحدة'}],
        inventory_revaluation:[{v:'quantity',l:'الكمية'},{v:'unit_cost',l:'تكلفة الوحدة'}],
        wallet_deposit:      [{v:'debit',l:'مدين'},{v:'credit',l:'دائن'},{v:'balance',l:'الرصيد'}],
        wallet_withdraw:     [{v:'debit',l:'مدين'},{v:'credit',l:'دائن'},{v:'balance',l:'الرصيد'}],
        wallet_payment:      [{v:'debit',l:'مدين'},{v:'credit',l:'دائن'},{v:'balance',l:'الرصيد'}],
        wallet_refund:       [{v:'debit',l:'مدين'},{v:'credit',l:'دائن'},{v:'balance',l:'الرصيد'}],
    };
    var AMOUNT_FIELD_DEFAULT = [{v:'total_amount',l:'إجمالي المبلغ'}];

    function getAmountFields(opType) {
        return AMOUNT_FIELD_MAP[opType] || AMOUNT_FIELD_DEFAULT;
    }

    function populateAmountFieldSelect($select, opType, savedValue) {
        var fields = getAmountFields(opType);
        $select.empty().append('<option value="">-- --</option>');
        $.each(fields, function (i, f) {
            $select.append($('<option>', {value: f.v, text: f.l}));
        });
        if (savedValue) {
            if ($select.find('option[value="' + savedValue + '"]').length === 0) {
                $select.append($('<option>', {value: savedValue, text: savedValue}));
            }
            $select.val(savedValue);
        }
    }

    function repopulateAllAmountFieldSelects() {
        var opType = $('[name=operation_type]').val();
        $('#rules_body .amount-field-select').each(function () {
            var saved = $(this).val() || $(this).data('value') || '';
            populateAmountFieldSelect($(this), opType, saved);
        });
    }

    $('[name=operation_type]').on('change', repopulateAllAmountFieldSelects);
    // ─────────────────────────────────────────────────────────────────────────

    $('#btn_add_rule').on('click', function () {
        var tpl = document.getElementById('rule_row_template').innerHTML;
        tpl = tpl.replace(/__IDX__/g, rowIndex);
        rowIndex++;
        var $row = $(tpl);
        $('#rules_body').append($row);
        bindRowEvents($row);
        populateAmountFieldSelect($row.find('.amount-field-select'), $('[name=operation_type]').val(), '');
    });

    function bindRowEvents($row) {
        $row.find('.btn-remove-rule').on('click', function () {
            $(this).closest('tr').remove();
        });
        $row.find('.cc-source-select').on('change', function () {
            var $cell = $(this).closest('tr').find('.fixed-cc-cell');
            $(this).val() === 'fixed' ? $cell.show() : $cell.hide();
        });
    }

    // bind existing rows and populate their selects
    $('#rules_body tr').each(function () { bindRowEvents($(this)); });
    repopulateAllAmountFieldSelects();

    // prevent double-submit
    $('#scenario_form').on('submit', function () {
        $(this).find('[type=submit]').prop('disabled', true);
    });
});
</script>
@endsection
