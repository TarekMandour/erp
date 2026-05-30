<div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_scroll" data-kt-scroll="true"
    data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_header"
    data-kt-scroll-wrappers="#kt_modal_add_scroll" data-kt-scroll-offset="300px">

    @if (isset($data))
        <input type="hidden" name="id" value="{{$data->id}}" />
    @endif


    <div class="row fv-row mb-7">
        <div class="col-md-3 text-md-end">
            <!--begin::Label-->
            <label class="fs-6 fw-semibold form-label mt-3">
                <span class="">المنتجات</span>
            </label>
            <!--end::Label-->
        </div>
        <div class="col-md-9">
            <select class="form-select form-select-solid product_id" name="product_id"
                data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">

                @if(isset($data) && $data->product_id)
                    <option value="{{ $data->product_id }}" selected="selected">{{ $data->product->name ?? '' }}</option>
                @else
                    <option value="">تحويل عام</option>
                @endif
            </select>
        </div>
    </div>

    <div class="row fv-row mb-7">
        <div class="col-md-3 text-md-end">
            <!--begin::Label-->
            <label class="fs-6 fw-semibold form-label mt-3">
                <span class="">انواع المنتجات</span>
            </label>
            <!--end::Label-->
        </div>
        <div class="col-md-9">
            <select class="form-select form-select-solid variant_id" name="variant_id"
                data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                <option></option>
                @if(isset($data) && $data->variant_id)
                    <option value="{{ $data->variant_id }}" selected="selected">{{ $data->variant->formatted_attributes_text }}</option>
                @endif
            </select>
        </div>
    </div>

    <div class="row fv-row mb-7">
        <div class="col-md-3 text-md-end">
            <!--begin::Label-->
            <label class="fs-6 fw-semibold form-label mt-3">
                <span class="required">الوحدة الاساسية</span>
            </label>
            <!--end::Label-->
        </div>
        <div class="col-md-9">
            <select class="form-select form-select-solid base_unit" name="base_unit"
                data-close-on-select="true" data-placeholder="اختر ..."
                data-allow-clear="false">
                <option></option>
                @foreach ($filters['units'] as $unit)
                    <option value="{{ $unit->id }}" @if(isset($data) && $data->base_unit == $unit->id) selected="selected"
                    @endif>{{ $unit->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row fv-row mb-7">
        <div class="col-md-3 text-md-end">
            <!--begin::Label-->
            <label class="fs-6 fw-semibold form-label mt-3">
                <span class="required">الوحدة المستهدفة</span>
            </label>
            <!--end::Label-->
        </div>
        <div class="col-md-9">
            <select class="form-select form-select-solid target_unit" name="target_unit"
                data-close-on-select="true" data-placeholder="اختر ..."
                data-allow-clear="false">
                <option></option>
                @foreach ($filters['units'] as $unit)
                    <option value="{{ $unit->id }}" @if(isset($data) && $data->target_unit == $unit->id) selected="selected"
                    @endif>{{ $unit->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row fv-row mb-7">
        <div class="col-md-3 text-md-end">
            <!--begin::Label-->
            <label class="fs-6 fw-semibold form-label mt-3">
                <span class="required">معامل التحويل</span>
            </label>
            <!--end::Label-->
        </div>
        <div class="col-md-9">
            <!--begin::Input-->
            <input type="number" class="form-control form-control-solid" name="conversion_rate"
                value="{{old('conversion_rate', $data->conversion_rate ?? 0)}}" step="0.01" min="0"
                placeholder="معامل التحويل" />
            <!--end::Input-->
        </div>
    </div>

    <div class="form-check form-check-custom form-check-primary form-check-solid mb-5">
        <input class="form-check-input h-20px w-20px" type="checkbox" name="is_default" value="1" @if(isset($data) && $data->is_default == 1) checked @endif />
        <label class="form-check-label text-dark fw-bold" for="">
            هل النوع افتراضي ؟
        </label>
    </div>

    <div class="form-check form-check-custom form-check-primary form-check-solid mb-5">
        <input class="form-check-input h-20px w-20px" type="checkbox" name="allow_fractions" value="1" @if(isset($data) && $data->allow_fractions == 1) checked @endif />
        <label class="form-check-label text-dark fw-bold" for="">
            هل مسموح الكسور ؟
        </label>
    </div>

</div>