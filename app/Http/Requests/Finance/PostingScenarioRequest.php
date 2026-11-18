<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostingScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('posting_scenario') ?? $this->input('id');

        return [
            'code'           => ['required', 'string', 'max:50', Rule::unique('posting_scenarios', 'code')->ignore($id)],
            'name'           => ['required', 'string', 'max:255'],
            'operation_type' => ['required', Rule::in(['sales','sales_return','sales_discount','sales_installment','purchase','purchase_return','purchase_discount','inventory_in','inventory_out','inventory_transfer','inventory_adjustment','inventory_write_off','inventory_revaluation','cash_deposit','cash_withdraw','cash_transfer','bank_deposit','bank_withdraw','bank_transfer','cheque_received','cheque_issued','cheque_cashed','wallet_deposit','wallet_withdraw','wallet_payment','wallet_refund','customer_payment','customer_credit_note','customer_debit_note','supplier_payment','supplier_credit_note','supplier_debit_note','expense','revenue','accrued_expense','prepaid_expense','depreciation','amortization','salary','salary_advance','salary_loan','overtime','bonus','commission','asset_purchase','asset_sale','asset_disposal','asset_depreciation','vat_input','vat_output','vat_payment','vat_refund','income_tax','withholding_tax','loan_received','loan_payment','loan_interest','capital_increase','capital_decrease','dividend_paid','internal_transfer','cost_allocation','profit_transfer','opening_balance','adjustment','revaluation','closing_entry','refund','write_off','reversal','correction'])],
            'is_active'      => ['nullable', 'boolean'],
            'priority'       => ['nullable', 'integer'],
            'description'    => ['nullable', 'string'],

            'rules'                          => ['required', 'array', 'min:1'],
            'rules.*.rule_group'             => ['required', 'integer', 'min:1'],
            'rules.*.debit_account_id'       => ['nullable', 'exists:account_trees,id'],
            'rules.*.credit_account_id'      => ['nullable', 'exists:account_trees,id'],
            'rules.*.amount_type'            => ['required', Rule::in(['fixed', 'subtotal', 'tax', 'total', 'quantity_cost', 'percentage', 'formula', 'field'])],
            'rules.*.amount_value'           => ['nullable', 'numeric', 'min:0'],
            'rules.*.amount_field'           => ['nullable', 'string', 'max:100'],
            'rules.*.formula'                => ['nullable', 'string'],
            'rules.*.conditions'             => ['nullable', 'string'],
            'rules.*.cost_center_source'     => ['nullable', Rule::in(['from_transaction', 'fixed', 'from_parent', 'from_account'])],
            'rules.*.fixed_cost_center_id'   => ['nullable', 'exists:cost_centers,id'],
            'rules.*.is_required'            => ['nullable', 'boolean'],
            'rules.*.sort_order'             => ['nullable', 'integer'],

            'rules.*.variables'                  => ['nullable', 'array'],
            'rules.*.variables.*.variable_name'  => ['required', 'string', 'max:100'],
            'rules.*.variables.*.source_type'    => ['required', Rule::in(['field','function','subquery'])],
            'rules.*.variables.*.source_value'   => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * منطق القواعد: جانب واحد لكل قاعدة، نسبة ≤ 100، مبلغ ثابت > 0، صيغة آمنة،
     * وكل مجموعة قواعد فيها مدين ودائن (والقواعد الثابتة بالكامل متوازنة).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $evaluator = app(\App\Services\Finance\TransactionsService\SafeFormulaEvaluator::class);
            $groups    = [];

            foreach (array_values((array) $this->input('rules', [])) as $i => $rule) {
                $n      = $i + 1;
                $debit  = ! empty($rule['debit_account_id']);
                $credit = ! empty($rule['credit_account_id']);
                $type   = $rule['amount_type'] ?? null;
                $value  = (float) ($rule['amount_value'] ?? 0);

                if ($debit === $credit) {
                    $validator->errors()->add("rules.{$i}", "القاعدة {$n}: اختر حساباً مديناً أو دائناً فقط (حساب واحد لكل قاعدة)");
                }
                if ($type === 'percentage' && ($value <= 0 || $value > 100)) {
                    $validator->errors()->add("rules.{$i}.amount_value", "القاعدة {$n}: النسبة يجب أن تكون بين 0 و 100");
                }
                if ($type === 'fixed' && $value <= 0) {
                    $validator->errors()->add("rules.{$i}.amount_value", "القاعدة {$n}: المبلغ الثابت يجب أن يكون أكبر من صفر");
                }
                if ($type === 'formula') {
                    $vars = array_column((array) ($rule['variables'] ?? []), 'variable_name');
                    if (empty($rule['formula']) || ! $evaluator->isValidFormula($rule['formula'], $vars)) {
                        $validator->errors()->add("rules.{$i}.formula", "القاعدة {$n}: الصيغة غير صالحة. المسموح: أرقام و + - * / ( ) والمتغيرات total, subtotal, tax, discount, paid, shipping ...");
                    }
                }

                $g = (int) ($rule['rule_group'] ?? 1);
                $groups[$g]['debit']  = ($groups[$g]['debit'] ?? false) || $debit;
                $groups[$g]['credit'] = ($groups[$g]['credit'] ?? false) || $credit;
                $groups[$g]['all_fixed'] = ($groups[$g]['all_fixed'] ?? true) && $type === 'fixed';
                $groups[$g]['fixed'] = ($groups[$g]['fixed'] ?? 0) + ($debit && ! $credit ? $value : 0) - ($credit && ! $debit ? $value : 0);
            }

            foreach ($groups as $g => $info) {
                if (! $info['debit'] || ! $info['credit']) {
                    $validator->errors()->add('rules', "مجموعة القواعد {$g}: يجب أن تحتوي على قاعدة مدينة وقاعدة دائنة على الأقل (وإلا ينتج قيد غير متوازن)");
                } elseif ($info['all_fixed'] && round($info['fixed'], 2) != 0.0) {
                    $validator->errors()->add('rules', "مجموعة القواعد {$g}: مجموع المبالغ الثابتة المدينة لا يساوي الدائنة");
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'rules.required' => 'يجب إضافة قاعدة ترحيل واحدة على الأقل',
            'rules.min'      => 'يجب إضافة قاعدة ترحيل واحدة على الأقل',
        ];
    }

    public function attributes(): array
    {
        return [
            'code'           => 'رمز السيناريو',
            'name'           => 'اسم السيناريو',
            'operation_type' => 'نوع العملية',
            'priority'       => 'الأولوية',
        ];
    }
}
