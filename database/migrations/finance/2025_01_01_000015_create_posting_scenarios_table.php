<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posting_scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('رمز السيناريو (مثل SALE_CREDIT, SALE_CASH)');
            $table->string('name')->comment('اسم السيناريو');
            $table->enum('operation_type', [
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
            ])->comment('نوع العملية');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0)->comment('أولوية التطبيق');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index('operation_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_scenarios');
    }
};
