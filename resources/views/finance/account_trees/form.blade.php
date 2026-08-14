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

            <label class="form-label">نوع الحساب <span class="text-danger">*</span></label>
            <select class="form-select form-select-solid mb-5" name="type">
                <option value="">-- اختر --</option>
                <option value="asset"     @if(old('type', $data->type ?? '') == 'asset')     selected @endif>أصول</option>
                <option value="liability" @if(old('type', $data->type ?? '') == 'liability') selected @endif>خصوم</option>
                <option value="equity"    @if(old('type', $data->type ?? '') == 'equity')    selected @endif>حقوق ملكية</option>
                <option value="revenue"   @if(old('type', $data->type ?? '') == 'revenue')   selected @endif>إيرادات</option>
                <option value="expense"   @if(old('type', $data->type ?? '') == 'expense')   selected @endif>مصروفات</option>
            </select>
            @error('type')<div class="text-danger mt-1 mb-3">{{$message}}</div>@enderror

            <label class="form-label">الحساب الأب</label>
            <select class="form-select form-select-solid mb-5" name="parent_id">
                <option value="">-- لا يوجد --</option>
                @foreach($parents as $parent)
                    <option value="{{$parent->id}}" @if(old('parent_id', $data->parent_id ?? '') == $parent->id) selected @endif>
                        {{$parent->code}} - {{$parent->name}}
                    </option>
                @endforeach
            </select>
            @error('parent_id')<div class="text-danger mt-1 mb-3">{{$message}}</div>@enderror

            <label class="form-label"> طبيعة الحساب</label>
            <select class="form-select form-select-solid mb-5" name="account_type">
                <option value="debit" @if(old('account_type', $data->account_type ?? '') == 'debit') selected @endif>مدين</option>
                <option value="credit" @if(old('account_type', $data->account_type ?? '') == 'credit') selected @endif>دائن</option>
            </select>
            @error('account_type')<div class="text-danger mt-1 mb-3">{{$message}}</div>@enderror

            <div class="form-check form-switch mb-5 mt-3">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                    @if(old('is_active', $data->is_active ?? true)) checked @endif />
                <label class="form-check-label fw-semibold" for="is_active">نشط</label>
            </div>

        </div>
    </div>
</div>
<!--end::Aside column-->

<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title">
                <h2>بيانات الحساب</h2>
            </div>
        </div>
        <div class="card-body pt-0">

            <div class="row mb-7">
                <div class="col-md-8 fv-row fv-plugins-icon-container">
                    <label class="form-label required">اسم الحساب</label>
                    <input type="text" class="form-control form-control-solid" name="name"
                        value="{{old('name', $data->name ?? '')}}" placeholder="اسم الحساب" />
                    @error('name')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-4 fv-row fv-plugins-icon-container">
                    <label class="form-label required">كود الحساب</label>
                    <input type="text" class="form-control form-control-solid" name="code"
                        value="{{old('code', $data->code ?? '')}}" placeholder="مثال: 1001" />
                    @error('code')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            @if(isset($data))
            <div class="row mb-7">
                <div class="col-md-4">
                    <label class="form-label">إجمالي المدين</label>
                    <input type="text" class="form-control form-control-solid" value="{{number_format($data->total_debit, 2)}}" readonly />
                </div>
                <div class="col-md-4">
                    <label class="form-label">إجمالي الدائن</label>
                    <input type="text" class="form-control form-control-solid" value="{{number_format($data->total_credit, 2)}}" readonly />
                </div>
                <div class="col-md-4">
                    <label class="form-label">الرصيد</label>
                    <input type="text" class="form-control form-control-solid @if($data->balance < 0) text-danger @else text-success @endif"
                        value="{{number_format($data->balance, 2)}}" readonly />
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
<!--end::Main column-->
