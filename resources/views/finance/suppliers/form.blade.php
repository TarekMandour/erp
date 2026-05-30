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

            <label class="form-label">حالة الحساب</label>
            <select class="form-select form-select-solid mb-5" name="account_status">
                <option value="active" @if(old('account_status', $data->account_status ?? 'active') == 'active') selected @endif>نشط</option>
                <option value="inactive" @if(old('account_status', $data->account_status ?? '') == 'inactive') selected @endif>غير نشط</option>
                <option value="blocked" @if(old('account_status', $data->account_status ?? '') == 'blocked') selected @endif>محظور</option>
            </select>

            <label class="form-label">الرقم الضريبي</label>
            <input type="text" class="form-control form-control-solid mb-5" name="tax_number"
                value="{{old('tax_number', $data->tax_number ?? '')}}" placeholder="الرقم الضريبي" />

            <label class="form-label">السجل التجاري</label>
            <input type="text" class="form-control form-control-solid mb-5" name="commercial_number"
                value="{{old('commercial_number', $data->commercial_number ?? '')}}" placeholder="رقم السجل التجاري" />

            <label class="form-label">البنك</label>
            <input type="text" class="form-control form-control-solid mb-5" name="bank"
                value="{{old('bank', $data->bank ?? '')}}" placeholder="اسم البنك" />

            <label class="form-label">رقم الحساب البنكي</label>
            <input type="text" class="form-control form-control-solid mb-5" name="bank_account"
                value="{{old('bank_account', $data->bank_account ?? '')}}" placeholder="رقم الحساب البنكي" />

        </div>
    </div>
</div>
<!--end::Aside column-->

<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title">
                <h2>بيانات المورد</h2>
            </div>
        </div>
        <div class="card-body pt-0">

            <div class="row mb-7">
                <div class="col-md-6 fv-row fv-plugins-icon-container">
                    <label class="form-label required">اسم الشركة</label>
                    <input type="text" class="form-control form-control-solid" name="company_name"
                        value="{{old('company_name', $data->company_name ?? '')}}" placeholder="اسم الشركة" />
                    @error('company_name')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-6 fv-row fv-plugins-icon-container">
                    <label class="form-label required">اسم المورد</label>
                    <input type="text" class="form-control form-control-solid" name="name"
                        value="{{old('name', $data->name ?? '')}}" placeholder="اسم المورد" />
                    @error('name')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-6 fv-row fv-plugins-icon-container">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" class="form-control form-control-solid" name="phone"
                        value="{{old('phone', $data->phone ?? '')}}" placeholder="رقم الهاتف" />
                    @error('phone')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-12 fv-row fv-plugins-icon-container">
                    <label class="form-label">العنوان</label>
                    <textarea class="form-control form-control-solid" name="address" rows="3"
                        placeholder="العنوان">{{old('address', $data->address ?? '')}}</textarea>
                    @error('address')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

        </div>
    </div>
</div>
<!--end::Main column-->
