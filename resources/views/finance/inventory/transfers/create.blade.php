@extends('admin.layout.master')
@php $route = 'finance.inventory.transfers'; @endphp
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تحويل مخزون جديد</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route('finance.inventory.index')}}" class="text-gray-600 text-hover-primary">المخزون</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">تحويلات المخزون</a></li>
            <li class="breadcrumb-item text-gray-600">تحويل جديد</li>
        </ul>
    </div>
</div>
@endsection
@section('content')
<div class="content flex-column-fluid" id="kt_content">
    <form method="POST" action="{{route($route.'.store')}}">
        @csrf
        <div class="card card-flush py-4">
            <div class="card-header">
                <div class="card-title"><h2>تحويل المخزون بين المستودعات</h2></div>
            </div>
            <div class="card-body pt-0">

                @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-7">
                    <ul class="mb-0">
                        @foreach($errors->all() as $err)
                        <li>{{$err}}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                {{-- Row 1: Warehouses --}}
                <div class="row mb-7">
                    <div class="col-md-5 fv-row">
                        <label class="form-label required">من المستودع (المصدر)</label>
                        <select class="form-select form-select-solid" name="from_warehouse_id" id="from_warehouse_id" data-kt-select2="true" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                            <option value="">-- اختر المستودع المصدر --</option>
                            @foreach($warehouses as $w)
                            <option value="{{$w->id}}" {{old('from_warehouse_id') == $w->id ? 'selected' : ''}}>{{$w->name}}</option>
                            @endforeach
                        </select>
                        @error('from_warehouse_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                    </div>

                    <div class="col-md-2 d-flex align-items-end justify-content-center pb-2">
                        <i class="bi bi-arrow-left-right fs-1 text-primary"></i>
                    </div>

                    <div class="col-md-5 fv-row">
                        <label class="form-label required">إلى المستودع (الوجهة)</label>
                        <select class="form-select form-select-solid" name="to_warehouse_id" id="to_warehouse_id" data-kt-select2="true" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                            <option value="">-- اختر المستودع الوجهة --</option>
                            @foreach($warehouses as $w)
                            <option value="{{$w->id}}" {{old('to_warehouse_id') == $w->id ? 'selected' : ''}}>{{$w->name}}</option>
                            @endforeach
                        </select>
                        @error('to_warehouse_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                    </div>
                </div>

                {{-- Row 2: Product / Variant --}}
                <div class="row mb-7">
                    <div class="col-md-5 fv-row">
                        <label class="form-label required">المنتج</label>
                        <select class="form-select form-select-solid" name="product_id" id="product_id" data-allow-clear="false">
                        </select>
                        @error('product_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                    </div>

                    <div class="col-md-4 fv-row" id="variant-section" style="display:none">
                        <label class="form-label">النوع / المتغير</label>
                        <select class="form-select form-select-solid" name="variant_id" id="variant_id" data-kt-select2="true" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                            <option value="">-- اختر النوع --</option>
                        </select>
                        @error('variant_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                    </div>

                    <div class="col-md-3 fv-row">
                        <label class="form-label">الوحدة</label>
                        <select class="form-select form-select-solid" name="unit_conversion_id" id="unit_conversion_id">
                            <option value="">الوحدة الأساسية</option>
                        </select>
                        @error('unit_conversion_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                    </div>
                </div>

                {{-- Row 3: Stock info + Quantity --}}
                <div class="row mb-7">
                    <div class="col-md-4 fv-row">
                        <label class="form-label required">الكمية المحولة</label>
                        <input type="number" step="0.001" min="0.001" class="form-control form-control-solid"
                            name="quantity" id="quantity" value="{{old('quantity')}}" placeholder="0.000" />
                        @error('quantity')<div class="text-danger mt-1">{{$message}}</div>@enderror
                    </div>

                    <div class="col-md-4 fv-row" id="available-section" style="display:none">
                        <label class="form-label">الرصيد المتاح في المستودع المصدر</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light-info"><i class="bi bi-boxes text-info"></i></span>
                            <input type="text" class="form-control form-control-solid" id="available_qty" readonly placeholder="—" />
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="row mb-7">
                    <div class="col-md-9 fv-row">
                        <label class="form-label">ملاحظات</label>
                        <textarea class="form-control form-control-solid" name="notes" rows="3"
                            placeholder="سبب التحويل أو أي ملاحظات إضافية ...">{{old('notes')}}</textarea>
                    </div>
                </div>

            </div>
            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <a href="{{route($route.'.index')}}" class="btn btn-light me-3">الغاء</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-arrow-left-right me-1"></i> تنفيذ التحويل
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
@section('script')
<script>
$(function () {
    var getVariantsUrl = "{{ route('finance.inventory.get-variants') }}";
    var getStockUrl    = "{{ url('admin/finance/inventory/get-stock') }}";
    var getUnitsUrl    = "{{ route('finance.inventory.transfers.ajax.units') }}";

    function loadAvailableStock() {
        var productId   = $('#product_id').val();
        var variantId   = $('#variant_id').val();
        var warehouseId = $('#from_warehouse_id').val(); 

        if (!productId || !warehouseId) {
            $('#available-section').hide();
            $('#available_qty').val('');
            return;
        }

        $.get(getStockUrl, {
            product_id:   productId,
            variant_id:   variantId || '',
            warehouse_id: warehouseId
        }, function (res) {
            console.log(res);
            $('#available_qty').val(res.quantity ?? '0.000');
            $('#available-section').show();
        }).fail(function () {
            $('#available_qty').val('—');
            $('#available-section').show();
        });
    }

    // Init product Select2 with AJAX search

    $('#product_id').select2({
        dir: 'rtl',
        placeholder: 'ابحث عن منتج ...',
        allowClear: true,
        ajax: {
            url: "{{ route('finance.inventory.ajax.products') }}",
            dataType: 'json',
            delay: 300,
            data: function (p) { return { search: p.term, page: p.page || 1 }; },
            processResults: function (d) { return { results: d.results, pagination: d.pagination }; },
            cache: true
        }
    });


    // Load variants when product changes
    $('#product_id').on('change', function () {
        var productId = $(this).val();
        $('#variant_id').empty().append('<option value="">-- اختر النوع --</option>');
        $('#variant-section').hide();
        loadUnitConversions(productId, null);

        if (!productId) return;

        $.get("{{ route('finance.inventory.get-variants') }}", {product_id: productId}, function (res) {
            if (res.has_variants && res.variants.length) {
                $.each(res.variants, function (i, v) {
                    $('#variant_id').append('<option value="' + v.id + '">' + v.label + ' (' + v.sku + ')</option>');
                });
                $('#variant-section').show();
            }
        });
    });

    function loadUnitConversions(productId, variantId) {
        var $sel = $('#unit_conversion_id');
        $sel.find('option:not([value=""])').remove();
        if (!productId) return;

        $.get(getUnitsUrl, {product_id: productId, variant_id: variantId || ''}, function (res) {
            if (res.results && res.results.length) {
                $.each(res.results, function (i, u) {
                    var $opt = $('<option>', {value: u.id}).text(u.text);
                    if (u.is_default) $opt.attr('selected', true);
                    $sel.append($opt);
                });
            }
        });
    }

    // Reload unit conversions when variant changes
    $('#variant_id').on('change', function () {
        loadUnitConversions($('#product_id').val(), $(this).val());
        loadAvailableStock();
    });

    // Reload stock when source warehouse changes
    $('#from_warehouse_id').on('change', function () {
        loadAvailableStock();
    });
});
</script>
@endsection
