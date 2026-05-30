<!--begin::Aside column-->
<div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="reset" class="btn btn-light me-3">الغاء</button>
                <button type="submit" class="btn btn-primary" id="kt_account_profile_details_submit">حفظ</button>
            </div>
        </div>
        <div class="card-body pt-0">

            <label class="form-label">الحساب <span class="text-danger">*</span></label>
            <select class="form-select form-select-solid mb-5" name="account_id" id="account_id_select">
                <option value="">-- اختر الحساب --</option>
                @foreach($accounts as $account)
                    <option value="{{$account->id}}"
                        @if(old('account_id', $data->account_id ?? request('account_id')) == $account->id) selected @endif>
                        {{$account->code}} - {{$account->name}}
                    </option>
                @endforeach
            </select>
            @error('account_id')<div class="text-danger mt-1 mb-3">{{$message}}</div>@enderror

        </div>
    </div>
</div>
<!--end::Aside column-->

<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title">
                <h2>بيانات القيد</h2>
            </div>
        </div>
        <div class="card-body pt-0">

            <div class="row mb-7">
                <div class="col-md-6 fv-row fv-plugins-icon-container">
                    <label class="form-label">مدين (Debit)</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="debit"
                        value="{{old('debit', $data->debit ?? '0.00')}}" placeholder="0.00" />
                    @error('debit')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-6 fv-row fv-plugins-icon-container">
                    <label class="form-label">دائن (Credit)</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="credit"
                        value="{{old('credit', $data->credit ?? '0.00')}}" placeholder="0.00" />
                    @error('credit')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-6 fv-row fv-plugins-icon-container">
                    <label class="form-label">نوع المرجع</label>
                    <input type="text" class="form-control form-control-solid" name="reference_type"
                        value="{{old('reference_type', $data->reference_type ?? '')}}" placeholder="مثال: Invoice, Payment ..." />
                    @error('reference_type')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-6 fv-row fv-plugins-icon-container">
                    <label class="form-label">رقم المرجع</label>
                    <input type="number" min="1" class="form-control form-control-solid" name="reference_id"
                        value="{{old('reference_id', $data->reference_id ?? '')}}" placeholder="رقم المرجع" />
                    @error('reference_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-12 fv-row fv-plugins-icon-container">
                    <label class="form-label">الوصف</label>
                    <textarea class="form-control form-control-solid" name="description" rows="4"
                        placeholder="وصف القيد">{{old('description', $data->description ?? '')}}</textarea>
                    @error('description')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

        </div>
    </div>
</div>
<!--end::Main column-->
