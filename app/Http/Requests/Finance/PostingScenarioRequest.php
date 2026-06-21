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

            'rules'                          => ['nullable', 'array'],
            'rules.*.rule_group'             => ['required', 'integer', 'min:1'],
            'rules.*.debit_account_id'       => ['nullable', 'exists:account_trees,id'],
            'rules.*.credit_account_id'      => ['nullable', 'exists:account_trees,id'],
            'rules.*.amount_type'            => ['required', Rule::in(['fixed', 'subtotal', 'tax', 'total', 'quantity_cost', 'percentage', 'formula'])],
            'rules.*.amount_value'           => ['nullable', 'numeric', 'min:0'],
            'rules.*.amount_field'           => ['nullable', 'string', 'max:100'],
            'rules.*.formula'                => ['nullable', 'string'],
            'rules.*.conditions'             => ['nullable', 'string'],
            'rules.*.cost_center_source'     => ['nullable', Rule::in(['from_transaction', 'fixed', 'from_parent', 'from_account'])],
            'rules.*.fixed_cost_center_id'   => ['nullable', 'exists:cost_centers,id'],
            'rules.*.is_required'            => ['nullable', 'boolean'],
            'rules.*.sort_order'             => ['nullable', 'integer'],
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
