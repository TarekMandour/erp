<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title"><h2>بيانات حركة الخزنة</h2></div>
        </div>
        <div class="card-body pt-0">

            <div class="row mb-7">
                <div class="col-md-6 fv-row">
                    <label class="form-label required">الخزنة</label>
                    <select class="form-select form-select-solid" name="treasury_id">
                        <option value="">-- اختر الخزنة --</option>
                        @foreach($treasuries as $treasury)
                            <option value="{{$treasury->id}}" {{old('treasury_id', $data->treasury_id ?? request('treasury_id')) == $treasury->id ? 'selected' : ''}}>
                                {{$treasury->name}} ({{$treasury->currency}})
                            </option>
                        @endforeach
                    </select>
                    @error('treasury_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-6 fv-row">
                    <label class="form-label required">نوع المعاملة</label>
                    <select class="form-select form-select-solid" name="type">
                        <option value="">-- اختر النوع --</option>
                        <option value="deposit" {{old('type', $data->type ?? '') == 'deposit' ? 'selected' : ''}}>إيداع</option>
                        <option value="withdraw" {{old('type', $data->type ?? '') == 'withdraw' ? 'selected' : ''}}>سحب</option>
                    </select>
                    @error('type')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-4 fv-row">
                    <label class="form-label required">المبلغ</label>
                    <input type="number" step="0.01" min="0.01" class="form-control form-control-solid" name="amount"
                        value="{{old('amount', $data->amount ?? '')}}" placeholder="0.00" />
                    @error('amount')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-8 fv-row">
                    <label class="form-label">البيان</label>
                    <input type="text" class="form-control form-control-solid" name="description"
                        value="{{old('description', $data->description ?? '')}}" placeholder="وصف الحركة" />
                    @error('description')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-4 fv-row">
                    <label class="form-label">نوع المرجع</label>
                    <input type="text" class="form-control form-control-solid" name="reference_type"
                        value="{{old('reference_type', $data->reference_type ?? '')}}" placeholder="مثال: invoice" />
                    @error('reference_type')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-4 fv-row">
                    <label class="form-label">رقم المرجع</label>
                    <input type="text" class="form-control form-control-solid" name="reference_id"
                        value="{{old('reference_id', $data->reference_id ?? '')}}" placeholder="رقم المستند" />
                    @error('reference_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <a href="{{ route('finance.treasury_transactions.index') }}" class="btn btn-light me-3">الغاء</a>
            <button type="submit" class="btn btn-primary">حفظ</button>
        </div>
    </div>
</div>
<!--end::Main column-->
