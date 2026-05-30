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

            <label class="form-label">تاريخ الانضمام</label>
            <input type="date" class="form-control form-control-solid mb-5" name="join_date"
                value="{{old('join_date', isset($data) ? $data->join_date->format('Y-m-d') : date('Y-m-d'))}}" />
            @error('join_date')<div class="text-danger mt-1 mb-3">{{$message}}</div>@enderror

            <label class="form-label">الرقم الضريبي</label>
            <input type="text" class="form-control form-control-solid mb-5" name="tax_number"
                value="{{old('tax_number', $data->tax_number ?? '')}}" placeholder="الرقم الضريبي" />

            <label class="form-label">ملاحظات</label>
            <textarea class="form-control form-control-solid" name="notes" rows="4"
                placeholder="ملاحظات">{{old('notes', $data->notes ?? '')}}</textarea>

        </div>
    </div>
</div>
<!--end::Aside column-->

<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title">
                <h2>بيانات العميل</h2>
            </div>
        </div>
        <div class="card-body pt-0">

            <div class="row mb-7">
                <div class="col-md-6 fv-row fv-plugins-icon-container">
                    <label class="form-label required">الاسم</label>
                    <input type="text" class="form-control form-control-solid" name="name"
                        value="{{old('name', $data->name ?? '')}}" placeholder="اسم العميل" />
                    @error('name')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-6 fv-row fv-plugins-icon-container">
                    <label class="form-label required">كود العميل</label>
                    <input type="text" class="form-control form-control-solid" name="customer_code"
                        value="{{old('customer_code', $data->customer_code ?? '')}}" placeholder="كود العميل" />
                    @error('customer_code')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-6 fv-row fv-plugins-icon-container">
                    <label class="form-label required">رقم الهاتف</label>
                    <input type="text" class="form-control form-control-solid" name="phone"
                        value="{{old('phone', $data->phone ?? '')}}" placeholder="رقم الهاتف" />
                    @error('phone')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-6 fv-row fv-plugins-icon-container">
                    <label class="form-label">البريد الالكتروني</label>
                    <input type="email" class="form-control form-control-solid" name="email"
                        value="{{old('email', $data->email ?? '')}}" placeholder="البريد الالكتروني" />
                    @error('email')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-12 fv-row fv-plugins-icon-container">
                    <label class="form-label">العنوان</label>
                    <textarea class="form-control form-control-solid" name="address" rows="3"
                        placeholder="العنوان">{{old('address', $data->address ?? '')}}</textarea>
                </div>
            </div>

        </div>
    </div>
</div>
<!--end::Main column-->
