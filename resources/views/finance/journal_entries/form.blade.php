{{-- ===== Aside: header fields ===== --}}
<div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title">
                <h2>بيانات القيد</h2>
            </div>
        </div>
        <div class="card-body pt-0">

            {{-- Entry Type --}}
            <div class="mb-5">
                <label class="form-label required">نوع القيد</label>
                <select class="form-select form-select-solid" name="entry_type" id="entry_type">
                    <option value="">-- اختر النوع --</option>
                    @php $cur = old('entry_type', $data->entry_type ?? ''); @endphp
                    <optgroup label="المبيعات">
                        <option value="sales"             @selected($cur=='sales')>مبيعات</option>
                        <option value="sales_return"      @selected($cur=='sales_return')>مرتجع مبيعات</option>
                        <option value="sales_discount"    @selected($cur=='sales_discount')>خصم مبيعات</option>
                        <option value="sales_installment" @selected($cur=='sales_installment')>مبيعات بالتقسيط</option>
                    </optgroup>
                    <optgroup label="المشتريات">
                        <option value="purchase"          @selected($cur=='purchase')>مشتريات</option>
                        <option value="purchase_return"   @selected($cur=='purchase_return')>مرتجع مشتريات</option>
                        <option value="purchase_discount" @selected($cur=='purchase_discount')>خصم مشتريات</option>
                    </optgroup>
                    <optgroup label="المخزون">
                        <option value="inventory_in"          @selected($cur=='inventory_in')>وارد مخزون</option>
                        <option value="inventory_out"         @selected($cur=='inventory_out')>صادر مخزون</option>
                        <option value="inventory_transfer"    @selected($cur=='inventory_transfer')>تحويل مخزون</option>
                        <option value="inventory_adjustment"  @selected($cur=='inventory_adjustment')>تسوية مخزون</option>
                        <option value="inventory_write_off"   @selected($cur=='inventory_write_off')>إتلاف مخزون</option>
                        <option value="inventory_revaluation" @selected($cur=='inventory_revaluation')>إعادة تقييم مخزون</option>
                    </optgroup>
                    <optgroup label="الصندوق">
                        <option value="cash_deposit"  @selected($cur=='cash_deposit')>إيداع صندوق</option>
                        <option value="cash_withdraw" @selected($cur=='cash_withdraw')>سحب صندوق</option>
                        <option value="cash_transfer" @selected($cur=='cash_transfer')>تحويل صندوق</option>
                    </optgroup>
                    <optgroup label="البنوك">
                        <option value="bank_deposit"  @selected($cur=='bank_deposit')>إيداع بنك</option>
                        <option value="bank_withdraw" @selected($cur=='bank_withdraw')>سحب بنك</option>
                        <option value="bank_transfer" @selected($cur=='bank_transfer')>تحويل بنك</option>
                    </optgroup>
                    <optgroup label="الشيكات">
                        <option value="cheque_received" @selected($cur=='cheque_received')>شيك مستلم</option>
                        <option value="cheque_issued"   @selected($cur=='cheque_issued')>شيك صادر</option>
                        <option value="cheque_cashed"   @selected($cur=='cheque_cashed')>صرف شيك</option>
                    </optgroup>
                    <optgroup label="المحفظة">
                        <option value="wallet_deposit"  @selected($cur=='wallet_deposit')>إيداع محفظة</option>
                        <option value="wallet_withdraw" @selected($cur=='wallet_withdraw')>سحب محفظة</option>
                        <option value="wallet_payment"  @selected($cur=='wallet_payment')>دفع محفظة</option>
                        <option value="wallet_refund"   @selected($cur=='wallet_refund')>استرداد محفظة</option>
                    </optgroup>
                    <optgroup label="العملاء">
                        <option value="customer_payment"     @selected($cur=='customer_payment')>تحصيل عميل</option>
                        <option value="customer_credit_note" @selected($cur=='customer_credit_note')>إشعار دائن عميل</option>
                        <option value="customer_debit_note"  @selected($cur=='customer_debit_note')>إشعار مدين عميل</option>
                    </optgroup>
                    <optgroup label="الموردين">
                        <option value="supplier_payment"     @selected($cur=='supplier_payment')>دفع مورد</option>
                        <option value="supplier_credit_note" @selected($cur=='supplier_credit_note')>إشعار دائن مورد</option>
                        <option value="supplier_debit_note"  @selected($cur=='supplier_debit_note')>إشعار مدين مورد</option>
                    </optgroup>
                    <optgroup label="المصروفات والإيرادات">
                        <option value="expense"         @selected($cur=='expense')>مصروف</option>
                        <option value="revenue"         @selected($cur=='revenue')>إيراد</option>
                        <option value="accrued_expense" @selected($cur=='accrued_expense')>مصروف مستحق</option>
                        <option value="prepaid_expense" @selected($cur=='prepaid_expense')>مصروف مدفوع مقدماً</option>
                        <option value="depreciation"    @selected($cur=='depreciation')>إهلاك</option>
                        <option value="amortization"    @selected($cur=='amortization')>استهلاك</option>
                    </optgroup>
                    <optgroup label="الرواتب">
                        <option value="salary"         @selected($cur=='salary')>راتب</option>
                        <option value="salary_advance" @selected($cur=='salary_advance')>سلفة راتب</option>
                        <option value="salary_loan"    @selected($cur=='salary_loan')>قرض موظف</option>
                        <option value="overtime"       @selected($cur=='overtime')>عمل إضافي</option>
                        <option value="bonus"          @selected($cur=='bonus')>مكافأة</option>
                        <option value="commission"     @selected($cur=='commission')>عمولة</option>
                    </optgroup>
                    <optgroup label="الأصول الثابتة">
                        <option value="asset_purchase"     @selected($cur=='asset_purchase')>شراء أصل</option>
                        <option value="asset_sale"         @selected($cur=='asset_sale')>بيع أصل</option>
                        <option value="asset_disposal"     @selected($cur=='asset_disposal')>استبعاد أصل</option>
                        <option value="asset_depreciation" @selected($cur=='asset_depreciation')>إهلاك أصل</option>
                    </optgroup>
                    <optgroup label="الضرائب">
                        <option value="vat_input"       @selected($cur=='vat_input')>ضريبة مدخلات</option>
                        <option value="vat_output"      @selected($cur=='vat_output')>ضريبة مخرجات</option>
                        <option value="vat_payment"     @selected($cur=='vat_payment')>دفع ضريبة</option>
                        <option value="vat_refund"      @selected($cur=='vat_refund')>استرداد ضريبة</option>
                        <option value="income_tax"      @selected($cur=='income_tax')>ضريبة دخل</option>
                        <option value="withholding_tax" @selected($cur=='withholding_tax')>خصم المصدر</option>
                    </optgroup>
                    <optgroup label="القروض والتمويل">
                        <option value="loan_received" @selected($cur=='loan_received')>استلام قرض</option>
                        <option value="loan_payment"  @selected($cur=='loan_payment')>سداد قرض</option>
                        <option value="loan_interest" @selected($cur=='loan_interest')>فوائد قرض</option>
                    </optgroup>
                    <optgroup label="رأس المال">
                        <option value="capital_increase" @selected($cur=='capital_increase')>زيادة رأس المال</option>
                        <option value="capital_decrease" @selected($cur=='capital_decrease')>تخفيض رأس المال</option>
                        <option value="dividend_paid"    @selected($cur=='dividend_paid')>توزيعات أرباح</option>
                    </optgroup>
                    <optgroup label="التحويلات الداخلية">
                        <option value="internal_transfer" @selected($cur=='internal_transfer')>تحويل داخلي</option>
                        <option value="cost_allocation"   @selected($cur=='cost_allocation')>تخصيص تكلفة</option>
                        <option value="profit_transfer"   @selected($cur=='profit_transfer')>ترحيل أرباح</option>
                    </optgroup>
                    <optgroup label="أرصدة افتتاحية وتسويات">
                        <option value="opening_balance" @selected($cur=='opening_balance')>رصيد افتتاحي</option>
                        <option value="adjustment"      @selected($cur=='adjustment')>تسوية</option>
                        <option value="revaluation"     @selected($cur=='revaluation')>إعادة تقييم</option>
                        <option value="closing_entry"   @selected($cur=='closing_entry')>قيد إقفال</option>
                    </optgroup>
                    <optgroup label="عمليات أخرى">
                        <option value="refund"     @selected($cur=='refund')>استرداد</option>
                        <option value="write_off"  @selected($cur=='write_off')>شطب</option>
                        <option value="reversal"   @selected($cur=='reversal')>عكس قيد</option>
                        <option value="correction" @selected($cur=='correction')>تصحيح</option>
                    </optgroup>
                </select>
                @error('entry_type')<div class="text-danger mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Date --}}
            <div class="mb-5">
                <label class="form-label required">تاريخ القيد</label>
                <input type="date" class="form-control form-control-solid" name="date"
                    value="{{old('date', isset($data) ? $data->date->format('Y-m-d') : date('Y-m-d'))}}" />
                @error('date')<div class="text-danger mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Status --}}
            <div class="mb-5">
                <label class="form-label required">الحالة</label>
                <select class="form-select form-select-solid" name="status">
                    <option value="draft"    @if(old('status', $data->status ?? 'draft') == 'draft')    selected @endif>مسودة</option>
                    <option value="posted"   @if(old('status', $data->status ?? '') == 'posted')   selected @endif>مرحّل</option>
                    <option value="canceled" @if(old('status', $data->status ?? '') == 'canceled') selected @endif>ملغي</option>
                </select>
                @error('status')<div class="text-danger mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Reference --}}
            <div class="mb-5">
                <label class="form-label">نوع المرجع</label>
                <input type="text" class="form-control form-control-solid" name="reference_type"
                    value="{{old('reference_type', $data->reference_type ?? '')}}" placeholder="Invoice, Payment ..." />
            </div>
            <div class="mb-5">
                <label class="form-label">رقم المرجع</label>
                <input type="number" class="form-control form-control-solid" name="reference_id" min="1"
                    value="{{old('reference_id', $data->reference_id ?? '')}}" />
            </div>

            {{-- Description --}}
            <div class="mb-5">
                <label class="form-label">الوصف</label>
                <textarea class="form-control form-control-solid" name="description" rows="3"
                    placeholder="وصف القيد">{{old('description', $data->description ?? '')}}</textarea>
            </div>

        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <a href="{{route('finance.journal_entries.index')}}" class="btn btn-light me-3">إلغاء</a>
            <button type="submit" class="btn btn-primary" id="btn_submit">حفظ القيد</button>
        </div>
    </div>
</div>

{{-- ===== Main: items table ===== --}}
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <div class="card-header">
            <div class="card-title">
                <h2>أسطر القيد</h2>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-success" id="btn_add_row">
                    <i class="bi bi-plus-circle fs-4 me-1"></i> إضافة سطر
                </button>
            </div>
        </div>
        <div class="card-body pt-0">
            @error('items')<div class="alert alert-danger mb-4">{{$message}}</div>@enderror

            <div class="table-responsive">
                <table class="table table-row-dashed align-middle fs-6 gy-3" id="items_table">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                            <th style="min-width:220px">الحساب <span class="text-danger">*</span></th>
                            <th style="min-width:130px">مدين</th>
                            <th style="min-width:130px">دائن</th>
                            <th style="min-width:160px">مركز التكلفة</th>
                            <th style="min-width:180px">الوصف</th>
                            <th class="w-40px"></th>
                        </tr>
                    </thead>
                    <tbody id="items_body">
                        {{-- Existing rows (edit mode) --}}
                        @if(isset($data) && $data->items->count())
                            @foreach($data->items as $i => $item)
                            <tr class="item-row">
                                <td>
                                    <select class="form-select form-select-solid form-select-sm account-select" name="items[{{$i}}][account_tree_id]" required>
                                        <option value="">-- الحساب --</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{$acc->id}}" @if($acc->id == $item->account_tree_id) selected @endif>
                                                {{$acc->code}} - {{$acc->name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-solid form-control-sm debit-input"
                                        name="items[{{$i}}][debit]" value="{{old('items.'.$i.'.debit', $item->debit)}}" placeholder="0.00" />
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-solid form-control-sm credit-input"
                                        name="items[{{$i}}][credit]" value="{{old('items.'.$i.'.credit', $item->credit)}}" placeholder="0.00" />
                                </td>
                                <td>
                                    <select class="form-select form-select-solid form-select-sm" name="items[{{$i}}][cost_center_id]">
                                        <option value="">--</option>
                                        @foreach($costCenters as $cc)
                                            <option value="{{$cc->id}}" @if($cc->id == $item->cost_center_id) selected @endif>
                                                {{$cc->name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-solid form-control-sm"
                                        name="items[{{$i}}][description]" value="{{old('items.'.$i.'.description', $item->description)}}" placeholder="وصف" />
                                </td>
                                <td>
                                    <button type="button" class="btn btn-icon btn-sm btn-danger btn-remove-row"><i class="bi bi-trash fs-4"></i></button>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            {{-- Two blank rows by default --}}
                            @for($i = 0; $i < 2; $i++)
                            <tr class="item-row">
                                <td>
                                    <select class="form-select form-select-solid form-select-sm account-select" name="items[{{$i}}][account_tree_id]" required>
                                        <option value="">-- الحساب --</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{$acc->id}}" @if(old('items.'.$i.'.account_tree_id') == $acc->id) selected @endif>
                                                {{$acc->code}} - {{$acc->name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-solid form-control-sm debit-input"
                                        name="items[{{$i}}][debit]" value="{{old('items.'.$i.'.debit', '0.00')}}" placeholder="0.00" />
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-solid form-control-sm credit-input"
                                        name="items[{{$i}}][credit]" value="{{old('items.'.$i.'.credit', '0.00')}}" placeholder="0.00" />
                                </td>
                                <td>
                                    <select class="form-select form-select-solid form-select-sm" name="items[{{$i}}][cost_center_id]">
                                        <option value="">--</option>
                                        @foreach($costCenters as $cc)
                                            <option value="{{$cc->id}}">{{$cc->name}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-solid form-control-sm"
                                        name="items[{{$i}}][description]" value="{{old('items.'.$i.'.description')}}" placeholder="وصف" />
                                </td>
                                <td>
                                    <button type="button" class="btn btn-icon btn-sm btn-danger btn-remove-row"><i class="bi bi-trash fs-4"></i></button>
                                </td>
                            </tr>
                            @endfor
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold fs-5">
                            <td class="text-end text-muted">المجموع</td>
                            <td><span id="total_debit" class="text-danger fw-bold">0.00</span></td>
                            <td><span id="total_credit" class="text-success fw-bold">0.00</span></td>
                            <td colspan="3">
                                <span id="balance_status"></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>
</div>
