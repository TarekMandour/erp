<!--begin::Aside column-->
<div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="reset" data-kt-ecommerce-settings-type="cancel" class="btn btn-light me-3">الغاء</button>
                <button type="submit" class="btn btn-primary" id="kt_account_profile_details_submit">حفظ</button>
            </div>
        </div>
        <div class="card-body pt-0">

            <label class="form-label required">التاريخ</label>
            <input type="date" class="form-control form-control-solid mb-5" name="date"
                value="{{old('date', isset($data) ? $data->date->format('Y-m-d') : date('Y-m-d'))}}" />
            @error('date')<div class="text-danger mt-1 mb-3">{{$message}}</div>@enderror

            <label class="form-label">الوصف</label>
            <textarea class="form-control form-control-solid mb-5" name="description" rows="4"
                placeholder="الوصف">{{old('description', $data->description ?? '')}}</textarea>

        </div>
    </div>
</div>
<!--end::Aside column-->

<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title">
                <h2>بيانات الحركة المالية</h2>
            </div>
        </div>
        <div class="card-body pt-0">

            <div class="row mb-7">
                <div class="col-md-6 fv-row fv-plugins-icon-container">
                    <label class="form-label required">مدين (Debit)</label>
                    <input type="number" class="form-control form-control-solid" name="debit"
                        value="{{old('debit', $data->debit ?? 0)}}" step="0.01" min="0" placeholder="0.00" />
                    @error('debit')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-6 fv-row fv-plugins-icon-container">
                    <label class="form-label required">دائن (Credit)</label>
                    <input type="number" class="form-control form-control-solid" name="credit"
                        value="{{old('credit', $data->credit ?? 0)}}" step="0.01" min="0" placeholder="0.00" />
                    @error('credit')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

        </div>
    </div>
</div>
<!--end::Main column-->
