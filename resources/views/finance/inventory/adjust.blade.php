@extends('admin.layout.master')
@php $route = 'finance.inventory'; @endphp
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">المخزون</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">المخزون</a></li>
            <li class="breadcrumb-item text-gray-600">تعديل مخزون</li>
        </ul>
    </div>
</div>
@endsection
@section('content')
<div class="content flex-column-fluid" id="kt_content">
    <form method="POST" action="{{route($route.'.store-adjust')}}">
        @csrf
        @if($item)
        <input type="hidden" name="warehouse_id" value="{{$item->warehouse_id}}" />
        <input type="hidden" name="product_id"   value="{{$item->product_id}}" />
        <input type="hidden" name="variant_id"   value="{{$item->variant_id}}" />
        @endif

        <div class="card card-flush py-4">
            <div class="card-header">
                <div class="card-title"><h2>تعديل المخزون</h2></div>
            </div>
            <div class="card-body pt-0">

                {{-- Current Stock Badge (if adjusting existing item) --}}
                @if($item)
                <div class="alert alert-info d-flex align-items-center mb-7">
                    <i class="bi bi-info-circle-fill fs-3 me-3"></i>
                    <div>
                        <strong>{{$item->product->name}}</strong>
                        @if($item->variant)
                            @php $attrs = $item->variant->attributes ?? []; @endphp
                            — <span class="badge badge-light-info">{{ is_array($attrs) ? implode(' / ', array_values($attrs)) : $item->variant->sku }}</span>
                        @endif
                        | المستودع: <strong>{{$item->warehouse->name}}</strong>
                        | الكمية الحالية: <strong>{{ number_format((float)$item->quantity, 3) }}</strong>
                    </div>
                </div>
                @endif

                <div class="row mb-7">
                    {{-- Warehouse --}}
                    <div class="col-md-4 fv-row">
                        <label class="form-label required">المستودع</label>
                        @if($item)
                            <input type="text" class="form-control form-control-solid" value="{{$item->warehouse->name}}" readonly />
                        @else
                            <select class="form-select form-select-solid" name="warehouse_id" id="warehouse_id">
                                <option value="">-- اختر المستودع --</option>
                                @foreach($warehouses as $w)
                                <option value="{{$w->id}}" {{old('warehouse_id') == $w->id ? 'selected' : ''}}>{{$w->name}}</option>
                                @endforeach
                            </select>
                            @error('warehouse_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                        @endif
                    </div>

                    {{-- Product --}}
                    <div class="col-md-4 fv-row">
                        <label class="form-label required">المنتج</label>
                        @if($item)
                            <input type="text" class="form-control form-control-solid" value="{{$item->product->name}}" readonly />
                        @else
                            <select class="form-select form-select-solid" name="product_id" id="product_id">
                                <option value="">-- اختر المنتج --</option>
                                @foreach($products as $p)
                                <option value="{{$p->id}}" data-has-variants="{{$p->has_variants ? '1' : '0'}}" {{old('product_id') == $p->id ? 'selected' : ''}}>
                                    {{$p->name}} ({{$p->sku}})
                                </option>
                                @endforeach
                            </select>
                            @error('product_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                        @endif
                    </div>

                    {{-- Variant (shown dynamically) --}}
                    <div class="col-md-4 fv-row" id="variant-section" style="{{$item && !$item->variant ? 'display:none' : ''}}">
                        <label class="form-label">النوع / المتغير</label>
                        @if($item && $item->variant)
                            @php $attrs = $item->variant->attributes ?? []; @endphp
                            <input type="text" class="form-control form-control-solid"
                                value="{{ is_array($attrs) ? implode(' / ', array_values($attrs)) : $item->variant->sku }}" readonly />
                        @else
                            <select class="form-select form-select-solid" name="variant_id" id="variant_id">
                                <option value="">-- اختر النوع --</option>
                            </select>
                            @error('variant_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                        @endif
                    </div>
                </div>

                <div class="row mb-7">
                    {{-- Type --}}
                    <div class="col-md-3 fv-row">
                        <label class="form-label required">نوع العملية</label>
                        <select class="form-select form-select-solid" name="type" id="type">
                            <option value="in"         {{old('type') == 'in'         ? 'selected' : ''}}>وارد (إضافة)</option>
                            <option value="out"        {{old('type') == 'out'        ? 'selected' : ''}}>صادر (خصم)</option>
                            <option value="adjustment" {{old('type') == 'adjustment' ? 'selected' : ''}}>تعديل (تحديد الكمية)</option>
                        </select>
                        @error('type')<div class="text-danger mt-1">{{$message}}</div>@enderror
                    </div>

                    {{-- Quantity --}}
                    <div class="col-md-3 fv-row">
                        <label class="form-label required" id="qty-label">الكمية</label>
                        <input type="number" step="0.001" min="0.001" class="form-control form-control-solid" name="quantity"
                            value="{{old('quantity')}}" placeholder="0.000" />
                        @error('quantity')<div class="text-danger mt-1">{{$message}}</div>@enderror
                    </div>

                    {{-- Unit Cost --}}
                    <div class="col-md-3 fv-row">
                        <label class="form-label">تكلفة الوحدة</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="unit_cost"
                            value="{{old('unit_cost', 0)}}" placeholder="0.00" />
                    </div>
                </div>

                <div class="row mb-7">
                    <div class="col-md-9 fv-row">
                        <label class="form-label">ملاحظات</label>
                        <textarea class="form-control form-control-solid" name="notes" rows="3"
                            placeholder="سبب التعديل أو ملاحظات ...">{{old('notes')}}</textarea>
                    </div>
                </div>

            </div>
            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <a href="{{route($route.'.index')}}" class="btn btn-light me-3">الغاء</a>
                <button type="submit" class="btn btn-primary">حفظ التعديل</button>
            </div>
        </div>
    </form>
</div>
@endsection
@section('script')
<script>
$(function () {
    // Update label based on type
    $('#type').on('change', function () {
        var labels = {in: 'الكمية المضافة', out: 'الكمية المخصومة', adjustment: 'الكمية الجديدة (مطلق)'};
        $('#qty-label').text(labels[$(this).val()] || 'الكمية');
    });

    // Load variants when product changes
    $('#product_id').on('change', function () {
        var productId = $(this).val();
        $('#variant_id').empty().append('<option value="">-- اختر النوع --</option>');
        $('#variant-section').hide();

        if (!productId) return;

        $.get("{{ route($route.'.get-variants') }}", {product_id: productId}, function (res) {
            if (res.has_variants && res.variants.length) {
                $.each(res.variants, function (i, v) {
                    $('#variant_id').append('<option value="' + v.id + '">' + v.label + ' (' + v.sku + ')</option>');
                });
                $('#variant-section').show();
            }
        });
    });

    // Trigger on page load if old() value is set
    @if(old('product_id'))
    $('#product_id').trigger('change');
    @endif
});
</script>
@endsection
