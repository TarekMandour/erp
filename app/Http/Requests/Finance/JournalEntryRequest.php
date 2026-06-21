<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class JournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_type' => 'required|in:sales,sales_return,sales_discount,sales_installment,purchase,purchase_return,purchase_discount,inventory_in,inventory_out,inventory_transfer,inventory_adjustment,inventory_write_off,inventory_revaluation,cash_deposit,cash_withdraw,cash_transfer,bank_deposit,bank_withdraw,bank_transfer,cheque_received,cheque_issued,cheque_cashed,wallet_deposit,wallet_withdraw,wallet_payment,wallet_refund,customer_payment,customer_credit_note,customer_debit_note,supplier_payment,supplier_credit_note,supplier_debit_note,expense,revenue,accrued_expense,prepaid_expense,depreciation,amortization,salary,salary_advance,salary_loan,overtime,bonus,commission,asset_purchase,asset_sale,asset_disposal,asset_depreciation,vat_input,vat_output,vat_payment,vat_refund,income_tax,withholding_tax,loan_received,loan_payment,loan_interest,capital_increase,capital_decrease,dividend_paid,internal_transfer,cost_allocation,profit_transfer,opening_balance,adjustment,revaluation,closing_entry,refund,write_off,reversal,correction',
            'date'                           => 'required|date',
            'description'                    => 'nullable|string',
            'reference_type'                 => 'nullable|string|max:255',
            'reference_id'                   => 'nullable|integer|min:1',
            'status'                         => 'required|in:draft,posted,canceled',
            'items'                          => 'required|array|min:2',
            'items.*.account_tree_id'        => 'required|exists:account_trees,id',
            'items.*.debit'                  => 'nullable|numeric|min:0',
            'items.*.credit'                 => 'nullable|numeric|min:0',
            'items.*.cost_center_id'         => 'nullable|exists:cost_centers,id',
            'items.*.description'            => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'entry_type.required'             => 'نوع القيد مطلوب',
            'entry_type.in'                   => 'نوع القيد غير صالح',
            'date.required'                   => 'تاريخ القيد مطلوب',
            'status.required'                 => 'حالة القيد مطلوبة',
            'items.required'                  => 'يجب إضافة أسطر للقيد',
            'items.min'                       => 'يجب أن يحتوي القيد على سطرين على الأقل',
            'items.*.account_tree_id.required' => 'الحساب مطلوب في كل سطر',
            'items.*.account_tree_id.exists'  => 'الحساب المحدد غير موجود',
        ];
    }
}
