<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QA Batch 1 (المشتريات) — سيناريوهات 12.4، 12.5، 12.17، 12.18
 *
 *  1. purchases.bank_account_id (اختياري) لربط الدفع البنكي/المحفظة بحساب بنكي فعلي
 *  2. إضافة الحساب 2105 «أوراق دفع (شيكات مستحقة الدفع)» إن لم يوجد
 *  3. إعادة كتابة قواعد PURCHASE_*: المخزون = subtotal (بدون خصم مزدوج)،
 *     المدفوع على حساب الدفع والباقي (total - paid) على الموردين 2101 لكل الأنواع
 *  4. تعطيل سيناريوهات مرتجع الشراء (لا توجد شاشة مرتجع بعد) وإصلاح قاعدة الشيك فيها
 */
return new class extends Migration
{
    /** كود السيناريو => كود حساب الدفع (الجزء المدفوع) */
    private array $paymentAccounts = [
        'PURCHASE_CASH'         => '1101',
        'PURCHASE_CREDIT'       => '1101',
        'PURCHASE_INSTALLMENTS' => '1101',
        'PURCHASE_WALLET'       => '1106',
        'PURCHASE_BANK_ONLINE'  => '1107',
        'PURCHASE_BANK_DIRECT'  => '1108',
        'PURCHASE_CHECK'        => '2105',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('purchases', 'bank_account_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->foreignId('bank_account_id')
                    ->nullable()
                    ->after('payment_type')
                    ->constrained('bank_accounts')
                    ->nullOnDelete();
            });
        }

        // البيانات تُعدَّل فقط إذا كانت الشجرة والسيناريوهات مبذورة
        $parent = DB::table('account_trees')->where('code', '2100')->first();
        if (! $parent) {
            return;
        }

        if (! DB::table('account_trees')->where('code', '2105')->exists()) {
            DB::table('account_trees')->insert([
                'name'         => 'أوراق دفع (شيكات مستحقة الدفع)',
                'code'         => '2105',
                'parent_id'    => $parent->id,
                'type'         => 'liability',
                'account_type' => 'credit',
                'level'        => $parent->level + 1,
                'is_active'    => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $acc = fn (string $code) => DB::table('account_trees')->where('code', $code)->value('id');

        $inventory = $acc('1103');
        $vat       = $acc('2102');
        $suppliers = $acc('2101');

        foreach ($this->paymentAccounts as $code => $payCode) {
            $scenarioId = DB::table('posting_scenarios')->where('code', $code)->value('id');
            if (! $scenarioId) {
                continue;
            }

            DB::table('posting_rules')->where('scenario_id', $scenarioId)->delete();

            DB::table('posting_rules')->insert([
                $this->rule($scenarioId, 1, $inventory, null, 'subtotal', 'subtotal'),
                $this->rule($scenarioId, 2, $vat, null, 'tax', 'tax'),
                $this->rule($scenarioId, 3, null, $acc($payCode), 'total', 'paid'),
                $this->rule($scenarioId, 4, null, $suppliers, 'formula', null, 'total - paid'),
            ]);

            Cache::forget("posting_rules:{$scenarioId}");
            Cache::forget("posting_scenario:{$code}");
        }

        // مرتجعات المشتريات: إصلاح حساب الشيك ثم التعطيل حتى تُبنى الشاشة
        $returnCheckId = DB::table('posting_scenarios')->where('code', 'PURCHASE_RETURN_CHECK')->value('id');
        if ($returnCheckId) {
            DB::table('posting_rules')
                ->where('scenario_id', $returnCheckId)
                ->whereNull('debit_account_id')
                ->whereNull('credit_account_id')
                ->update(['debit_account_id' => $acc('2105')]);
            Cache::forget("posting_rules:{$returnCheckId}");
        }

        DB::table('posting_scenarios')->where('code', 'like', 'PURCHASE_RETURN_%')->update(['is_active' => 0]);
        foreach (DB::table('posting_scenarios')->where('code', 'like', 'PURCHASE_RETURN_%')->pluck('code') as $code) {
            Cache::forget("posting_scenario:{$code}");
        }
    }

    public function down(): void
    {
        DB::table('posting_scenarios')->where('code', 'like', 'PURCHASE_RETURN_%')->update(['is_active' => 1]);

        if (Schema::hasColumn('purchases', 'bank_account_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropForeign(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            });
        }
        // قواعد الترحيل القديمة لا تُستعاد (كانت خاطئة): أعد تشغيل PostingRuleSeeder عند الحاجة
    }

    private function rule(int $scenarioId, int $sort, ?int $debit, ?int $credit, string $type, ?string $field, ?string $formula = null): array
    {
        return [
            'scenario_id'       => $scenarioId,
            'rule_group'        => 1,
            'debit_account_id'  => $debit,
            'credit_account_id' => $credit,
            'amount_type'       => $type,
            'amount_field'      => $field,
            'formula'           => $formula,
            'is_required'       => 1,
            'sort_order'        => $sort,
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
    }
};
