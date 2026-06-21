{{-- Posting Rule Variable Form (HTML only) --}}
<div class="card card-flush">
    <div class="card-header pt-5">
        <div class="card-title">
            <h2>بيانات المتغير</h2>
        </div>
    </div>
    <div class="card-body pt-0">

        <div class="row g-5">

            <div class="col-md-6">
                <label class="required form-label">القاعدة</label>
                <select name="rule_id" class="form-select form-select-solid @error('rule_id') is-invalid @enderror">
                    <option value="">-- اختر القاعدة --</option>
                    @foreach($rules as $rule)
                        <option value="{{ $rule->id }}"
                            {{ old('rule_id', isset($data) ? $data->rule_id : ($ruleId ?? '')) == $rule->id ? 'selected' : '' }}>
                            {{ $rule->scenario?->code ?? '?' }} / مجموعة {{ $rule->rule_group }}
                            @if($rule->debitAccount) &nbsp;| مدين: {{ $rule->debitAccount->code }} @endif
                            @if($rule->creditAccount) &nbsp;| دائن: {{ $rule->creditAccount->code }} @endif
                        </option>
                    @endforeach
                </select>
                @error('rule_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="required form-label">اسم المتغير</label>
                <input type="text" name="variable_name"
                       value="{{ old('variable_name', $data->variable_name ?? '') }}"
                       class="form-control form-control-solid @error('variable_name') is-invalid @enderror"
                       placeholder="total_tax" />
                @error('variable_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="required form-label">نوع المصدر</label>
                <select name="source_type" class="form-select form-select-solid @error('source_type') is-invalid @enderror">
                    <option value="">-- اختر --</option>
                    <option value="field"    {{ old('source_type', $data->source_type ?? '') == 'field'    ? 'selected' : '' }}>حقل (Field)</option>
                    <option value="function" {{ old('source_type', $data->source_type ?? '') == 'function' ? 'selected' : '' }}>دالة (Function)</option>
                    <option value="subquery" {{ old('source_type', $data->source_type ?? '') == 'subquery' ? 'selected' : '' }}>استعلام فرعي (Subquery)</option>
                </select>
                @error('source_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="required form-label">قيمة المصدر</label>
                <input type="text" name="source_value"
                       value="{{ old('source_value', $data->source_value ?? '') }}"
                       class="form-control form-control-solid @error('source_value') is-invalid @enderror"
                       placeholder="مثال: order.tax_amount" />
                @error('source_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

        </div>

    </div>
</div>
