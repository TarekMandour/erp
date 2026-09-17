<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE posting_scenarios MODIFY operation_type ENUM(
            'sales', 'sales_return', 'sales_discount', 'sales_installment',
            'purchase', 'purchase_return', 'purchase_discount',
            'order',
            'inventory_in', 'inventory_out', 'inventory_transfer', 'inventory_adjustment',
            'inventory_write_off', 'inventory_revaluation',
            'cash_deposit', 'cash_withdraw', 'cash_transfer',
            'bank_deposit', 'bank_withdraw', 'bank_transfer',
            'cheque_received', 'cheque_issued', 'cheque_cashed',
            'wallet_deposit', 'wallet_withdraw', 'wallet_payment', 'wallet_refund',
            'customer_payment', 'customer_credit_note', 'customer_debit_note',
            'supplier_payment', 'supplier_credit_note', 'supplier_debit_note',
            'expense', 'revenue', 'accrued_expense', 'prepaid_expense',
            'depreciation', 'amortization',
            'salary', 'salary_advance', 'salary_loan', 'overtime', 'bonus', 'commission',
            'asset_purchase', 'asset_sale', 'asset_disposal', 'asset_depreciation',
            'vat_input', 'vat_output', 'vat_payment', 'vat_refund',
            'income_tax', 'withholding_tax',
            'loan_received', 'loan_payment', 'loan_interest',
            'capital_increase', 'capital_decrease', 'dividend_paid',
            'internal_transfer', 'cost_allocation', 'profit_transfer',
            'opening_balance', 'adjustment', 'revaluation', 'closing_entry',
            'refund', 'write_off', 'reversal', 'correction'
        ) COMMENT 'نوع العملية'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE posting_scenarios MODIFY operation_type ENUM(
            'sales', 'sales_return', 'sales_discount', 'sales_installment',
            'purchase', 'purchase_return', 'purchase_discount',
            'inventory_in', 'inventory_out', 'inventory_transfer', 'inventory_adjustment',
            'inventory_write_off', 'inventory_revaluation',
            'cash_deposit', 'cash_withdraw', 'cash_transfer',
            'bank_deposit', 'bank_withdraw', 'bank_transfer',
            'cheque_received', 'cheque_issued', 'cheque_cashed',
            'wallet_deposit', 'wallet_withdraw', 'wallet_payment', 'wallet_refund',
            'customer_payment', 'customer_credit_note', 'customer_debit_note',
            'supplier_payment', 'supplier_credit_note', 'supplier_debit_note',
            'expense', 'revenue', 'accrued_expense', 'prepaid_expense',
            'depreciation', 'amortization',
            'salary', 'salary_advance', 'salary_loan', 'overtime', 'bonus', 'commission',
            'asset_purchase', 'asset_sale', 'asset_disposal', 'asset_depreciation',
            'vat_input', 'vat_output', 'vat_payment', 'vat_refund',
            'income_tax', 'withholding_tax',
            'loan_received', 'loan_payment', 'loan_interest',
            'capital_increase', 'capital_decrease', 'dividend_paid',
            'internal_transfer', 'cost_allocation', 'profit_transfer',
            'opening_balance', 'adjustment', 'revaluation', 'closing_entry',
            'refund', 'write_off', 'reversal', 'correction'
        ) COMMENT 'نوع العملية'");
    }
};
