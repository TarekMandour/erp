<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerWallet;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\PostingRule;
use App\Models\Finance\PostingScenario;
use App\Models\Finance\Supplier;
use App\Models\Finance\SupplierWallet;
use App\Models\Finance\Treasury;
use App\Models\Finance\Voucher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * QA Batch 2 — السندات (سيناريوهات 11.x في ERP_Finance_QA_Report.xlsx)
 */
class VoucherBatch2Test extends FinanceTestCase
{
    private Customer $customer;
    private Customer $customer2;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedVoucherScenariosAsBefore();
        $this->runMigrationUp('2026_11_18_000001_purchase_qa_batch1_fixes.php'); // الحساب 2105
        $this->runMigrationUp('2026_11_18_000002_voucher_qa_batch2_fixes.php');

        Treasury::create(['name' => 'خزنة', 'account_tree_id' => $this->acc('1101')->id, 'currency' => 'SAR', 'balance' => 0, 'is_active' => true]);

        $this->customer  = Customer::create(['customer_code' => 'C1', 'name' => 'عميل', 'phone' => '0101', 'join_date' => '2026-01-01', 'account_status' => 'active']);
        $this->customer2 = Customer::create(['customer_code' => 'C2', 'name' => 'عميل 2', 'phone' => '0102', 'join_date' => '2026-01-01', 'account_status' => 'active']);
        $this->supplier  = Supplier::create(['company_name' => 'مورد', 'name' => 'مورد', 'account_status' => 'active']);
    }

    /**
     * سيناريوهات السندات كما كانت مبذورة قبل الإصلاح: الأقساط والشيك بلا حساب للطرف المالي
     */
    private function seedVoucherScenariosAsBefore(): void
    {
        $id = fn ($code) => $this->acc($code)->id;
        $pay = ['CASH' => $id('1101'), 'CREDIT' => $id('1102'), 'INSTALLMENTS' => null, 'WALLET' => $id('1106'),
                'BANK_ONLINE' => $id('1107'), 'BANK_DIRECT' => $id('1108'), 'CHECK' => null];
        $party = ['CUSTOMER' => $id('1104'), 'SUPPLIER' => $id('2101')];

        foreach ($pay as $p => $payAcc) {
            foreach (['CUSTOMER', 'SUPPLIER', 'OTHER'] as $pt) {
                $op = $pt === 'OTHER' ? null : strtolower($pt) . '_payment';

                $r = PostingScenario::create(['code' => "VOUCHER_RECEIPT_{$p}_{$pt}", 'name' => 'r', 'operation_type' => $op ?? 'revenue', 'is_active' => 1, 'priority' => 10]);
                $this->rule($r->id, 1, $payAcc, null);
                $this->rule($r->id, 2, null, $party[$pt] ?? $id('4002'));

                $s = PostingScenario::create(['code' => "VOUCHER_PAYMENT_{$p}_{$pt}", 'name' => 'p', 'operation_type' => $op ?? 'expense', 'is_active' => 1, 'priority' => 10]);
                $this->rule($s->id, 1, $party[$pt] ?? $id('5007'), null);
                $this->rule($s->id, 2, null, $payAcc);
            }
        }
    }

    private function rule(int $scenarioId, int $sort, ?int $debit, ?int $credit): void
    {
        DB::table('posting_rules')->insert([
            'scenario_id' => $scenarioId, 'rule_group' => 1, 'debit_account_id' => $debit, 'credit_account_id' => $credit,
            'amount_type' => 'total', 'amount_field' => 'total_amount', 'is_required' => 1, 'sort_order' => $sort,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function voucher(array $o = []): array
    {
        return array_merge([
            'type' => 'receipt', 'payment_type' => 'cash', 'party_type' => 'customer', 'party_id' => $this->customer->id,
            'total_amount' => 100, 'date' => '2026-11-18', 'description' => 'QA',
        ], $o);
    }

    private function store(array $o = [])
    {
        return $this->post(route('finance.vouchers.store'), $this->voucher($o));
    }

    private function activeEntries(Voucher $v)
    {
        return JournalEntry::where('reference_type', Voucher::class)->where('reference_id', $v->id)
            ->where('status', 'posted')->where('is_reversed', false)->get();
    }

    // ---------------------------------------------------------------- 11.2

    public function test_11_2_installments_receipt_and_payment_post_entries(): void
    {
        $this->store(['payment_type' => 'installments'])->assertRedirect(route('finance.vouchers.index'));
        $this->store(['payment_type' => 'installments', 'type' => 'payment', 'party_type' => 'supplier', 'party_id' => $this->supplier->id, 'total_amount' => 40]);
        $this->store(['payment_type' => 'installments', 'party_type' => 'other', 'party_id' => null, 'party_name' => 'جهة', 'total_amount' => 7]);

        $this->assertSame(3, Voucher::count());
        foreach (Voucher::all() as $v) {
            $this->assertCount(1, $this->activeEntries($v));
        }
        $this->assertSame(100.0 - 40 + 7, $this->ledger('1101'));
        $this->assertSame(-100.0, $this->ledger('1104'));
        $this->assertSame(40.0, $this->ledger('2101'));
        $this->assertSame(-7.0, $this->ledger('4002'));
    }

    // ---------------------------------------------------------------- 11.3

    public function test_11_3_all_ui_payment_types_are_accepted_and_posted(): void
    {
        foreach (['wallet', 'bank_online', 'bank_direct', 'check'] as $pt) {
            $this->store(['payment_type' => $pt])->assertSessionHasNoErrors();
            $this->store(['payment_type' => $pt, 'type' => 'payment', 'party_type' => 'supplier', 'party_id' => $this->supplier->id])->assertSessionHasNoErrors();
        }

        $this->assertSame(8, Voucher::count());
        foreach (Voucher::all() as $v) {
            $this->assertCount(1, $this->activeEntries($v), "voucher {$v->payment_type}/{$v->type}");
        }
        $this->assertSame(100.0, $this->ledger('1109'));  // شيك قبض → أوراق قبض
        $this->assertSame(-100.0, $this->ledger('2105')); // شيك صرف → أوراق دفع
    }

    // ---------------------------------------------------------------- 2.1: لا سند بلا أثر

    public function test_voucher_without_valid_scenario_is_not_saved(): void
    {
        $s = PostingScenario::where('code', 'VOUCHER_RECEIPT_CASH_CUSTOMER')->first();
        PostingRule::where('scenario_id', $s->id)->delete();
        PostingScenario::where('code', 'ADJUSTMENT')->delete();
        Cache::flush();

        $this->store()->assertSessionHas('error');

        $this->assertSame(0, Voucher::count());
        $this->assertSame(0, CustomerWallet::count());
        $this->assertSame(0, JournalEntry::count());
    }

    // ---------------------------------------------------------------- 11.5 / 11.6

    public function test_11_5_supplier_receipt_numbering_and_wallet_direction_match_ledger(): void
    {
        $this->store(['party_type' => 'supplier', 'party_id' => $this->supplier->id, 'total_amount' => 300]);                       // قبض من المورد
        $this->store(['type' => 'payment', 'party_type' => 'supplier', 'party_id' => $this->supplier->id, 'total_amount' => 500]); // صرف للمورد

        [$receipt, $payment] = Voucher::orderBy('id')->get()->all();
        $this->assertStringStartsWith('REC-', $this->activeEntries($receipt)->first()->entry_number);
        $this->assertStringStartsWith('PAY-', $this->activeEntries($payment)->first()->entry_number);

        $this->assertEquals(300, (float) SupplierWallet::where('voucher_id', $receipt->id)->value('credit'));
        $this->assertEquals(500, (float) SupplierWallet::where('voucher_id', $payment->id)->value('debit'));

        // محفظة المورد (دائن − مدين) = رصيد 2101 الدائن
        $this->assertEquals(-200, (float) $this->supplier->fresh()->wallet_balance);
        $this->assertSame(200.0, $this->ledger('2101'));
        $this->assertEquals((float) $this->supplier->fresh()->wallet_balance, -$this->ledger('2101'));
    }

    public function test_11_6_payment_to_customer_uses_pay_prefix(): void
    {
        $this->store(['type' => 'payment']);
        $this->assertStringStartsWith('PAY-', $this->activeEntries(Voucher::first())->first()->entry_number);
    }

    // ---------------------------------------------------------------- 11.4 / 2.5

    public function test_11_4_other_party_can_choose_counter_account(): void
    {
        $this->store(['type' => 'payment', 'party_type' => 'other', 'party_id' => null, 'party_name' => 'كهرباء', 'counter_account_id' => $this->acc('5004')->id, 'total_amount' => 80])
            ->assertSessionHasNoErrors();
        $this->store(['party_type' => 'other', 'party_id' => null, 'party_name' => 'شحن', 'counter_account_id' => $this->acc('4004')->id, 'total_amount' => 30])
            ->assertSessionHasNoErrors();

        $this->assertSame(80.0, $this->ledger('5004'));
        $this->assertSame(-30.0, $this->ledger('4004'));
        $this->assertSame(0.0, $this->ledger('5007'));
        $this->assertSame(0.0, $this->ledger('4002'));
    }

    public function test_11_4_counter_account_must_be_a_leaf_and_not_cash(): void
    {
        $base = ['party_type' => 'other', 'party_id' => null, 'party_name' => 'x'];
        $this->store($base + ['counter_account_id' => $this->acc('5000')->id])->assertSessionHasErrors('counter_account_id');
        $this->store($base + ['counter_account_id' => $this->acc('1101')->id])->assertSessionHasErrors('counter_account_id');
        $this->assertSame(0, Voucher::count());
    }

    // ---------------------------------------------------------------- 11.12 / 2.4

    public function test_11_12_create_and_update_post_synchronously_without_queue(): void
    {
        Queue::fake();

        $this->store();
        $v = Voucher::first();
        $this->post(route('finance.vouchers.update'), $this->voucher(['id' => $v->id, 'total_amount' => 250]));

        Queue::assertNothingPushed();
        $this->assertCount(1, $this->activeEntries($v));
        $this->assertSame(250.0, $this->ledger('1101'));
        $this->assertSame(-250.0, $this->ledger('1104'));
    }

    public function test_delete_reverses_entry_and_wallet(): void
    {
        $this->store();
        $v = Voucher::first();
        $this->post(route('finance.vouchers.delete'), ['ids' => [$v->id]])->assertJson(['success' => true]);

        $this->assertSame(0, Voucher::count());
        $this->assertSame(0.0, $this->ledger('1101'));
        $this->assertEquals(0, (float) $this->customer->fresh()->wallet_balance);
    }

    // ---------------------------------------------------------------- 11.10 / 2.6

    public function test_11_10_moving_voucher_to_another_customer_updates_both_balances(): void
    {
        $this->store(['type' => 'payment', 'total_amount' => 200]);
        $v = Voucher::first();
        $this->assertEquals(200, (float) $this->customer->fresh()->wallet_balance);

        $this->post(route('finance.vouchers.update'), $this->voucher(['id' => $v->id, 'type' => 'payment', 'total_amount' => 200, 'party_id' => $this->customer2->id]));

        $this->assertEquals(0, (float) $this->customer->fresh()->wallet_balance);
        $this->assertEquals(200, (float) $this->customer2->fresh()->wallet_balance);
    }

    public function test_11_10_manual_wallet_entries_recalculate_balances_server_side(): void
    {
        $this->post(route('finance.customers.wallet.store', $this->customer->id), [
            'customer_id' => $this->customer->id, 'date' => '2026-11-18', 'debit' => 300, 'credit' => 0, 'balance' => 999,
        ]);
        $this->post(route('finance.customers.wallet.store', $this->customer->id), [
            'customer_id' => $this->customer->id, 'date' => '2026-11-18', 'debit' => 0, 'credit' => 100, 'balance' => 999,
        ]);
        $this->assertEquals(200, (float) $this->customer->fresh()->wallet_balance);
        $this->assertEquals(200, (float) CustomerWallet::latest('id')->value('balance'));

        $first = CustomerWallet::orderBy('id')->first();
        $this->post(route('finance.customers.wallet.delete', $this->customer->id), ['id' => [$first->id]]);
        $this->assertEquals(-100, (float) $this->customer->fresh()->wallet_balance);

        $this->post(route('finance.suppliers.wallet.store', $this->supplier->id), [
            'supplier_id' => $this->supplier->id, 'date' => '2026-11-18', 'debit' => 0, 'credit' => 1000,
        ]);
        $this->assertEquals(1000, (float) $this->supplier->fresh()->wallet_balance);
    }
}
