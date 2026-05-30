<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title"><h2>بيانات الرصيد الافتتاحي</h2></div>
        </div>
        <div class="card-body pt-0">

            <div class="row mb-7">
                <div class="col-md-12 fv-row">
                    <label class="form-label required">الحساب</label>
                    <select class="form-select form-select-solid" name="account_id">
                        <option value="">-- اختر الحساب --</option>
                        @foreach($accounts as $account)
                            <option value="{{$account->id}}" {{old('account_id', $data->account_id ?? '') == $account->id ? 'selected' : ''}}>
                                {{$account->code}} - {{$account->name}}
                            </option>
                        @endforeach
                    </select>
                    @error('account_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-6 fv-row">
                    <label class="form-label required">مدين</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="debit"
                        value="{{old('debit', $data->debit ?? '0.00')}}" placeholder="0.00" />
                    @error('debit')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-6 fv-row">
                    <label class="form-label required">دائن</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="credit"
                        value="{{old('credit', $data->credit ?? '0.00')}}" placeholder="0.00" />
                    @error('credit')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-4 fv-row">
                    <label class="form-label required">التاريخ</label>
                    <input type="date" class="form-control form-control-solid" name="date"
                        value="{{old('date', isset($data->date) ? $data->date->format('Y-m-d') : date('Y-m-d'))}}" />
                    @error('date')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-8 fv-row">
                    <label class="form-label">البيان</label>
                    <input type="text" class="form-control form-control-solid" name="description"
                        value="{{old('description', $data->description ?? '')}}" placeholder="وصف الرصيد الافتتاحي" />
                    @error('description')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <a href="{{ route('finance.opening_balances.index') }}" class="btn btn-light me-3">الغاء</a>
            <button type="submit" class="btn btn-primary">حفظ</button>
        </div>
    </div>
</div>
<!--end::Main column-->
