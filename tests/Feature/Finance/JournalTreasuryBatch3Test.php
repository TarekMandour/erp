<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\AccountTree;
use App\Models\Finance\Bank;
use App\Models\Finance\BankAccount;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryItem;
use App\Models\Finance\OpeningBalance;
use App\Models\Finance\PostingScenario;
use App\Models\Finance\Treasury;
use App\Models\Finance\TreasuryTransaction;
use Illuminate\Support\Facades\DB;

/**
 * QA Batch 3 — القيود، الشجرة، الخزن والبنوك (سيناريوهات 6.x – 10.x في ERP_Finance_QA_Report.xlsx)
 */
class JournalTreasuryBatch3Test extends FinanceTestCase
{
    private Treasury $treasury;
    private BankAccount $bank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->treasury = Treasury::create(['name' => 'الخزنة', 'account_tree_id' => $this->acc('1101')->id, 'currency' => 'SAR', 'balance' => 0, 'is_active' => true]);
        $bankModel      = Bank::create(['name' => 'بنك']);
        $this->bank     = BankAccount::create(['bank_id' => $bankModel->id, 'account_tree_id' => $this->acc('1102')->id, 'account_number' => 'EG1', 'currency' => 'SAR', 'balance' => 0]);
    }

    private function entry(array $lines, string $status = 'posted', array $o = [])
    {
        return $this->post(route('finance.journal_entries.store'), array_merge([
            'entry_type' => 'adjustment', 'date' => '2026-11-18', 'status' => $status,
            'items'      => array_map(fn ($l) => ['account_tree_id' => $this->acc($l[0])->id, 'debit' => $l[1], 'credit' => $l[2]], $lines),
        ], $o));
    }

    private function lastEntry(): JournalEntry
    {
        return JournalEntry::latest('id')->firstOrFail();
    }

    // ── 8.x القيود اليومية ────────────────────────────────────────────────

    /** 8.2 */
    public function test_unbalanced_posted_entry_is_rejected(): void
    {
        $this->entry([['1101', 100, 0], ['3001', 0, 50]])->assertSessionHasErrors('items');
        $this->assertSame(0, JournalEntry::count());
        $this->assertEquals(0, $this->ledger('1101'));
    }

    /** 8.3 */
    public function test_zero_two_sided_and_duplicate_lines_are_rejected(): void
    {
        $this->entry([['1101', 0, 0], ['3001', 0, 0]])->assertSessionHasErrors();
        $this->entry([['1101', 100, 100], ['3001', 0, 0]])->assertSessionHasErrors();
        $this->entry([['1101', 100, 0], ['1101', 0, 100]])->assertSessionHasErrors();

        $this->assertSame(0, JournalEntry::count());
        $this->assertSame(0, TreasuryTransaction::count());
    }

    /** 8.5 */
    public function test_posting_on_parent_account_is_rejected(): void
    {
        $this->entry([['1100', 100, 0], ['3001', 0, 100]])->assertSessionHasErrors('items.0');
        $this->assertSame(0, JournalEntry::count());
    }

    /** 8.4 */
    public function test_draft_entry_does_not_affect_tree_or_treasury_until_posted(): void
    {
        $this->entry([['1101', 500, 0], ['3001', 0, 500]], 'draft')->assertSessionHasNoErrors();
        $draft = $this->lastEntry();

        $this->assertEquals(0, $this->ledger('1101'));
        $this->assertEquals(0, (float) $this->treasury->fresh()->balance);
        $this->assertSame(0, TreasuryTransaction::count());

        // ترحيل المسودة من شاشة التعديل
        $this->post(route('finance.journal_entries.update'), [
            'id' => $draft->id, 'entry_type' => 'adjustment', 'date' => '2026-11-18', 'status' => 'posted',
            'items' => [['account_tree_id' => $this->acc('1101')->id, 'debit' => 500, 'credit' => 0], ['account_tree_id' => $this->acc('3001')->id, 'debit' => 0, 'credit' => 500]],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(500, $this->ledger('1101'));
        $this->assertEquals(-500, $this->ledger('3001'));
        $this->assertEquals(500, (float) $this->treasury->fresh()->balance);
    }

    /** 8.8 */
    public function test_canceling_posted_entry_reverses_balances(): void
    {
        $this->entry([['1101', 300, 0], ['3001', 0, 300]])->assertSessionHasNoErrors();
        $entry = $this->lastEntry();

        $this->post(route('finance.journal_entries.update'), [
            'id' => $entry->id, 'entry_type' => 'adjustment', 'date' => '2026-11-18', 'status' => 'canceled',
            'items' => [['account_tree_id' => $this->acc('1101')->id, 'debit' => 300, 'credit' => 0], ['account_tree_id' => $this->acc('3001')->id, 'debit' => 0, 'credit' => 300]],
        ])->assertSessionHasNoErrors();

        $this->assertSame('canceled', $entry->fresh()->status);
        $this->assertTrue(JournalEntry::where('reversed_from', $entry->id)->where('status', 'posted')->exists());
        $this->assertEquals(0, $this->ledger('1101'));
        $this->assertEquals(0, $this->ledger('3001'));
        $this->assertEquals(0, (float) $this->treasury->fresh()->balance);
    }

    /** 8.9 */
    public function test_posted_entry_cannot_be_deleted_but_draft_can(): void
    {
        $this->entry([['1101', 250, 0], ['5004', 0, 250]]);
        $posted = $this->lastEntry();
        $this->post(route('finance.journal_entries.delete'), ['ids' => [$posted->id]])->assertStatus(422);
        $this->assertNotNull($posted->fresh());
        $this->assertEquals(250, $this->ledger('1101'));
        $this->assertEquals(250, (float) $this->treasury->fresh()->balance);

        $this->entry([['1101', 10, 0], ['5004', 0, 10]], 'draft');
        $draft = $this->lastEntry();
        $this->post(route('finance.journal_entries.delete'), ['id' => $draft->id])->assertOk();
        $this->assertNull($draft->fresh());
    }

    /** 8.7 — تعديل قيد يدوي مرحّل يبقى مسموحاً ويعيد الحساب */
    public function test_editing_posted_manual_entry_recalculates(): void
    {
        $this->entry([['5004', 300, 0], ['1101', 0, 300]]);
        $entry = $this->lastEntry();

        $this->post(route('finance.journal_entries.update'), [
            'id' => $entry->id, 'entry_type' => 'adjustment', 'date' => '2026-11-18', 'status' => 'posted',
            'items' => [['account_tree_id' => $this->acc('5004')->id, 'debit' => 250, 'credit' => 0], ['account_tree_id' => $this->acc('1101')->id, 'debit' => 0, 'credit' => 250]],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(250, $this->ledger('5004'));
        $this->assertEquals(-250, $this->ledger('1101'));
        $this->assertEquals(-250, (float) $this->treasury->fresh()->balance);
    }

    // ── 6.x شجرة الحسابات ────────────────────────────────────────────────

    /** 6.5 */
    public function test_account_tree_rejects_self_parent_and_cycles(): void
    {
        $parent = $this->acc('1100');
        $child  = $this->acc('1101');

        $this->post(route('finance.account_trees.update'), ['id' => $parent->id, 'name' => $parent->name, 'code' => '1100', 'type' => 'asset', 'parent_id' => $parent->id])
            ->assertSessionHasErrors('parent_id');
        $this->post(route('finance.account_trees.update'), ['id' => $parent->id, 'name' => $parent->name, 'code' => '1100', 'type' => 'asset', 'parent_id' => $child->id])
            ->assertSessionHasErrors('parent_id');

        $this->assertSame($this->acc('1000')->id, $parent->fresh()->parent_id);
    }

    /** 6.4 */
    public function test_account_tree_rejects_type_and_code_mismatch_with_parent(): void
    {
        $p = $this->acc('1100')->id;
        $this->post(route('finance.account_trees.store'), ['name' => 'خصم', 'code' => '1190', 'type' => 'liability', 'parent_id' => $p])->assertSessionHasErrors('type');
        $this->post(route('finance.account_trees.store'), ['name' => 'خطأ', 'code' => '9999', 'type' => 'asset', 'parent_id' => $p])->assertSessionHasErrors('code');

        $this->post(route('finance.account_trees.store'), ['name' => 'عهد', 'code' => '1190', 'type' => 'asset', 'parent_id' => $p, 'is_active' => 'on'])->assertSessionHasNoErrors();
        $new = $this->acc('1190');
        $this->assertSame(2, $new->level);
        $this->assertSame('debit', $new->account_type);

        $this->post(route('finance.account_trees.store'), ['name' => 'دائن', 'code' => '2190', 'type' => 'liability', 'parent_id' => $this->acc('2100')->id])->assertSessionHasNoErrors();
        $this->assertSame('credit', $this->acc('2190')->account_type);
    }

    /** 6.7 */
    public function test_account_with_children_or_entries_cannot_be_deleted(): void
    {
        $this->post(route('finance.account_trees.delete'), ['ids' => [$this->acc('1100')->id]])->assertStatus(422);
        $this->assertNotNull(AccountTree::where('code', '1100')->first());

        $this->entry([['5004', 10, 0], ['3001', 0, 10]]);
        $this->post(route('finance.account_trees.delete'), ['id' => $this->acc('5004')->id])->assertStatus(422);

        $leaf = $this->acc('5010');
        $this->post(route('finance.account_trees.delete'), ['id' => $leaf->id])->assertOk();
        $this->assertNull($leaf->fresh());
    }

    // ── 7.x الخزن والبنوك ────────────────────────────────────────────────

    /** 7.1 / 7.2 / 7.4 */
    public function test_treasury_and_bank_account_link_validation(): void
    {
        $this->post(route('finance.treasuries.store'), ['name' => 'بلا حساب', 'currency' => 'SAR'])->assertSessionHasErrors('account_tree_id');
        $this->post(route('finance.treasuries.store'), ['name' => 'مكررة', 'currency' => 'SAR', 'account_tree_id' => $this->acc('1101')->id])->assertSessionHasErrors('account_tree_id');
        $this->post(route('finance.treasuries.store'), ['name' => 'على بنك', 'currency' => 'SAR', 'account_tree_id' => $this->acc('1102')->id])->assertSessionHasErrors('account_tree_id');
        $this->post(route('finance.treasuries.store'), ['name' => 'على مخزون أب', 'currency' => 'SAR', 'account_tree_id' => $this->acc('1100')->id])->assertSessionHasErrors('account_tree_id');
        $this->post(route('finance.bank_accounts.store'), ['bank_id' => $this->bank->bank_id, 'account_number' => 'EG2', 'currency' => 'SAR'])->assertSessionHasErrors('account_tree_id');
        $this->post(route('finance.bank_accounts.store'), ['bank_id' => $this->bank->bank_id, 'account_number' => 'EG2', 'currency' => 'SAR', 'account_tree_id' => $this->acc('2101')->id])->assertSessionHasErrors('account_tree_id');

        $this->assertSame(1, Treasury::count());
        $this->assertSame(1, BankAccount::count());
    }

    /** 7.6 / 7.7 */
    public function test_manual_treasury_transactions_post_entries_and_block_overdraft(): void
    {
        $store = fn ($type, $amount) => $this->post(route('finance.treasury_transactions.store'), [
            'treasury_id' => $this->treasury->id, 'type' => $type, 'amount' => $amount, 'counter_account_id' => $this->acc('3001')->id, 'description' => 'QA',
        ]);

        $store('deposit', 1000)->assertSessionHasNoErrors();
        $store('withdraw', 200)->assertSessionHasNoErrors();
        $store('withdraw', 999999)->assertSessionHasErrors('amount');
        $store('withdraw', -5)->assertSessionHasErrors('amount');

        $this->assertEquals(800, (float) $this->treasury->fresh()->balance);
        $this->assertEquals(800, $this->ledger('1101'));
        $this->assertEquals(-800, $this->ledger('3001'));
        $this->assertSame(2, JournalEntry::whereIn('entry_type', ['cash_deposit', 'cash_withdraw'])->count());

        // تعديل الإيداع 1000 → 800 ثم حذفه (قيد عكسي)
        $deposit = TreasuryTransaction::where('type', 'deposit')->firstOrFail();
        $this->post(route('finance.treasury_transactions.update'), [
            'id' => $deposit->id, 'treasury_id' => $this->treasury->id, 'type' => 'deposit', 'amount' => 800, 'counter_account_id' => $this->acc('3001')->id,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(600, (float) $this->treasury->fresh()->balance);
        $this->assertEquals(600, $this->ledger('1101'));

        // حذف الإيداع يترك الرصيد سالباً (−200) → مرفوض
        $deposit = TreasuryTransaction::where('type', 'deposit')->latest('id')->firstOrFail();
        $this->post(route('finance.treasury_transactions.delete'), ['ids' => [$deposit->id]])->assertStatus(422);

        $withdraw = TreasuryTransaction::where('type', 'withdraw')->firstOrFail();
        $this->post(route('finance.treasury_transactions.delete'), ['ids' => [$withdraw->id]])->assertOk();
        $this->assertEquals(800, (float) $this->treasury->fresh()->balance);
        $this->assertEquals(800, $this->ledger('1101'));
    }

    /** 7.8 */
    public function test_manual_bank_transaction_posts_entry(): void
    {
        $this->post(route('finance.bank_transactions.store'), [
            'bank_account_id' => $this->bank->id, 'type' => 'deposit', 'amount' => 500, 'counter_account_id' => $this->acc('3001')->id,
        ])->assertSessionHasNoErrors();
        $this->post(route('finance.bank_transactions.store'), [
            'bank_account_id' => $this->bank->id, 'type' => 'withdraw', 'amount' => 600, 'counter_account_id' => $this->acc('5013')->id,
        ])->assertSessionHasErrors('amount');
        $this->post(route('finance.bank_transactions.store'), [
            'bank_account_id' => $this->bank->id, 'type' => 'withdraw', 'amount' => 50, 'counter_account_id' => $this->acc('1101')->id,
        ])->assertSessionHasErrors('counter_account_id');

        $this->assertEquals(500, (float) $this->bank->fresh()->balance);
        $this->assertEquals(500, $this->ledger('1102'));
    }

    /** يُمنع تعديل حركة خزنة ناتجة عن قيد/مستند من شاشة الحركات */
    public function test_document_generated_treasury_transaction_is_not_editable(): void
    {
        $this->entry([['1101', 100, 0], ['3001', 0, 100]]);
        $txn = TreasuryTransaction::firstOrFail();

        $this->post(route('finance.treasury_transactions.delete'), ['ids' => [$txn->id]])->assertStatus(422);
        $this->assertNotNull($txn->fresh());
    }

    // ── 9.x الأرصدة الافتتاحية ───────────────────────────────────────────

    /** 9.1 / 9.2 */
    public function test_opening_balances_generate_one_entry(): void
    {
        $add = fn ($code, $d, $c) => $this->post(route('finance.opening_balances.store'), ['account_id' => $this->acc($code)->id, 'debit' => $d, 'credit' => $c, 'date' => '2026-01-01']);

        $add('1101', 5000, 0)->assertSessionHasNoErrors()->assertSessionHas('warning');
        $entry = JournalEntry::where('reference_type', OpeningBalance::class)->firstOrFail();
        $this->assertSame('draft', $entry->status);
        $this->assertEquals(0, $this->ledger('1101'));

        $add('3001', 0, 5000)->assertSessionHasNoErrors()->assertSessionMissing('warning');
        $this->assertSame('posted', $entry->fresh()->status);
        $this->assertEquals(5000, $this->ledger('1101'));
        $this->assertEquals(-5000, $this->ledger('3001'));
        $this->assertEquals(5000, (float) $this->treasury->fresh()->balance);

        // 9.2: مدين ودائن معاً، حساب أب، تكرار نفس الحساب
        $add('1104', 100, 100)->assertSessionHasErrors('debit');
        $add('1100', 100, 0)->assertSessionHasErrors('account_id');
        $add('1101', 10, 0)->assertSessionHasErrors('account_id');

        $this->assertSame(1, JournalEntry::where('reference_type', OpeningBalance::class)->count());
        $this->assertSame(2, OpeningBalance::count());
    }

    // ── 10.x سيناريوهات القيود ───────────────────────────────────────────

    /** 10.2 */
    public function test_posting_scenario_rules_are_validated_on_save(): void
    {
        $base = ['code' => 'QA_ADJ', 'name' => 'تسوية', 'operation_type' => 'inventory_adjustment'];
        $r    = fn (array $o) => array_merge(['rule_group' => 1, 'amount_type' => 'fixed', 'amount_value' => 100], $o);
        $dr   = $this->acc('5012')->id;
        $cr   = $this->acc('1103')->id;

        $this->post(route('finance.posting_scenarios.store'), $base + ['rules' => []])->assertSessionHasErrors('rules');
        $this->post(route('finance.posting_scenarios.store'), $base + ['rules' => [$r([]), $r(['credit_account_id' => $cr])]])->assertSessionHasErrors('rules.0');
        $this->post(route('finance.posting_scenarios.store'), $base + ['rules' => [$r(['debit_account_id' => $dr, 'credit_account_id' => $cr])]])->assertSessionHasErrors('rules.0');
        $this->post(route('finance.posting_scenarios.store'), $base + ['rules' => [$r(['debit_account_id' => $dr, 'amount_type' => 'percentage', 'amount_value' => 150]), $r(['credit_account_id' => $cr, 'amount_type' => 'total'])]])->assertSessionHasErrors('rules.0.amount_value');
        $this->post(route('finance.posting_scenarios.store'), $base + ['rules' => [$r(['debit_account_id' => $dr, 'amount_type' => 'formula', 'formula' => 'system("id")']), $r(['credit_account_id' => $cr])]])->assertSessionHasErrors('rules.0.formula');
        $this->post(route('finance.posting_scenarios.store'), $base + ['rules' => [$r(['debit_account_id' => $dr]), $r(['credit_account_id' => $cr, 'amount_value' => 90])]])->assertSessionHasErrors('rules');
        $this->post(route('finance.posting_scenarios.store'), $base + ['rules' => [$r(['debit_account_id' => $dr])]])->assertSessionHasErrors('rules');
        $this->assertSame(0, PostingScenario::where('code', 'QA_ADJ')->count());

        $this->post(route('finance.posting_scenarios.store'), $base + ['rules' => [
            $r(['debit_account_id' => $dr, 'amount_type' => 'formula', 'formula' => 'total - paid']),
            $r(['credit_account_id' => $cr, 'amount_type' => 'total']),
        ]])->assertSessionHasNoErrors();
        $this->assertSame(1, PostingScenario::where('code', 'QA_ADJ')->count());
    }

    // ── 6.1 / 6.2 + تصحيح البيانات ───────────────────────────────────────

    /** 6.1 / 6.2 / 8.2 / 8.4 / 8.8 / 8.9: migration تصحح البيانات القديمة */
    public function test_migration_fixes_account_types_missing_accounts_and_old_entries(): void
    {
        // الحالة القديمة: كل الحسابات مدين، والحسابات الجديدة غير موجودة
        DB::table('account_trees')->update(['account_type' => 'debit']);
        AccountTree::whereIn('code', ['1110', '1204', '2104', '4005', '5012', '5015'])->delete();

        $mk = function (string $number, string $status, array $lines) {
            $id = DB::table('journal_entries')->insertGetId(['entry_number' => $number, 'entry_type' => 'adjustment', 'date' => '2026-11-17', 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
            foreach ($lines as [$code, $d, $c]) {
                $itemId = DB::table('journal_entry_items')->insertGetId(['journal_entry_id' => $id, 'account_tree_id' => $this->acc($code)->id, 'debit' => $d, 'credit' => $c]);
                if ($code === '1101') {
                    DB::table('treasury_transactions')->insert(['treasury_id' => $this->treasury->id, 'type' => $d > 0 ? 'deposit' : 'withdraw', 'amount' => abs($d - $c), 'reference_type' => JournalEntryItem::class, 'reference_id' => $itemId]);
                }
            }
            return $id;
        };
        $mk('OLD-1', 'posted', [['1101', 10000, 0], ['3001', 0, 10000]]);
        $mk('OLD-2', 'posted', [['1101', 100, 0], ['3001', 0, 50]]);      // غير متوازن
        $canceled = $mk('OLD-6', 'canceled', [['5004', 100, 0], ['3001', 0, 100]]); // ملغى بلا عكس
        $mk('OLD-7', 'draft', [['1101', 500, 0], ['3001', 0, 500]]);      // مسودة أثّرت في الخزنة
        DB::table('account_trees')->where('code', '1101')->update(['balance' => 10850, 'total_debit' => 10850]);
        DB::table('account_trees')->where('code', '5004')->update(['balance' => 360, 'total_debit' => 360]); // + قيد محذوف 260
        DB::table('treasuries')->update(['balance' => 10600]);

        $this->runMigrationUp('2026_11_18_000003_journal_treasury_qa_batch3_fixes.php');

        $this->assertSame('credit', $this->acc('2101')->account_type);
        $this->assertSame('credit', $this->acc('4001')->account_type);
        $this->assertSame('credit', $this->acc('3001')->account_type);
        $this->assertSame('debit', $this->acc('5004')->account_type);
        foreach (['1110', '1204', '2104', '4005', '5012', '5015'] as $code) {
            $this->assertNotNull(AccountTree::where('code', $code)->first(), "الحساب {$code} غير موجود");
        }
        $this->assertSame('credit', $this->acc('1204')->account_type);

        $this->assertSame('draft', JournalEntry::where('entry_number', 'OLD-2')->value('status'));
        $this->assertTrue(JournalEntry::where('reversed_from', $canceled)->where('status', 'posted')->exists());

        $this->assertEquals(10000, $this->ledger('1101'));
        $this->assertEquals(0, $this->ledger('5004'));
        $this->assertEquals(-10000, $this->ledger('3001'));
        $this->assertEquals(10000, (float) $this->treasury->fresh()->balance);

        $tb = DB::table('account_trees')->selectRaw('SUM(total_debit) - SUM(total_credit) as d')->value('d');
        $this->assertEquals(0, (float) $tb, 'ميزان المراجعة غير متوازن');
    }
}
