<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title"><h2>بيانات المعاملة البنكية</h2></div>
        </div>
        <div class="card-body pt-0">

            <div class="row mb-7">
                <div class="col-md-6 fv-row">
                    <label class="form-label required">الحساب البنكي</label>
                    <select class="form-select form-select-solid" name="bank_account_id">
                        <option value="">-- اختر الحساب --</option>
                        @foreach($bankAccounts as $account)
                            <option value="{{$account->id}}" {{old('bank_account_id', $data->bank_account_id ?? request('bank_account_id')) == $account->id ? 'selected' : ''}}>
                                {{$account->bank->name}} - {{$account->account_number}} ({{$account->currency}})
                            </option>
                        @endforeach
                    </select>
                    @error('bank_account_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
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
            <a href="{{ route('finance.bank_transactions.index') }}" class="btn btn-light me-3">الغاء</a>
            <button type="submit" class="btn btn-primary">حفظ</button>
        </div>
    </div>
</div>
<!--end::Main column-->
