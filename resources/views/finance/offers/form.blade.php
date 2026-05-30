{{-- Shared form fields for offers create & edit --}}

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-7">
    <ul class="mb-0">@foreach($errors->all() as $err)<li>{{$err}}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Row 1: Name + Priority --}}
<div class="row mb-7">
    <div class="col-md-8 fv-row">
        <label class="form-label required">اسم العرض</label>
        <input type="text" class="form-control form-control-solid" name="name"
            value="{{old('name', $data?->name ?? '')}}" placeholder="مثال: عرض صيف 2026" />
        @error('name')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-4 fv-row">
        <label class="form-label">
            الأولوية
            <span class="ms-1" data-bs-toggle="tooltip" title="عند تداخل أكثر من عرض، يُطبَّق العرض ذو الأولوية الأعلى أولاً">
                <i class="ki-duotone ki-information-5 text-gray-500 fs-6"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            </span>
        </label>
        <input type="number" min="0" max="999" class="form-control form-control-solid" name="priority"
            value="{{old('priority', $data?->priority ?? 0)}}" placeholder="0" />
    </div>
</div>

{{-- Row 2: Description --}}
<div class="row mb-7">
    <div class="col-12 fv-row">
        <label class="form-label">الوصف <span class="text-muted fs-7">(ملاحظات داخلية)</span></label>
        <textarea class="form-control form-control-solid" name="description" rows="2"
            placeholder="وصف اختياري لهذا العرض ...">{{old('description', $data?->description ?? '')}}</textarea>
    </div>
</div>

{{-- Row 3: Type + Applies To --}}
<div class="row mb-7">
    <div class="col-md-6 fv-row">
        <label class="form-label required">نوع العرض</label>
        <select class="form-select form-select-solid" name="type" id="offer_type">
            @foreach(\App\Models\Finance\Offer::$typeLabels as $key => $label)
                <option value="{{$key}}" {{old('type', $data?->type ?? '') === $key ? 'selected' : ''}}>
                    {{$label}}
                </option>
            @endforeach
        </select>
        @error('type')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-6 fv-row">
        <label class="form-label required">يُطبَّق على</label>
        <select class="form-select form-select-solid" name="applies_to">
            @foreach(\App\Models\Finance\Offer::$appliesToLabels as $key => $label)
                <option value="{{$key}}" {{old('applies_to', $data?->applies_to ?? 'order') === $key ? 'selected' : ''}}>
                    {{$label}}
                </option>
            @endforeach
        </select>
        @error('applies_to')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
</div>

{{-- Items selector (shown when applies_to = product or category) --}}
<div class="row mb-7" id="offer-items-row" style="display:none">
    <div class="col-12 fv-row">
        <label class="form-label required" id="offer-items-label">المنتجات</label>
        {{-- Single select2 element — name/placeholder/data updated by JS on applies_to change --}}
        <select class="form-select form-select-solid" id="offer_items_select" name="item_ids[]" multiple="multiple"
            data-placeholder="ابحث وحدد ...">
            @if(isset($data) && $data->applies_to === 'product')
                @foreach($data->products as $p)
                    <option value="{{$p->id}}" selected>{{$p->name}}</option>
                @endforeach
            @elseif(isset($data) && $data->applies_to === 'category')
                @foreach($data->categories as $c)
                    <option value="{{$c->id}}" selected>{{$c->name}}</option>
                @endforeach
            @endif
        </select>
        @error('item_ids')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
</div>

{{-- ============================================================ --}}
{{-- CONDITIONAL GROUPS — shown/hidden by JS based on offer type  --}}
{{-- ============================================================ --}}

{{-- Group A: القيمة → percentage, fixed, product_price_discount, bundle, first_order --}}
<div class="row mb-7 offer-grp offer-grp-value" style="display:none">
    <div class="col-md-4 fv-row">
        <label class="form-label required">القيمة</label>
        <div class="input-group">
            <input type="number" step="0.01" min="0" class="form-control form-control-solid"
                name="value" id="offer_value"
                value="{{old('value', $data?->value ?? '')}}" placeholder="0.00" />
            <span class="input-group-text" id="value-suffix">%</span>
        </div>
        @error('value')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
</div>

{{-- Group B: الكميات → buy_x_get_y, buy_x_get_discount, bundle --}}
<div class="row mb-7 offer-grp offer-grp-buy-get" style="display:none">
    <div class="col-md-3 fv-row">
        <label class="form-label required">كمية الشراء (X)</label>
        <input type="number" min="1" class="form-control form-control-solid"
            name="buy_quantity"
            value="{{old('buy_quantity', $data?->buy_quantity ?? '')}}" placeholder="2" />
        @error('buy_quantity')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    {{-- get_qty only for buy_x_get_y --}}
    <div class="col-md-3 fv-row offer-grp offer-grp-get-qty" style="display:none">
        <label class="form-label required">كمية الهدية (Y)</label>
        <input type="number" min="1" class="form-control form-control-solid"
            name="get_quantity"
            value="{{old('get_quantity', $data?->get_quantity ?? '')}}" placeholder="1" />
        @error('get_quantity')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
</div>

{{-- Group C: الحد الأدنى للمبلغ → buy_amount_get_discount, free_shipping, buy_x_get_discount --}}
<div class="row mb-7 offer-grp offer-grp-min-amount" style="display:none">
    <div class="col-md-4 fv-row">
        <label class="form-label required">الحد الأدنى لقيمة الطلب</label>
        <div class="input-group">
            <input type="number" step="0.01" min="0" class="form-control form-control-solid"
                name="min_amount"
                value="{{old('min_amount', $data?->min_amount ?? '')}}" placeholder="0.00" />
            <span class="input-group-text">ر.س</span>
        </div>
        @error('min_amount')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
</div>

{{-- Group D: نسبة/مبلغ الخصم → buy_x_get_discount, buy_amount_get_discount --}}
<div class="row mb-7 offer-grp offer-grp-discount" style="display:none">
    <div class="col-md-4 fv-row">
        <label class="form-label">نسبة الخصم (%)</label>
        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-solid"
            name="discount_percentage"
            value="{{old('discount_percentage', $data?->discount_percentage ?? '')}}" placeholder="0.00" />
    </div>
    <div class="col-md-4 fv-row">
        <label class="form-label">مبلغ الخصم (ر.س)</label>
        <div class="input-group">
            <input type="number" step="0.01" min="0" class="form-control form-control-solid"
                name="discount_amount"
                value="{{old('discount_amount', $data?->discount_amount ?? '')}}" placeholder="0.00" />
            <span class="input-group-text">ر.س</span>
        </div>
    </div>
    <div class="col-12 mt-2">
        <small class="text-muted">أدخل نسبة الخصم أو مبلغه — لا تعبئ الاثنين معاً.</small>
    </div>
</div>

{{-- Group E: الكمية المتاحة → flash --}}
<div class="row mb-7 offer-grp offer-grp-flash" style="display:none">
    <div class="col-md-4 fv-row">
        <label class="form-label required">الكمية المتاحة للعرض</label>
        <input type="number" min="1" class="form-control form-control-solid"
            name="flash_quantity"
            value="{{old('flash_quantity', $data?->flash_quantity ?? '')}}" placeholder="100" />
        @error('flash_quantity')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-8 d-flex align-items-end pb-1">
        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-0 p-4 w-100">
            <i class="ki-duotone ki-information fs-2tx text-warning me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            <div class="d-flex flex-stack flex-grow-1">
                <div class="fw-semibold fs-7">ينتهي العرض تلقائياً عند نفاد الكمية المتاحة.</div>
            </div>
        </div>
    </div>
</div>

{{-- Group F: شحن مجاني info → free_shipping --}}
<div class="row mb-7 offer-grp offer-grp-free-shipping-info" style="display:none">
    <div class="col-12">
        <div class="notice d-flex bg-light-success rounded border-success border border-dashed mb-0 p-4 w-100">
            <i class="ki-duotone ki-truck fs-2tx text-success me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
            <div class="d-flex flex-stack flex-grow-1">
                <div class="fw-semibold fs-7">يُطبَّق الشحن المجاني تلقائياً على كل طلب يتجاوز الحد الأدنى للمبلغ المحدد أعلاه.</div>
            </div>
        </div>
    </div>
</div>

{{-- Group G: شرائح الخصم المتدرج → tiered --}}
<div class="row mb-7 offer-grp offer-grp-tier" style="display:none">
    <div class="col-12">
        <label class="form-label required">شرائح الخصم المتدرج</label>
        <div class="border rounded p-4 bg-light">
            <div id="tier-rows"></div>
            <button type="button" class="btn btn-sm btn-light-primary mt-3" id="add-tier-row">
                <i class="bi bi-plus-lg me-1"></i> إضافة شريحة
            </button>
        </div>
        <input type="hidden" name="tier_thresholds" id="tier_thresholds_input"
            value="{{old('tier_thresholds', isset($data) && $data->tier_thresholds ? json_encode($data->tier_thresholds) : '[]')}}">
        <p class="text-muted fs-7 mt-2">
            مثال: شريحة 1 → من 100 ر.س = خصم 5% &nbsp;|&nbsp; شريحة 2 → من 200 ر.س = خصم 10%
        </p>
        @error('tier_thresholds')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
</div>

{{-- ============================================================ --}}
{{-- COMMON BOTTOM FIELDS                                         --}}
{{-- ============================================================ --}}

{{-- Row: max_uses + dates --}}
<div class="row mb-7">
    <div class="col-md-3 fv-row">
        <label class="form-label">الحد الأقصى للاستخدام <span class="text-muted fs-7">(اختياري)</span></label>
        <input type="number" min="1" class="form-control form-control-solid" name="max_uses"
            value="{{old('max_uses', $data?->max_uses ?? '')}}" placeholder="∞ بلا حد" />
    </div>
    <div class="col-md-3 fv-row">
        <label class="form-label required">تاريخ البدء</label>
        <input type="date" class="form-control form-control-solid" name="start_date"
            value="{{old('start_date', isset($data) && $data->start_date ? $data->start_date->format('Y-m-d') : '')}}" />
        @error('start_date')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-3 fv-row">
        <label class="form-label required">تاريخ الانتهاء</label>
        <input type="date" class="form-control form-control-solid" name="end_date"
            value="{{old('end_date', isset($data) && $data->end_date ? $data->end_date->format('Y-m-d') : '')}}" />
        @error('end_date')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
</div>

{{-- Row: Toggles --}}
<div class="row mb-7">
    <div class="col-md-3">
        <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                {{old('is_active', $data?->is_active ?? true) ? 'checked' : ''}}>
            <label class="form-check-label fw-semibold" for="is_active">مفعّل</label>
        </div>
    </div>
    <div class="col-md-5">
        <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="is_stackable" id="is_stackable" value="1"
                {{old('is_stackable', $data?->is_stackable ?? false) ? 'checked' : ''}}>
            <label class="form-check-label fw-semibold" for="is_stackable">
                قابل للتكديس مع كوبون
                <span class="ms-1" data-bs-toggle="tooltip" title="السماح بتطبيق هذا العرض مع كوبون خصم في نفس الوقت">
                    <i class="ki-duotone ki-information-5 text-gray-500 fs-6"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                </span>
            </label>
        </div>
    </div>
</div>
