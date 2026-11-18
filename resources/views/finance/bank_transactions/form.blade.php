<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title"><h2>بيانات المعاملة البنكية</h2></div>
        </div>
        <div class="card-body pt-0">
            @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul></div>
            @endif

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
                <div class="col-md-8 fv-row">
                    <label class="form-label">البيان</label>
                    <input type="text" class="form-control form-control-solid" name="description"
                        value="{{old('description', $data->description ?? '')}}" placeholder="وصف الحركة (يظهر في القيد)" />
                    @error('description')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-6 fv-row">
                    <label class="form-label required">الحساب المقابل</label>
                    <select class="form-select form-select-solid" name="counter_account_id" data-control="select2" data-placeholder="اختر الحساب المقابل">
                        <option value="">-- اختر الحساب المقابل --</option>
                        @foreach($counterAccounts as $acc)
                            <option value="{{$acc->id}}" {{old('counter_account_id', $data->counter_account_id ?? '') == $acc->id ? 'selected' : ''}}>{{$acc->code}} - {{$acc->name}}</option>
                        @endforeach
                    </select>
                    <div class="text-muted fs-7 mt-1">الإيداع: مدين الخزنة/البنك ودائن هذا الحساب، والسحب بالعكس. يُنشأ قيد يومية مرحّل تلقائياً.</div>
                    @error('counter_account_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
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
