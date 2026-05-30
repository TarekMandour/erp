{{-- Shared form fields for create & edit --}}

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-7">
    <ul class="mb-0">@foreach($errors->all() as $err)<li>{{$err}}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Row 1: Product + Variant --}}
<div class="row mb-7">
    <div class="col-md-5 fv-row">
        <label class="form-label required">المنتج <span class="text-muted fs-7">(يمكن تحديد متغير محدد أو تركه عاماً)</span></label>
        <select class="form-select form-select-solid" name="product_id" id="product_id">
            <option value="">-- اختر المنتج --</option>
            @foreach($products as $p)
            <option value="{{$p->id}}" {{old('product_id', $data->product_id ?? '') == $p->id ? 'selected' : ''}}>
                {{$p->name}} ({{$p->sku}})
            </option>
            @endforeach
        </select>
        @error('product_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-5 fv-row">
        <label class="form-label">المتغير <span class="text-muted fs-7">(اتركه فارغاً لتطبيق السعر على المنتج كاملاً)</span></label>
        <select class="form-select form-select-solid" name="variant_id" id="variant_id">
            <option value="">-- عام (كل متغيرات المنتج) --</option>
            @if(isset($variants))
                @foreach($variants as $v)
                @php $attrs = $v->attributes ?? []; $label = is_array($attrs) && count($attrs) ? implode(' / ', array_values($attrs)) : $v->sku; @endphp
                <option value="{{$v->id}}" data-price="{{$v->selling_price}}" {{old('variant_id', $data->variant_id ?? '') == $v->id ? 'selected' : ''}}>
                    {{$label}} ({{$v->sku}})
                </option>
                @endforeach
            @endif
        </select>
        @error('variant_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
        <div id="base-price-hint" class="text-muted fs-7 mt-1" style="display:none">
            السعر الافتراضي للمتغير: <strong id="base-price-val"></strong>
        </div>
    </div>
    <div class="col-md-2 fv-row">
        <label class="form-label">الأولوية</label>
        <input type="number" min="0" class="form-control form-control-solid" name="priority"
            value="{{old('priority', $data->priority ?? 0)}}" />
        @error('priority')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
</div>

{{-- Row 2: Price + Sale Price --}}
<div class="row mb-7">
    <div class="col-md-3 fv-row">
        <label class="form-label required">السعر الأساسي</label>
        <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="price"
            id="price" value="{{old('price', $data->price ?? '')}}" placeholder="0.00" />
        @error('price')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-3 fv-row">
        <label class="form-label">سعر التخفيض</label>
        <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="sale_price"
            value="{{old('sale_price', $data->sale_price ?? '')}}" placeholder="اتركه فارغاً إن لم يكن هناك تخفيض" />
        @error('sale_price')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-3 fv-row">
        <label class="form-label">بداية التخفيض</label>
        <input type="date" class="form-control form-control-solid" name="sale_start"
            value="{{old('sale_start', isset($data) && $data->sale_start ? $data->sale_start->format('Y-m-d') : '')}}" />
    </div>
    <div class="col-md-3 fv-row">
        <label class="form-label">نهاية التخفيض</label>
        <input type="date" class="form-control form-control-solid" name="sale_end"
            value="{{old('sale_end', isset($data) && $data->sale_end ? $data->sale_end->format('Y-m-d') : '')}}" />
    </div>
</div>

{{-- Row 3: Customer + Valid Dates --}}
<div class="row mb-7">
    <div class="col-md-4 fv-row">
        <label class="form-label">تخصيص للعملاء <span class="text-muted fs-7">(يمكن اختيار أكثر من عميل)</span></label>
        @php
            $selectedCustomerIds = old('customer_ids', isset($data) ? ($data->customer_ids ?? ($data->customer_id ? [$data->customer_id] : [])) : []);
        @endphp
        <select class="form-select form-select-solid" name="customer_ids[]" id="customer_ids" multiple size="5">
            @foreach($customers as $c)
            <option value="{{$c->id}}" {{in_array($c->id, (array)$selectedCustomerIds) ? 'selected' : ''}}>
                {{$c->name}}
            </option>
            @endforeach
        </select>
        <div class="text-muted fs-7 mt-1">
            <i class="bi bi-info-circle me-1"></i>اتركه بدون اختيار لتطبيق السعر على جميع العملاء. Ctrl+Click لاختيار متعدد.
        </div>
        @error('customer_ids')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-4 fv-row">
        <label class="form-label">صالح من</label>
        <input type="date" class="form-control form-control-solid" name="valid_from"
            value="{{old('valid_from', isset($data) && $data->valid_from ? $data->valid_from->format('Y-m-d') : '')}}" />
    </div>
    <div class="col-md-4 fv-row">
        <label class="form-label">صالح حتى</label>
        <input type="date" class="form-control form-control-solid" name="valid_to"
            value="{{old('valid_to', isset($data) && $data->valid_to ? $data->valid_to->format('Y-m-d') : '')}}" />
    </div>
</div>

{{-- Row 4: Active --}}
<div class="row mb-7">
    <div class="col-md-3 fv-row">
        <div class="form-check form-switch mt-8">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                {{old('is_active', $data->is_active ?? true) ? 'checked' : ''}}>
            <label class="form-check-label fw-semibold" for="is_active">مفعّل</label>
        </div>
    </div>
</div>

{{-- Quantity Tiers --}}
<div class="separator separator-dashed mb-7"></div>
<div class="mb-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">شرائح الأسعار حسب الكمية <span class="text-muted fs-7">(اختياري)</span></h4>
        <button type="button" class="btn btn-sm btn-light-primary" id="add-tier">
            <i class="bi bi-plus"></i> إضافة شريحة
        </button>
    </div>
    <div id="tiers-container">
        @if(isset($data) && !empty($data->quantity_prices))
            @foreach($data->quantity_prices as $i => $tier)
            <div class="row mb-3 tier-row">
                <div class="col-md-4 fv-row">
                    <label class="form-label">الحد الأدنى للكمية</label>
                    <input type="number" step="0.001" min="0" class="form-control form-control-solid"
                        name="tier_qty[]" value="{{$tier['min_qty']}}" placeholder="مثال: 10" />
                </div>
                <div class="col-md-4 fv-row">
                    <label class="form-label">السعر عند هذه الكمية</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-solid"
                        name="tier_price[]" value="{{$tier['price']}}" placeholder="0.00" />
                </div>
                <div class="col-md-2 d-flex align-items-end pb-1">
                    <button type="button" class="btn btn-sm btn-light-danger remove-tier">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            @endforeach
        @endif
    </div>
    <div id="tier-template" class="d-none">
        <div class="row mb-3 tier-row">
            <div class="col-md-4 fv-row">
                <label class="form-label">الحد الأدنى للكمية</label>
                <input type="number" step="0.001" min="0" class="form-control form-control-solid"
                    name="tier_qty[]" placeholder="مثال: 10" />
            </div>
            <div class="col-md-4 fv-row">
                <label class="form-label">السعر عند هذه الكمية</label>
                <input type="number" step="0.01" min="0" class="form-control form-control-solid"
                    name="tier_price[]" placeholder="0.00" />
            </div>
            <div class="col-md-2 d-flex align-items-end pb-1">
                <button type="button" class="btn btn-sm btn-light-danger remove-tier">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="text-muted fs-7 mt-2">
        <i class="bi bi-info-circle me-1"></i>
        مثال: كمية ≥ 5 → السعر 90، كمية ≥ 10 → السعر 85. الأسعار تُطبق تلقائياً عند إنشاء فاتورة.
    </div>
</div>
