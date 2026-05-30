{{-- Shared form fields for create & edit --}}

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-7">
    <ul class="mb-0">@foreach($errors->all() as $err)<li>{{$err}}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Row 1: Code + Type --}}
<div class="row mb-7">
    <div class="col-md-5 fv-row">
        <label class="form-label required">كود الكوبون</label>
        <div class="input-group">
            <input type="text" class="form-control form-control-solid text-uppercase" name="code"
                id="coupon_code" value="{{old('code', $data->code ?? '')}}"
                placeholder="مثال: SUMMER2026" style="text-transform:uppercase" />
            <button type="button" class="btn btn-secondary" id="btn-generate" title="توليد كود عشوائي">
                <i class="bi bi-shuffle"></i>
            </button>
        </div>
        @error('code')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-4 fv-row">
        <label class="form-label required">نوع الخصم</label>
        <select class="form-select form-select-solid" name="type" id="coupon_type">
            <option value="percentage" {{old('type', $data->type ?? '') === 'percentage' ? 'selected' : ''}}>نسبة مئوية (%)</option>
            <option value="fixed"      {{old('type', $data->type ?? '') === 'fixed'      ? 'selected' : ''}}>مبلغ ثابت</option>
        </select>
        @error('type')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-3 fv-row">
        <label class="form-label required">القيمة</label>
        <div class="input-group">
            <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="value"
                value="{{old('value', $data->value ?? '')}}" placeholder="0.00" />
            <span class="input-group-text" id="type-suffix">%</span>
        </div>
        @error('value')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
</div>

{{-- Row 2: Min Order + Max Discount --}}
<div class="row mb-7">
    <div class="col-md-4 fv-row">
        <label class="form-label">الحد الأدنى لقيمة الطلب <span class="text-muted fs-7">(اختياري)</span></label>
        <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="min_order_amount"
            value="{{old('min_order_amount', $data->min_order_amount ?? '')}}" placeholder="0.00" />
        @error('min_order_amount')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-4 fv-row">
        <label class="form-label">أقصى قيمة للخصم <span class="text-muted fs-7">(للنسبة المئوية)</span></label>
        <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="max_discount"
            value="{{old('max_discount', $data->max_discount ?? '')}}" placeholder="0.00" />
        @error('max_discount')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
</div>

{{-- Row 3: Usage limits --}}
<div class="row mb-7">
    <div class="col-md-3 fv-row">
        <label class="form-label">الحد الأقصى للاستخدام الكلي <span class="text-muted fs-7">(اختياري)</span></label>
        <input type="number" min="1" class="form-control form-control-solid" name="usage_limit"
            value="{{old('usage_limit', $data->usage_limit ?? '')}}" placeholder="بلا حد" />
        @error('usage_limit')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-3 fv-row">
        <label class="form-label">الحد الأقصى لكل مستخدم <span class="text-muted fs-7">(اختياري)</span></label>
        <input type="number" min="1" class="form-control form-control-solid" name="usage_per_user"
            value="{{old('usage_per_user', $data->usage_per_user ?? '')}}" placeholder="بلا حد" />
        @error('usage_per_user')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
</div>

{{-- Row 4: Dates --}}
<div class="row mb-7">
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

{{-- Row 5: Active --}}
<div class="row mb-7">
    <div class="col-md-3 fv-row">
        <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                {{old('is_active', $data->is_active ?? true) ? 'checked' : ''}}>
            <label class="form-check-label fw-semibold" for="is_active">مفعّل</label>
        </div>
    </div>
</div>
