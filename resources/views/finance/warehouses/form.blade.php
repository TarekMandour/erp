<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title"><h2>بيانات المستودع</h2></div>
        </div>
        <div class="card-body pt-0">

            <div class="row mb-7">
                <div class="col-md-6 fv-row">
                    <label class="form-label required">اسم المستودع</label>
                    <input type="text" class="form-control form-control-solid" name="name"
                        value="{{old('name', $data->name ?? '')}}" placeholder="اسم المستودع" />
                    @error('name')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-6 fv-row">
                    <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                            {{old('is_active', $data->is_active ?? true) ? 'checked' : ''}} />
                        <label class="form-check-label fw-semibold text-gray-700" for="is_active">مفعل</label>
                    </div>
                </div>
            </div>

            {{-- Leaflet Polygon Area Picker --}}
            <div class="row mb-7">
                <div class="col-12">
                    <label class="form-label">منطقة المستودع <span class="text-muted fs-7">(ارسم مضلعاً على الخريطة لتحديد نطاق المستودع)</span></label>
                    <div id="warehouse-map" style="height: 450px; border-radius: 8px; border: 1px solid #e4e6ef;"></div>
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-8 fv-row">
                    <div id="polygon-info" class="alert alert-info py-2 mb-0">
                        <i class="bi bi-info-circle me-1"></i> استخدم أداة رسم المضلع <i class="bi bi-pentagon"></i> لتحديد منطقة المستودع على الخريطة
                    </div>
                    <input type="hidden" id="polygon_coords" name="polygon_coords"
                        value="{{old('polygon_coords', $data->location ? json_encode($data->location) : '')}}" />
                </div>
                <div class="col-md-4 fv-row d-flex align-items-center">
                    <button type="button" class="btn btn-light-danger w-100" id="btn_clear_location">
                        <i class="bi bi-x-circle me-1"></i> مسح المنطقة
                    </button>
                </div>
            </div>

        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <a href="{{ route('finance.warehouses.index') }}" class="btn btn-light me-3">الغاء</a>
            <button type="submit" class="btn btn-primary">حفظ</button>
        </div>
    </div>
</div>
<!--end::Main column-->
