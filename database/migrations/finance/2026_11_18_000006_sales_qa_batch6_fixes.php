<?php

use App\Models\Finance\Coupon;
use App\Models\Finance\CouponUsage;
use App\Models\Finance\CustomerWallet;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryItem;
use App\Models\Finance\Offer;
use App\Models\Finance\Order;
use App\Models\Finance\Purchase;
use App\Models\Finance\WarehouseTransaction;
use App\Services\Finance\ManualJournalService;
use App\Services\Finance\OrderPricingService;
use App\Services\Finance\OrderService;
use App\Services\Finance\TransactionsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * QA Batch 6 (المبيعات + القرارات المعلّقة)
 *
 *  1. orders.bank_account_id (اختياري) لترحيل المحصّل البنكي على حساب بنكي فعلي
 *  2. account_trees.is_cash: الخزنة تُربط بحساب نقدية فقط (قرار 7.2) — يُفعَّل للصندوق 1101 ولأي حساب مربوط بخزنة
 *  3. قواعد ORDER_* : المحصّل على حساب التحصيل (الآجل/الأقساط: الصندوق، الشيك: أوراق قبض 1109)
 *     والباقي (total − paid) على العملاء 1104، والإيراد = subtotal + discount (كان يُخصم الخصم مرتين)
 *     — ORDER_RETURN_* تُصحَّح بالمثل وتُعطَّل حتى تُبنى شاشة المرتجعات
 *  4. قرار 1: تسوية فرق محفظة الموردين مع 2101 (فواتير شراء قديمة رُحّلت كنقدي كامل أو بلا قيد)
 *  5. إصلاح طلبات البيع الموجودة التي حُفظت مؤكدة بلا قيد/مخزون/محفظة، أو بقي لها أثر بعد الحذف/الإلغاء
 *  6. used_count للكوبونات والعروض = الاستخدام الفعلي
 */
return new class extends Migration
{
    /** كود السيناريو => كود حساب التحصيل (الجزء المدفوع) */
    private array $orderPaymentAccounts = [
        'CASH'         => '1101',
        'CREDIT'       => '1101',
        'INSTALLMENTS' => '1101',
        'WALLET'       => '1106',
        'BANK_ONLINE'  => '1107',
        'BANK_DIRECT'  => '1108',
        'CHECK'        => '1109',
    ];

    /** حساب الدفع لفواتير الشراء (مطابق للدفعة 1) */
    private array $purchasePaymentAccounts = [
        'cash' => '1101', 'credit' => '1101', 'installments' => '1101', 'wallet' => '1106',
        'bank_online' => '1107', 'bank_direct' => '1108', 'check' => '2105',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'bank_account_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('bank_account_id')->nullable()->after('payment_type')
                    ->constrained('bank_accounts')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('account_trees', 'is_cash')) {
            Schema::table('account_trees', function (Blueprint $table) {
                $table->boolean('is_cash')->default(false)->after('is_active')
                    ->comment('حساب نقدية يمكن ربطه بخزنة');
            });
        }

        DB::table('account_trees')
            ->where('code', '1101')
            ->orWhereIn('id', DB::table('treasuries')->whereNotNull('account_tree_id')->pluck('account_tree_id'))
            ->update(['is_cash' => 1]);

        // الأجزاء التالية تحتاج شجرة الحسابات المبذورة
        if (! DB::table('account_trees')->where('code', '1104')->exists()) {
            return;
        }

        $this->rewriteOrderRules();

        DB::transaction(function () {
            $this->reconcileSupplierWallets();
            $this->repairOrders();
            $this->recountUsage();
        });
    }

    public function down(): void
    {
        DB::table('posting_scenarios')->where('code', 'like', 'ORDER_RETURN_%')->update(['is_active' => 1]);

        if (Schema::hasColumn('orders', 'bank_account_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            });
        }
        if (Schema::hasColumn('account_trees', 'is_cash')) {
            Schema::table('account_trees', fn (Blueprint $table) => $table->dropColumn('is_cash'));
        }
        // القيود التصحيحية لا تُحذف آلياً (توجد نسخة احتياطية قبل الدفعة 6)
    }

    // =========================================================================
    //  3) قواعد ترحيل المبيعات
    // =========================================================================

    private function rewriteOrderRules(): void
    {
        $acc = fn (string $code) => DB::table('account_trees')->where('code', $code)->value('id');

        foreach ($this->orderPaymentAccounts as $suffix => $payCode) {
            foreach (['ORDER_' . $suffix => false, 'ORDER_RETURN_' . $suffix => true] as $code => $isReturn) {
                $scenarioId = DB::table('posting_scenarios')->where('code', $code)->value('id');
                if (! $scenarioId) {
                    continue;
                }

                // [حساب, اتجاه البيع (debit/credit), نوع المبلغ, الحقل, المعادلة, مجموعة]
                $lines = [
                    [$payCode, 'debit',  'total',   'paid',            null,                  1],
                    ['1104',   'debit',  'formula', null,              'total - paid',        1],
                    ['5008',   'debit',  'total',   'discount',        null,                  1],
                    ['5009',   'debit',  'total',   'coupon_discount', null,                  1],
                    ['5010',   'debit',  'total',   'offer_discount',  null,                  1],
                    ['4001',   'credit', 'formula', null,              'subtotal + discount', 1],
                    ['4004',   'credit', 'total',   'shipping_cost',   null,                  1],
                    ['2102',   'credit', 'tax',     'tax',             null,                  1],
                    ['5001',   'debit',  'quantity_cost', null,        null,                  2],
                    ['1103',   'credit', 'quantity_cost', null,        null,                  2],
                ];

                $rows = [];
                foreach ($lines as $i => [$account, $side, $type, $field, $formula, $group]) {
                    if ($isReturn) {
                        $side = $side === 'debit' ? 'credit' : 'debit';
                    }
                    $rows[] = [
                        'scenario_id'       => $scenarioId,
                        'rule_group'        => $group,
                        'debit_account_id'  => $side === 'debit' ? $acc($account) : null,
                        'credit_account_id' => $side === 'credit' ? $acc($account) : null,
                        'amount_type'       => $type,
                        'amount_field'      => $field,
                        'formula'           => $formula,
                        'is_required'       => in_array($account, ['4001', '2102', '5001', '1103'], true) ? 1 : 0,
                        'sort_order'        => $i + 1,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];
                }

                DB::table('posting_rules')->where('scenario_id', $scenarioId)->delete();
                DB::table('posting_rules')->insert($rows);

                Cache::forget("posting_rules:{$scenarioId}");
                Cache::forget("posting_scenario:{$code}");
            }
        }

        // لا توجد شاشة مرتجع مبيعات بعد: القواعد مصححة لكنها معطلة (مثل مرتجع المشتريات في الدفعة 1)
        DB::table('posting_scenarios')->where('code', 'like', 'ORDER_RETURN_%')->update(['is_active' => 0]);
        foreach (DB::table('posting_scenarios')->where('code', 'like', 'ORDER_RETURN_%')->pluck('code') as $code) {
            Cache::forget("posting_scenario:{$code}");
        }
    }

    // =========================================================================
    //  4) قرار 1: محفظة الموردين ↔ 2101
    // =========================================================================

    /**
     * لكل فاتورة شراء مستلمة: صافي المحفظة (دائن − مدين) يجب أن يساوي صافي 2101 في قيودها.
     * الفرق من فواتير ما قبل الدفعة 1:
     *  - لها قيد قديم رحّل كامل الإجمالي على حساب الدفع مهما كان المدفوع ← نقل الفرق بين حساب الدفع و2101
     *  - بلا قيد إطلاقاً (فشل الترحيل) ← القيد الناقص؛ والمخزون سبق تسويته مقابل 3001 في الدفعة 4
     * القيد التصحيحي مرجعه qa_batch6_migration + رقم الفاتورة (يُحتسب في المرة التالية فلا يتكرر).
     */
    private function reconcileSupplierWallets(): void
    {
        $acc       = fn (string $code) => DB::table('account_trees')->where('code', $code)->value('id');
        $suppliers = $acc('2101');
        if (! $suppliers) {
            return;
        }

        $touched = [];

        foreach (Purchase::where('status', 'received')->orderBy('id')->get() as $p) {
            $walletNet = round((float) DB::table('supplier_wallets')->where('purchase_id', $p->id)->sum(DB::raw('credit - debit')), 2);

            $glNet = round((float) DB::table('journal_entry_items as i')
                ->join('journal_entries as j', 'j.id', '=', 'i.journal_entry_id')
                ->where('i.account_tree_id', $suppliers)
                ->where('j.status', '!=', 'draft')
                ->where(fn ($q) => $q
                    ->where(fn ($q2) => $q2->where('j.reference_type', Purchase::class)->where('j.reference_id', $p->id))
                    ->orWhere(fn ($q2) => $q2->where('j.reference_type', 'qa_batch6_migration')->where('j.reference_id', $p->id)))
                ->sum(DB::raw('i.credit - i.debit')), 2);

            $diff = round($walletNet - $glNet, 2);
            if (abs($diff) < 0.01) {
                continue;
            }

            $payAccount = $acc($this->purchasePaymentAccounts[$p->payment_type] ?? '1101');
            $hasEntry   = JournalEntry::where('reference_type', Purchase::class)->where('reference_id', $p->id)
                ->where('status', '!=', 'draft')->exists();

            if ($hasEntry) {
                // الدائن الزائد على حساب الدفع ينتقل للمورد (أو العكس عند دفع أكثر من الإجمالي)
                $lines = $diff > 0
                    ? [[$payAccount, $diff, 0], [$suppliers, 0, $diff]]
                    : [[$suppliers, -$diff, 0], [$payAccount, 0, -$diff]];
                $desc = 'تصحيح ترحيل فاتورة شراء ' . $p->purchase_number . ': المدفوع الفعلي ' . number_format((float) $p->paid, 2)
                    . ' من ' . number_format((float) $p->total, 2) . ' (كان القيد القديم نقدياً بالكامل) — QA الدفعة 6';
            } else {
                $paid  = round((float) $p->paid, 2);
                $rest  = round((float) $p->total - $paid, 2);
                $lines = [[$acc('3001'), round((float) $p->subtotal, 2), 0], [$acc('2102'), round((float) $p->tax, 2), 0]];
                if ($paid > 0) {
                    $lines[] = [$payAccount, 0, $paid];
                }
                $lines[] = $rest >= 0 ? [$suppliers, 0, $rest] : [$suppliers, -$rest, 0];
                $desc = 'قيد فاتورة شراء ' . $p->purchase_number . ' لم يُرحَّل (المخزون سُوّي مقابل 3001 في الدفعة 4) — QA الدفعة 6';
            }

            $lines = array_values(array_filter($lines, fn ($l) => $l[0] && ($l[1] > 0 || $l[2] > 0)));
            $dr = round(array_sum(array_column($lines, 1)), 2);
            $cr = round(array_sum(array_column($lines, 2)), 2);
            if (abs($dr - $cr) >= 0.01) {
                Log::warning('QA batch6: skipped unbalanced supplier adjustment', ['purchase' => $p->id, 'dr' => $dr, 'cr' => $cr]);
                continue;
            }

            $entry = JournalEntry::create([
                'entry_number'   => app(ManualJournalService::class)->nextEntryNumber('JE'),
                'entry_type'     => 'adjustment',
                'date'           => now()->toDateString(),
                'description'    => $desc,
                'reference_type' => 'qa_batch6_migration',
                'reference_id'   => $p->id,
                'status'         => 'posted',
            ]);
            foreach ($lines as [$account, $debit, $credit]) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_tree_id'  => $account,
                    'debit'            => $debit,
                    'credit'           => $credit,
                    'description'      => $desc,
                ]);
                $touched[] = $account;
            }
        }

        if ($touched) {
            app(ManualJournalService::class)->recalculateAccounts(array_unique($touched));
        }
    }

    // =========================================================================
    //  5) طلبات البيع
    // =========================================================================

    private function repairOrders(): void
    {
        $service      = app(OrderService::class);
        $transactions = app(TransactionsService::class);

        // آثار طلبات محذوفة: قيود مرحّلة وحركات محفظة يتيمة
        $orphans = JournalEntry::where('reference_type', Order::class)
            ->where('is_reversed', false)->where('status', 'posted')
            ->whereNotIn('reference_id', Order::pluck('id'))->get();
        foreach ($orphans as $je) {
            $ghost = new Order();
            $ghost->id = $je->reference_id;
            $transactions->reverseEntry($je->load('items'), $ghost);
        }
        $orphanCustomers = CustomerWallet::whereNotNull('order_id')->whereNotIn('order_id', Order::pluck('id'))->pluck('customer_id')->unique();
        CustomerWallet::whereNotNull('order_id')->whereNotIn('order_id', Order::pluck('id'))->delete();
        CouponUsage::whereNotNull('order_id')->whereNotIn('order_id', Order::pluck('id'))->delete();
        foreach ($orphanCustomers as $customerId) {
            $service->recalculateCustomerBalance((int) $customerId);
        }

        foreach (Order::with('items.unitConversion')->orderBy('id')->get() as $order) {
            if (! $this->needsRepair($order)) {
                continue;
            }

            try {
                DB::transaction(function () use ($order, $service) {
                    $service->reverse($order);
                    if ($order->is_effective) {
                        $service->apply($order->fresh());
                    }
                });
            } catch (\Throwable $e) {
                // يبقى الطلب كما كان، ويُسجَّل لمراجعة المحاسب (مثلاً المخزون الحالي لا يكفي لإعادة الترحيل)
                Log::warning('QA batch6: order not repaired', ['order' => $order->order_number, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * يحتاج الطلب إصلاحاً إذا لم تطابق آثاره حالته:
     *  - فعّال: بلا قيد مبيعات مرحّل، أو بلا محفظة، أو صافي المخزون الخارج ≠ كميات البنود
     *  - غير فعّال (مسودة/ملغى): بقي له قيد مرحّل أو محفظة أو مخزون خارج
     */
    private function needsRepair(Order $order): bool
    {
        $postedEntries = JournalEntry::where('reference_type', Order::class)->where('reference_id', $order->id)
            ->where('is_reversed', false)->where('status', 'posted')->count();
        $walletRows = CustomerWallet::where('order_id', $order->id)->count();

        $moves  = WarehouseTransaction::where('reference_type', Order::class)->where('reference_id', $order->id)->get();
        $netOut = round($moves->where('type', 'out')->sum('quantity') - $moves->where('type', 'in')->sum('quantity'), 3);

        if (! $order->is_effective) {
            return $postedEntries > 0 || $walletRows > 0 || $netOut > 0;
        }

        $expectedOut = round($order->items->sum(fn ($i) => (float) $i->quantity * (float) ($i->unitConversion?->conversion_rate ?: 1)), 3);

        return $postedEntries === 0 || $walletRows === 0 || abs($netOut - $expectedOut) > 0.001;
    }

    // =========================================================================
    //  6) عدادات الاستخدام
    // =========================================================================

    private function recountUsage(): void
    {
        foreach (Coupon::all() as $coupon) {
            $coupon->update(['used_count' => CouponUsage::where('coupon_id', $coupon->id)->count()]);
        }
        $pricing = app(OrderPricingService::class);
        foreach (Offer::all() as $offer) {
            $offer->update(['used_count' => $pricing->offerUses($offer)]);
        }
    }
};
