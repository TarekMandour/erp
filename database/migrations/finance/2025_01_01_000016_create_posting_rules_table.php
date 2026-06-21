<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posting_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained('posting_scenarios')->cascadeOnDelete();
            $table->integer('rule_group')->default(1)->comment('مجموعة القواعد (نفس الرقم يعني نفس القيد)');
            $table->foreignId('debit_account_id')->nullable()->constrained('account_trees')->nullOnDelete()->comment('الحساب المدين');
            $table->foreignId('credit_account_id')->nullable()->constrained('account_trees')->nullOnDelete()->comment('الحساب الدائن');
            $table->enum('amount_type', ['fixed', 'subtotal', 'tax', 'total', 'quantity_cost', 'percentage', 'formula'])->default('fixed');
            $table->decimal('amount_value', 15, 2)->nullable()->comment('قيمة ثابتة أو نسبة');
            $table->string('amount_field', 100)->nullable()->comment('حقل من العملية (مثل total, subtotal, tax)');
            $table->text('formula')->nullable()->comment('معادلة حسابية معقدة');
            $table->json('conditions')->nullable()->comment('شروط التطبيق (JSON)');
            $table->enum('cost_center_source', ['from_transaction', 'fixed', 'from_parent', 'from_account'])->nullable()->comment('مصدر مركز التكلفة');
            $table->foreignId('fixed_cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete()->comment('مركز تكلفة ثابت');
            $table->boolean('is_required')->default(true)->comment('هل هذه القاعدة إجبارية؟');
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index('scenario_id');
            $table->index('debit_account_id');
            $table->index('credit_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_rules');
    }
};
