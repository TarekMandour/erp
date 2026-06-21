<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 50)->unique()->comment('رقم القيد (مثلاً JE-2026-0001)');
            $table->enum('entry_type', [
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
            ])->comment('نوع القيد');
            $table->date('date')->comment('تاريخ القيد');
            $table->text('description')->nullable()->comment('وصف القيد');
            $table->string('reference_type')->nullable()->comment('نوع المستند المرتبط');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('رقم المستند المرتبط');
            $table->boolean('is_reversed')->default(false)->comment('هل هذا قيد عكسي؟');
            $table->foreignId('reversed_from')->nullable()->constrained('journal_entries')->nullOnDelete()->comment('تم عكسه من قيد رقم');
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete()->comment('من قام بالإضافة');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete()->comment('من قام بالاعتماد');
            $table->timestamp('approved_at')->nullable()->comment('تاريخ الاعتماد');
            $table->enum('status', ['draft', 'posted', 'canceled'])->default('draft');
            $table->timestamps();

            $table->index('date');
            $table->index(['reference_type', 'reference_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
