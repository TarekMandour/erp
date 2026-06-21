<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title"><h2>بيانات الخزنة</h2></div>
        </div>
        <div class="card-body pt-0">

            <div class="row mb-7">
                <div class="col-md-4 fv-row">
                    <label class="form-label required">اسم الخزنة</label>
                    <input type="text" class="form-control form-control-solid" name="name"
                        value="{{old('name', $data->name ?? '')}}" placeholder="اسم الخزنة" />
                    @error('name')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>

                <div class="col-md-4 fv-row">
                    <label class="form-label">الحساب</label>
                    <select class="form-select form-select-solid" name="account_tree_id" data-kt-select2="true" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                        <option value="">-- الحساب --</option>
                        @foreach($accounts as $acc)
                            <option value="{{$acc->id}}" @if(isset($data) && $acc->id == $data->account_tree_id) selected @endif>
                                {{$acc->code}} - {{$acc->name}}
                            </option>
                        @endforeach
                    </select>
                    @error('account_tree_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>

                <div class="col-md-4 fv-row">
                    <label class="form-label required">العملة</label>
                    <select class="form-select form-select-solid" name="currency">
                        <option value="">-- اختر العملة --</option>
                        <option value="SAR" {{old('currency', $data->currency ?? 'SAR') == 'SAR' ? 'selected' : ''}}>ريال سعودي (SAR)</option>
                        <option value="USD" {{old('currency', $data->currency ?? '') == 'USD' ? 'selected' : ''}}>دولار أمريكي (USD)</option>
                        <option value="EUR" {{old('currency', $data->currency ?? '') == 'EUR' ? 'selected' : ''}}>يورو (EUR)</option>
                        <option value="EGP" {{old('currency', $data->currency ?? '') == 'EGP' ? 'selected' : ''}}>جنيه مصري (EGP)</option>
                        <option value="AED" {{old('currency', $data->currency ?? '') == 'AED' ? 'selected' : ''}}>درهم إماراتي (AED)</option>
                    </select>
                    @error('currency')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            

            <div class="row mb-7">
                <div class="col-md-6 fv-row">
                    <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                            {{old('is_active', $data->is_active ?? true) ? 'checked' : ''}} />
                        <label class="form-check-label fw-semibold text-gray-700" for="is_active">مفعل</label>
                    </div>
                </div>
            </div>

        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <a href="{{ route('finance.treasuries.index') }}" class="btn btn-light me-3">الغاء</a>
            <button type="submit" class="btn btn-primary">حفظ</button>
        </div>
    </div>
</div>
<!--end::Main column-->
