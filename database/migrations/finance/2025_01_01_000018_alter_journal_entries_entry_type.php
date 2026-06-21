<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ENUM_VALUES = [
        // المبيعات
        'sales', 'sales_return', 'sales_discount', 'sales_installment',
        // المشتريات
        'purchase', 'purchase_return', 'purchase_discount',
        // المخزون
        'inventory_in', 'inventory_out', 'inventory_transfer', 'inventory_adjustment',
        'inventory_write_off', 'inventory_revaluation',
        // الصندوق
        'cash_deposit', 'cash_withdraw', 'cash_transfer',
        // البنوك
        'bank_deposit', 'bank_withdraw', 'bank_transfer',
        // الشيكات
        'cheque_received', 'cheque_issued', 'cheque_cashed',
        // المحفظة
        'wallet_deposit', 'wallet_withdraw', 'wallet_payment', 'wallet_refund',
        // العملاء
        'customer_payment', 'customer_credit_note', 'customer_debit_note',
        // الموردين
        'supplier_payment', 'supplier_credit_note', 'supplier_debit_note',
        // المصروفات والإيرادات
        'expense', 'revenue', 'accrued_expense', 'prepaid_expense',
        'depreciation', 'amortization',
        // الرواتب
        'salary', 'salary_advance', 'salary_loan', 'overtime', 'bonus', 'commission',
        // الأصول الثابتة
        'asset_purchase', 'asset_sale', 'asset_disposal', 'asset_depreciation',
        // الضرائب
        'vat_input', 'vat_output', 'vat_payment', 'vat_refund',
        'income_tax', 'withholding_tax',
        // القروض
        'loan_received', 'loan_payment', 'loan_interest',
        // رأس المال
        'capital_increase', 'capital_decrease', 'dividend_paid',
        // التحويلات الداخلية
        'internal_transfer', 'cost_allocation', 'profit_transfer',
        // أرصدة افتتاحية وتسويات
        'opening_balance', 'adjustment', 'revaluation', 'closing_entry',
        // عمليات أخرى
        'refund', 'write_off', 'reversal', 'correction',
    ];

    public function up(): void
    {
        // Step 1: Expand enum to include BOTH old legacy values and new values
        $legacy = ['receipt', 'payment', 'transfer', 'opening'];
        $expanded = implode(',', array_map(fn ($v) => "'{$v}'", array_unique(array_merge($legacy, self::ENUM_VALUES))));
        DB::statement("ALTER TABLE `journal_entries` MODIFY COLUMN `entry_type` ENUM({$expanded}) NOT NULL");

        // Step 2: Remap legacy values to their equivalents in the new list
        DB::table('journal_entries')->where('entry_type', 'receipt')->update(['entry_type' => 'customer_payment']);
        DB::table('journal_entries')->where('entry_type', 'payment')->update(['entry_type' => 'supplier_payment']);
        DB::table('journal_entries')->where('entry_type', 'transfer')->update(['entry_type' => 'internal_transfer']);
        DB::table('journal_entries')->where('entry_type', 'opening')->update(['entry_type' => 'opening_balance']);

        // Step 3: Finalize column to only the new enum values
        $enumList = implode(',', array_map(fn ($v) => "'{$v}'", self::ENUM_VALUES));
        DB::statement("ALTER TABLE `journal_entries` MODIFY COLUMN `entry_type` ENUM({$enumList}) COMMENT 'نوع القيد' NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `journal_entries` MODIFY COLUMN `entry_type` ENUM('sales','purchase','receipt','payment','expense','transfer','opening','adjustment') COMMENT 'نوع القيد' NOT NULL");
    }
};
