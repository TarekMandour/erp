<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title"><h2>بيانات الحساب البنكي</h2></div>
        </div>
        <div class="card-body pt-0">

            <div class="row mb-7">
                <div class="col-md-6 fv-row">
                    <label class="form-label required">البنك</label>
                    <select class="form-select form-select-solid" name="bank_id">
                        <option value="">-- اختر البنك --</option>
                        @foreach($banks as $bank)
                            <option value="{{$bank->id}}" {{old('bank_id', $data->bank_id ?? request('bank_id')) == $bank->id ? 'selected' : ''}}>
                                {{$bank->name}}
                            </option>
                        @endforeach
                    </select>
                    @error('bank_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-6 fv-row">
                    <label class="form-label required">العملة</label>
                    <select class="form-select form-select-solid" name="currency">
                        <option value="">-- اختر العملة --</option>
                        <option value="SAR" {{old('currency', $data->currency ?? '') == 'SAR' ? 'selected' : ''}}>ريال سعودي (SAR)</option>
                        <option value="USD" {{old('currency', $data->currency ?? '') == 'USD' ? 'selected' : ''}}>دولار أمريكي (USD)</option>
                        <option value="EUR" {{old('currency', $data->currency ?? '') == 'EUR' ? 'selected' : ''}}>يورو (EUR)</option>
                        <option value="EGP" {{old('currency', $data->currency ?? '') == 'EGP' ? 'selected' : ''}}>جنيه مصري (EGP)</option>
                        <option value="AED" {{old('currency', $data->currency ?? '') == 'AED' ? 'selected' : ''}}>درهم إماراتي (AED)</option>
                    </select>
                    @error('currency')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-8 fv-row">
                    <label class="form-label required">رقم الحساب</label>
                    <input type="text" class="form-control form-control-solid" name="account_number"
                        value="{{old('account_number', $data->account_number ?? '')}}" placeholder="رقم الحساب البنكي" />
                    @error('account_number')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <a href="{{ route('finance.bank_accounts.index') }}" class="btn btn-light me-3">الغاء</a>
            <button type="submit" class="btn btn-primary">حفظ</button>
        </div>
    </div>
</div>
<!--end::Main column-->
