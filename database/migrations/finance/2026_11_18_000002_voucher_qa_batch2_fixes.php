<?php

use App\Services\Finance\WalletBalanceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QA Batch 2 (السندات) — سيناريوهات 11.2، 11.3، 11.4، 11.5، 11.10، 11.11
 *
 *  1. vouchers.counter_account_id (اختياري): الحساب المقابل للطرف «أخرى»
 *  2. الحساب 1109 «أوراق قبض (شيكات تحت التحصيل)»
 *  3. إكمال قواعد VOUCHER_*_INSTALLMENTS_* (1101) و VOUCHER_*_CHECK_* (قبض 1109 / صرف 2105)
 *  4. توحيد اتجاه حركات محفظة المورد الناتجة عن السندات (صرف = مدين، قبض = دائن)
 *  5. إعادة حساب كل أرصدة المحافظ من الحركات
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vouchers', 'counter_account_id')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->foreignId('counter_account_id')
                    ->nullable()
                    ->after('financial_account_id')
                    ->constrained('account_trees')
                    ->nullOnDelete();
            });
        }

        $parent = DB::table('account_trees')->where('code', '1100')->first();
        if ($parent && ! DB::table('account_trees')->where('code', '1109')->exists()) {
            DB::table('account_trees')->insert([
                'name'         => 'أوراق قبض (شيكات تحت التحصيل)',
                'code'         => '1109',
                'parent_id'    => $parent->id,
                'type'         => 'asset',
                'account_type' => 'debit',
                'level'        => $parent->level + 1,
                'is_active'    => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $acc = fn (string $code) => DB::table('account_trees')->where('code', $code)->value('id');

        // القواعد الناقصة (الطرف المالي بلا حساب)
        $missing = [
            'VOUCHER_RECEIPT_INSTALLMENTS_%' => ['debit_account_id'  => $acc('1101')],
            'VOUCHER_PAYMENT_INSTALLMENTS_%' => ['credit_account_id' => $acc('1101')],
            'VOUCHER_RECEIPT_CHECK_%'        => ['debit_account_id'  => $acc('1109')],
            'VOUCHER_PAYMENT_CHECK_%'        => ['credit_account_id' => $acc('2105')],
        ];

        foreach ($missing as $pattern => $set) {
            if (in_array(null, $set, true)) {
                continue; // الشجرة غير مبذورة (بيئة اختبار فارغة)
            }

            $scenarioIds = DB::table('posting_scenarios')->where('code', 'like', $pattern)->pluck('id');

            DB::table('posting_rules')
                ->whereIn('scenario_id', $scenarioIds)
                ->whereNull('debit_account_id')
                ->whereNull('credit_account_id')
                ->update($set + ['updated_at' => now()]);

            foreach ($scenarioIds as $id) {
                Cache::forget("posting_rules:{$id}");
            }
        }

        // محفظة المورد من السندات كانت بإشارة معكوسة: صرف → دائن، قبض → مدين
        $wrong = DB::table('supplier_wallets as w')
            ->join('vouchers as v', 'v.id', '=', 'w.voucher_id')
            ->where(function ($q) {
                $q->where(fn ($q) => $q->where('v.type', 'payment')->where('w.credit', '>', 0))
                  ->orWhere(fn ($q) => $q->where('v.type', 'receipt')->where('w.debit', '>', 0));
            })
            ->pluck('w.id');

        // تبديل صريح بالقيم (MySQL ينفّذ SET من اليسار لليمين فلا يصح debit=credit, credit=debit)
        foreach (DB::table('supplier_wallets')->whereIn('id', $wrong)->get(['id', 'debit', 'credit']) as $row) {
            DB::table('supplier_wallets')->where('id', $row->id)
                ->update(['debit' => $row->credit, 'credit' => $row->debit]);
        }

        $service = app(WalletBalanceService::class);
        foreach (DB::table('suppliers')->pluck('id') as $id) {
            $service->recalculateSupplier((int) $id);
        }
        foreach (DB::table('customers')->pluck('id') as $id) {
            $service->recalculateCustomer((int) $id);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vouchers', 'counter_account_id')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->dropForeign(['counter_account_id']);
                $table->dropColumn('counter_account_id');
            });
        }
        // تصحيحات البيانات (القواعد والإشارات) لا تُعكس لأنها كانت أخطاء
    }
};
