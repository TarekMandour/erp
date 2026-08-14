{{-- Posting Scenario Form (HTML only — no script block) --}}
<div class="d-flex flex-column flex-xl-row gap-7 gap-lg-10">

    {{-- ===== MAIN: Posting Rules table ===== --}}
    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-12">
        <div class="card card-flush py-4 flex-row-fluid">
            <div class="card-header">
                <div class="card-title">
                    <h2>قواعد الترحيل</h2>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="row g-5">
                    <div class="col-md-4 fv-row">
                        <label class="required form-label">الكود</label>
                        <input type="text" name="code" value="{{ old('code', $data->code ?? '') }}"
                            class="form-control form-control-solid @error('code') is-invalid @enderror"
                            placeholder="SALE_CREDIT" />
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4 fv-row">
                        <label class="required form-label">الاسم</label>
                        <input type="text" name="name" value="{{ old('name', $data->name ?? '') }}"
                            class="form-control form-control-solid @error('name') is-invalid @enderror" />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4 fv-row">
                        <label class="required form-label">نوع العملية</label>
                        <select name="operation_type" class="form-select form-select-solid @error('operation_type') is-invalid @enderror">
                            <option value="">-- اختر --</option>
                            @php $cur = old('operation_type', $data->operation_type ?? ''); @endphp
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
                        @error('operation_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4 fv-row">
                        <label class="form-label">الأولوية</label>
                        <input type="number" name="priority" value="{{ old('priority', $data->priority ?? 0) }}"
                            class="form-control form-control-solid" min="0" />
                    </div>

                    <div class="col-md-4 fv-row">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="description" rows="3"
                                class="form-control form-control-solid">{{ old('description', $data->description ?? '') }}</textarea>
                    </div>

                    <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                            {{ old('is_active', $data->is_active ?? true) ? 'checked' : '' }} />
                        <label class="form-check-label fw-semibold" for="is_active">نشط</label>
                    </div>

                </div>

            </div>
        </div>

        <div class="card card-flush py-4 flex-row-fluid">
            <div class="card-header">
                <div class="card-title">
                    <h2>قواعد الترحيل</h2>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-light-primary" id="btn_add_rule">
                        <i class="ki-duotone ki-plus fs-3"></i> إضافة قاعدة
                    </button>
                </div>
            </div>
            <div class="card-body pt-0">

                @if($errors->has('rules') || $errors->has('rules.*') )
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0">
                            @foreach($errors->get('rules.*') as $msgs)
                                @foreach($msgs as $msg)<li>{{ $msg }}</li>@endforeach
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered align-middle table-row-dashed fs-7 gy-3" id="rules_table">
                        <thead class="table-light">
                            <tr class="fw-bold text-gray-700 text-center">
                                <th class="min-w-60px">المجموعة</th>
                                <th class="min-w-150px">حساب مدين</th>
                                <th class="min-w-150px">حساب دائن</th>
                                <th class="min-w-130px">نوع المبلغ</th>
                                <th class="min-w-90px">القيمة</th>
                                <th class="min-w-100px">الحقل</th>
                                <th class="min-w-120px">مصدر مركز التكلفة</th>
                                <th class="min-w-120px">مركز ثابت</th>
                                <th class="min-w-70px">الترتيب</th>
                                <th class="min-w-60px">إلزامي</th>
                                <th class="w-40px"></th>
                            </tr>
                        </thead>
                        <tbody id="rules_body">

                            @php $existingRules = old('rules', isset($data) ? $data->rules->toArray() : []); @endphp

                            @if(count($existingRules) > 0)
                                @foreach($existingRules as $ri => $rule)
                                    <tr class="rule-row">
                                        <td><input type="number" name="rules[{{ $ri }}][rule_group]" value="{{ $rule['rule_group'] ?? 1 }}" class="form-control form-control-sm form-control-solid text-center" min="1" /></td>
                                        <td>
                                            <select name="rules[{{ $ri }}][debit_account_id]" class="form-select form-select-sm form-select-solid">
                                                <option value="">-- --</option>
                                                @foreach($accounts as $acc)
                                                    <option value="{{ $acc->id }}" {{ ($rule['debit_account_id'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->code }} - {{ $acc->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="rules[{{ $ri }}][credit_account_id]" class="form-select form-select-sm form-select-solid">
                                                <option value="">-- --</option>
                                                @foreach($accounts as $acc)
                                                    <option value="{{ $acc->id }}" {{ ($rule['credit_account_id'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->code }} - {{ $acc->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="rules[{{ $ri }}][amount_type]" class="form-select form-select-sm form-select-solid amount-type-select">
                                                <option value="fixed"         {{ ($rule['amount_type'] ?? '') == 'fixed'         ? 'selected' : '' }}>قيمة ثابتة</option>
                                                <option value="subtotal"      {{ ($rule['amount_type'] ?? '') == 'subtotal'      ? 'selected' : '' }}>المجموع الفرعي</option>
                                                <option value="tax"           {{ ($rule['amount_type'] ?? '') == 'tax'           ? 'selected' : '' }}>الضريبة</option>
                                                <option value="total"         {{ ($rule['amount_type'] ?? '') == 'total'         ? 'selected' : '' }}>الإجمالي</option>
                                                <option value="quantity_cost" {{ ($rule['amount_type'] ?? '') == 'quantity_cost' ? 'selected' : '' }}>الكمية × التكلفة</option>
                                                <option value="percentage"    {{ ($rule['amount_type'] ?? '') == 'percentage'    ? 'selected' : '' }}>نسبة %</option>
                                                <option value="formula"       {{ ($rule['amount_type'] ?? '') == 'formula'       ? 'selected' : '' }}>معادلة</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="rules[{{ $ri }}][amount_value]" value="{{ $rule['amount_value'] ?? '' }}" class="form-control form-control-sm form-control-solid" step="0.01" min="0" /></td>
                                        <td>
                                            <select name="rules[{{ $ri }}][amount_field]" class="form-select form-select-sm form-select-solid amount-field-select" data-value="{{ $rule['amount_field'] ?? '' }}">
                                                <option value="">-- --</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="rules[{{ $ri }}][cost_center_source]" class="form-select form-select-sm form-select-solid cc-source-select">
                                                <option value="">-- --</option>
                                                <option value="from_transaction" {{ ($rule['cost_center_source'] ?? '') == 'from_transaction' ? 'selected' : '' }}>من العملية</option>
                                                <option value="fixed"            {{ ($rule['cost_center_source'] ?? '') == 'fixed'            ? 'selected' : '' }}>ثابت</option>
                                                <option value="from_parent"      {{ ($rule['cost_center_source'] ?? '') == 'from_parent'      ? 'selected' : '' }}>من الأب</option>
                                                <option value="from_account"     {{ ($rule['cost_center_source'] ?? '') == 'from_account'     ? 'selected' : '' }}>من الحساب</option>
                                            </select>
                                        </td>
                                        <td class="fixed-cc-cell" style="{{ ($rule['cost_center_source'] ?? '') == 'fixed' ? '' : 'display:none' }}">
                                            <select name="rules[{{ $ri }}][fixed_cost_center_id]" class="form-select form-select-sm form-select-solid">
                                                <option value="">-- --</option>
                                                @foreach($costCenters as $cc)
                                                    <option value="{{ $cc->id }}" {{ ($rule['fixed_cost_center_id'] ?? '') == $cc->id ? 'selected' : '' }}>{{ $cc->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" name="rules[{{ $ri }}][sort_order]" value="{{ $rule['sort_order'] ?? 0 }}" class="form-control form-control-sm form-control-solid text-center" min="0" /></td>
                                        <td class="text-center"><input type="checkbox" name="rules[{{ $ri }}][is_required]" value="1" class="form-check-input" {{ ($rule['is_required'] ?? true) ? 'checked' : '' }} /></td>
                                        <td><button type="button" class="btn btn-sm btn-icon btn-light-danger btn-remove-rule"><i class="bi bi-x fs-4"></i></button></td>
                                    </tr>
                                @endforeach
                            @endif

                        </tbody>
                    </table>
                </div>

                {{-- template row (hidden, cloned by JS) --}}
                <template id="rule_row_template">
                    <tr class="rule-row">
                        <td><input type="number" name="rules[__IDX__][rule_group]" value="1" class="form-control form-control-sm form-control-solid text-center" min="1" /></td>
                        <td>
                            <select name="rules[__IDX__][debit_account_id]" class="form-select form-select-sm form-select-solid">
                                <option value="">-- --</option>
                                @foreach($accounts as $acc)<option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>@endforeach
                            </select>
                        </td>
                        <td>
                            <select name="rules[__IDX__][credit_account_id]" class="form-select form-select-sm form-select-solid">
                                <option value="">-- --</option>
                                @foreach($accounts as $acc)<option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>@endforeach
                            </select>
                        </td>
                        <td>
                            <select name="rules[__IDX__][amount_type]" class="form-select form-select-sm form-select-solid amount-type-select">
                                <option value="fixed">قيمة ثابتة</option>
                                <option value="subtotal">المجموع الفرعي</option>
                                <option value="tax">الضريبة</option>
                                <option value="total">الإجمالي</option>
                                <option value="quantity_cost">الكمية × التكلفة</option>
                                <option value="percentage">نسبة %</option>
                                <option value="formula">معادلة</option>
                            </select>
                        </td>
                        <td><input type="number" name="rules[__IDX__][amount_value]" value="" class="form-control form-control-sm form-control-solid" step="0.01" min="0" /></td>
                        <td>
                            <select name="rules[__IDX__][amount_field]" class="form-select form-select-sm form-select-solid amount-field-select" data-value="">
                                <option value="">-- --</option>
                            </select>
                        </td>
                        <td>
                            <select name="rules[__IDX__][cost_center_source]" class="form-select form-select-sm form-select-solid cc-source-select">
                                <option value="">-- --</option>
                                <option value="from_transaction">من العملية</option>
                                <option value="fixed">ثابت</option>
                                <option value="from_parent">من الأب</option>
                                <option value="from_account">من الحساب</option>
                            </select>
                        </td>
                        <td class="fixed-cc-cell" style="display:none">
                            <select name="rules[__IDX__][fixed_cost_center_id]" class="form-select form-select-sm form-select-solid">
                                <option value="">-- --</option>
                                @foreach($costCenters as $cc)<option value="{{ $cc->id }}">{{ $cc->name }}</option>@endforeach
                            </select>
                        </td>
                        <td><input type="number" name="rules[__IDX__][sort_order]" value="0" class="form-control form-control-sm form-control-solid text-center" min="0" /></td>
                        <td class="text-center"><input type="checkbox" name="rules[__IDX__][is_required]" value="1" class="form-check-input" checked /></td>
                        <td><button type="button" class="btn btn-sm btn-icon btn-light-danger btn-remove-rule"><i class="bi bi-x fs-4"></i></button></td>
                    </tr>
                </template>

            </div>
        </div>
    </div>{{-- end main --}}

</div>
