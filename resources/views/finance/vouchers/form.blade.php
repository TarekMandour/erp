<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title"><h2>بيانات السند</h2></div>
        </div>
        <div class="card-body pt-0">

            <div class="row mb-7">
                <div class="col-md-6 fv-row">
                    <label class="form-label required">نوع السند</label>
                    <select class="form-select form-select-solid" name="type" id="voucher_type">
                        <option value="">-- اختر النوع --</option>
                        <option value="payment" {{old('type', $data->type ?? '') == 'payment' ? 'selected' : ''}}>سند صرف</option>
                        <option value="receipt" {{old('type', $data->type ?? '') == 'receipt' ? 'selected' : ''}}>سند قبض</option>
                    </select>
                    @error('type')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-6 fv-row">
                    <label class="form-label required">نوع الدفع</label>
                    <select class="form-select form-select-solid" name="payment_type">
                        <option value="">-- اختر نوع الدفع --</option>
                        <option value="cash" {{old('payment_type', $data->payment_type ?? '') == 'cash' ? 'selected' : ''}}>نقدي</option>
                        <option value="credit" {{old('payment_type', $data->payment_type ?? '') == 'credit' ? 'selected' : ''}}>آجل</option>
                        <option value="installments" {{old('payment_type', $data->payment_type ?? '') == 'installments' ? 'selected' : ''}}>أقساط</option>
                    </select>
                    @error('payment_type')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-6 fv-row">
                    <label class="form-label required">نوع الطرف</label>
                    <select class="form-select form-select-solid" name="party_type" id="party_type">
                        <option value="">-- اختر نوع الطرف --</option>
                        <option value="customer" {{old('party_type', $data->party_type ?? '') == 'customer' ? 'selected' : ''}}>عميل</option>
                        <option value="supplier" {{old('party_type', $data->party_type ?? '') == 'supplier' ? 'selected' : ''}}>مورد</option>
                        <option value="other"    {{old('party_type', $data->party_type ?? '') == 'other'    ? 'selected' : ''}}>أخرى</option>
                    </select>
                    @error('party_type')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>

                {{-- Customer dropdown --}}
                <div class="col-md-6 fv-row" id="section-customer" style="display:none;">
                    <label class="form-label required">اختر العميل</label>
                    <select class="form-select form-select-solid" name="party_id" id="select_customer" disabled>
                        <option value="">-- اختر العميل --</option>
                        @foreach($customers as $customer)
                            <option value="{{$customer->id}}"
                                {{old('party_id', ($data->party_type ?? '') === 'customer' ? ($data->party_id ?? '') : '') == $customer->id ? 'selected' : ''}}>
                                {{$customer->name}} ({{$customer->customer_code}})
                            </option>
                        @endforeach
                    </select>
                    @error('party_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>

                {{-- Supplier dropdown --}}
                <div class="col-md-6 fv-row" id="section-supplier" style="display:none;">
                    <label class="form-label required">اختر المورد</label>
                    <select class="form-select form-select-solid" name="party_id" id="select_supplier" disabled>
                        <option value="">-- اختر المورد --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{$supplier->id}}"
                                {{old('party_id', ($data->party_type ?? '') === 'supplier' ? ($data->party_id ?? '') : '') == $supplier->id ? 'selected' : ''}}>
                                {{$supplier->name}} — {{$supplier->company_name}}
                            </option>
                        @endforeach
                    </select>
                    @error('party_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>

                {{-- Other: free text --}}
                <div class="col-md-6 fv-row" id="section-other" style="display:none;">
                    <label class="form-label required">اسم الطرف</label>
                    <input type="text" class="form-control form-control-solid" name="party_name" id="input_party_name"
                        value="{{old('party_name', ($data->party_type ?? '') === 'other' ? ($data->party_name ?? '') : '')}}"
                        placeholder="اكتب اسم الطرف" disabled />
                    @error('party_name')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

            <div class="row mb-7">
                <div class="col-md-4 fv-row">
                    <label class="form-label required">المبلغ الإجمالي</label>
                    <input type="number" step="0.01" min="0.01" class="form-control form-control-solid" name="total_amount"
                        value="{{old('total_amount', $data->total_amount ?? '')}}" placeholder="0.00" />
                    @error('total_amount')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-4 fv-row">
                    <label class="form-label required">التاريخ</label>
                    <input type="date" class="form-control form-control-solid" name="date"
                        value="{{old('date', isset($data->date) ? $data->date->format('Y-m-d') : date('Y-m-d'))}}" />
                    @error('date')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
                <div class="col-md-4 fv-row">
                    <label class="form-label">البيان</label>
                    <input type="text" class="form-control form-control-solid" name="description"
                        value="{{old('description', $data->description ?? '')}}" placeholder="وصف السند" />
                    @error('description')<div class="text-danger mt-1">{{$message}}</div>@enderror
                </div>
            </div>

        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <a href="{{ route('finance.vouchers.index') }}" class="btn btn-light me-3">الغاء</a>
            <button type="submit" class="btn btn-primary">حفظ</button>
        </div>
    </div>
</div>
<!--end::Main column-->
