<?php

namespace Tests\Feature\Finance;

use App\Models\Admin;
use App\Models\Finance\AccountTree;
use App\Models\Finance\Bank;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankTransaction;
use App\Models\Finance\InventoryItem;
use App\Models\Finance\InventoryTransaction;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryItem;
use App\Models\Finance\PostingRule;
use App\Models\Finance\PostingScenario;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\Purchase;
use App\Models\Finance\Supplier;
use App\Models\Finance\SupplierWallet;
use App\Models\Finance\Treasury;
use App\Models\Finance\TreasuryTransaction;
use App\Models\Finance\Unit;
use App\Models\Finance\UnitConversion;
use App\Models\Finance\Warehouse;
use Database\Seeders\ClothesShop\AccountTreeSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

/**
 * QA Batch 1 — المشتريات (سيناريوهات 12.x في ERP_Finance_QA_Report.xlsx)
 */
class PurchaseBatch1Test extends TestCase
{
    use DatabaseTransactions;

    private static bool $migrated = false;

    private Supplier $supplier;
    private Warehouse $warehouse;
    private Product $pen;
    private Treasury $treasury;

    /**
     * قاعدة اختبار منفصلة (erp_testing): مجلد migrations الأساسي ثم finance بنفس ترتيب الإنتاج
     */
    protected function setUpTraits()
    {
        if (! static::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            Artisan::call('migrate', ['--path' => 'database/migrations/finance', '--force' => true]);
            static::$migrated = true;
        }

        return parent::setUpTraits();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->seed(AccountTreeSeeder::class);

        foreach (['CASH', 'CREDIT', 'INSTALLMENTS', 'WALLET', 'BANK_ONLINE', 'BANK_DIRECT', 'CHECK'] as $t) {
            PostingScenario::create(['code' => "PURCHASE_$t", 'name' => "شراء $t", 'operation_type' => 'purchase', 'is_active' => 1, 'priority' => 10]);
        }
        foreach (['CASH', 'CHECK'] as $t) {
            PostingScenario::create(['code' => "PURCHASE_RETURN_$t", 'name' => "مرتجع $t", 'operation_type' => 'purchase_return', 'is_active' => 1, 'priority' => 10]);
        }

        // تطبيق migration الإصلاح على البيانات المبذورة (قواعد + حساب 2105 + تعطيل المرتجعات)
        (require base_path('database/migrations/finance/2026_11_18_000001_purchase_qa_batch1_fixes.php'))->up();

        $admin = Admin::create(['name' => 'QA', 'email' => 'qa@test.local', 'phone' => '0100', 'password' => bcrypt('x'), 'is_active' => '1']);
        $this->actingAs($admin, 'admin');

        $this->supplier  = Supplier::create(['company_name' => 'مورد', 'name' => 'مورد', 'account_status' => 'active']);
        $this->warehouse = Warehouse::create(['name' => 'رئيسي', 'is_active' => true]);
        $this->pen       = Product::create(['name' => 'قلم', 'sku' => 'PEN', 'is_active' => true, 'has_variants' => false]);
        $this->treasury  = Treasury::create(['name' => 'خزنة', 'account_tree_id' => $this->acc('1101')->id, 'currency' => 'SAR', 'balance' => 0, 'is_active' => true]);
    }

    // ---------------------------------------------------------------- helpers

    private function acc(string $code): AccountTree
    {
        return AccountTree::where('code', $code)->firstOrFail();
    }

    private function payload(array $override = [], ?array $items = null): array
    {
        return array_merge([
            'supplier_id'  => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'date'         => '2026-11-18',
            'payment_type' => 'cash',
            'status'       => 'received',
            'paid'         => 57.5,
            'items'        => $items ?? [[
                'product_id' => $this->pen->id,
                'variant_id' => '',
                'quantity'   => 10,
                'unit_cost'  => 5,
                'discount'   => 0,
                'tax_rate'   => 15,
            ]],
        ], $override);
    }

    private function store(array $override = [], ?array $items = null)
    {
        return $this->post(route('finance.purchases.store'), $this->payload($override, $items));
    }

    private function stock(?int $variantId = null, ?Product $p = null): float
    {
        return (float) InventoryItem::where('product_id', ($p ?? $this->pen)->id)
            ->where('variant_id', $variantId)->sum('quantity');
    }

    /** مجموع (مدين − دائن) لحساب من القيود المرحّلة */
    /**
     * رصيد الحساب (مدين − دائن) كما في شجرة الحسابات، مع التحقق من مطابقته لمجموع بنود القيود غير المسودة
     */
    private function ledger(string $code): float
    {
        $account = $this->acc($code);
        $fromItems = round((float) JournalEntryItem::whereHas('entry', fn ($q) => $q->where('status', '!=', 'draft'))
            ->where('account_tree_id', $account->id)
            ->selectRaw('COALESCE(SUM(debit - credit),0) as b')->value('b'), 2);

        $this->assertEquals($fromItems, (float) $account->balance, "رصيد الشجرة {$code} لا يطابق بنود القيود");

        return (float) $account->balance;
    }

    private function activeEntries(Purchase $p)
    {
        return JournalEntry::where('reference_type', Purchase::class)->where('reference_id', $p->id)
            ->where('status', 'posted')->where('is_reversed', false)->get();
    }

    // ---------------------------------------------------------------- 12.5

    public function test_12_5_partial_cash_payment_posts_paid_to_cash_and_rest_to_suppliers(): void
    {
        $this->store(['paid' => 20])->assertRedirect(route('finance.purchases.index'));

        $p = Purchase::firstOrFail();
        $this->assertCount(1, $this->activeEntries($p));
        $this->assertSame(50.0, $this->ledger('1103'));
        $this->assertSame(7.5, $this->ledger('2102'));
        $this->assertSame(-20.0, $this->ledger('1101'));
        $this->assertSame(-37.5, $this->ledger('2101'));

        // محفظة المورد = ذمم المورد في الأستاذ
        $this->assertEquals(37.5, (float) $this->supplier->fresh()->wallet_balance);
        $this->assertEquals(20, (float) TreasuryTransaction::where('treasury_id', $this->treasury->id)->where('type', 'withdraw')->sum('amount'));
    }

    public function test_12_5_credit_with_partial_payment_uses_paid(): void
    {
        $this->store(['payment_type' => 'credit', 'paid' => 20]);
        $this->assertSame(-20.0, $this->ledger('1101'));
        $this->assertSame(-37.5, $this->ledger('2101'));
    }

    public function test_12_5_paid_greater_than_total_is_rejected(): void
    {
        $this->store(['paid' => 999])->assertSessionHasErrors('paid');
        $this->assertSame(0, Purchase::count());
    }

    // ---------------------------------------------------------------- 12.4

    public function test_12_4_check_purchase_posts_balanced_entry_on_2105(): void
    {
        $this->store(['payment_type' => 'check', 'paid' => 57.5]);
        $p = Purchase::firstOrFail();
        $this->assertCount(1, $this->activeEntries($p));
        $this->assertSame(-57.5, $this->ledger('2105'));
        $this->assertSame(0.0, $this->ledger('2101'));
    }

    public function test_12_4_installments_with_discount_debits_net_subtotal_once(): void
    {
        $items = [['product_id' => $this->pen->id, 'quantity' => 10, 'unit_cost' => 5, 'discount' => 10, 'tax_rate' => 15]];
        $this->store(['payment_type' => 'installments', 'paid' => 0], $items);

        $p = Purchase::firstOrFail();
        $this->assertEquals(40, (float) $p->subtotal);
        $this->assertEquals(46, (float) $p->total);
        $this->assertCount(1, $this->activeEntries($p));
        $this->assertSame(40.0, $this->ledger('1103'));
        $this->assertSame(-46.0, $this->ledger('2101'));
    }

    public function test_12_4_failed_posting_rolls_back_stock_and_wallet(): void
    {
        // كسر قاعدة الدفع النقدي لتصبح القيود غير متوازنة
        $scenario = PostingScenario::where('code', 'PURCHASE_CASH')->first();
        PostingRule::where('scenario_id', $scenario->id)->where('sort_order', 4)->delete();
        Cache::flush();

        $this->store(['paid' => 20])->assertSessionHas('error');

        $this->assertSame(0, Purchase::count());
        $this->assertSame(0.0, $this->stock());
        $this->assertSame(0, SupplierWallet::count());
        $this->assertSame(0, JournalEntry::count());
    }

    // ---------------------------------------------------------------- 12.6

    public function test_12_6_cancelled_or_pending_on_create_has_no_effects_until_received(): void
    {
        $this->store(['status' => 'cancelled']);
        $this->store(['status' => 'pending', 'purchase_number' => 'PO-PEND']);

        $this->assertSame(2, Purchase::count());
        $this->assertSame(0.0, $this->stock());
        $this->assertSame(0, SupplierWallet::count());
        $this->assertSame(0, JournalEntry::count());

        // التحويل لاحقاً إلى received يطبّق الآثار
        $p = Purchase::where('purchase_number', 'PO-PEND')->first();
        $this->post(route('finance.purchases.update'), $this->payload(['id' => $p->id, 'status' => 'received', 'purchase_number' => 'PO-PEND']))
            ->assertRedirect(route('finance.purchases.index'));

        $this->assertSame(10.0, $this->stock());
        $this->assertCount(1, $this->activeEntries($p));
        $this->assertSame(50.0, $this->ledger('1103'));
    }

    // ---------------------------------------------------------------- 12.14 / 12.16

    public function test_12_14_and_12_16_resaving_or_deleting_cancelled_invoice_does_not_touch_stock(): void
    {
        $this->store();
        $p = Purchase::firstOrFail();
        $this->assertSame(10.0, $this->stock());

        $cancel = $this->payload(['id' => $p->id, 'status' => 'cancelled', 'purchase_number' => $p->purchase_number]);
        $this->post(route('finance.purchases.update'), $cancel);
        $this->assertSame(0.0, $this->stock());
        $this->assertSame(0.0, $this->ledger('1103'));

        // إعادة حفظ الملغاة مرتين
        $this->post(route('finance.purchases.update'), $cancel);
        $this->post(route('finance.purchases.update'), $cancel);

        // مخزون آخر قائم يجب ألا يُخصم
        $this->store(['purchase_number' => 'PO-OTHER']);
        $this->assertSame(10.0, $this->stock());

        $this->post(route('finance.purchases.update'), $cancel);
        $this->post(route('finance.purchases.delete'), ['id' => $p->id]);

        $this->assertSame(10.0, $this->stock());
        $this->assertSame(50.0, $this->ledger('1103'));
        $this->assertEquals(0, (float) $this->supplier->fresh()->wallet_balance);
    }

    public function test_editing_twice_keeps_a_single_active_entry(): void
    {
        $this->store();
        $p = Purchase::firstOrFail();

        foreach ([8, 12] as $qty) {
            $items = [['product_id' => $this->pen->id, 'quantity' => $qty, 'unit_cost' => 5, 'discount' => 0, 'tax_rate' => 15]];
            $this->post(route('finance.purchases.update'), $this->payload(['id' => $p->id, 'paid' => 0], $items));
        }

        $this->assertCount(1, $this->activeEntries($p));
        $this->assertSame(12.0, $this->stock());
        $this->assertSame(60.0, $this->ledger('1103'));
        $this->assertSame(-69.0, $this->ledger('2101'));
        $this->assertSame(0.0, $this->ledger('1101'));
    }

    // ---------------------------------------------------------------- 12.7 / 12.10

    public function test_12_7_unit_cost_is_converted_to_base_unit_and_average_spans_warehouses(): void
    {
        $piece = Unit::create(['name' => 'حبة', 'is_active' => 1]);
        $box   = Unit::create(['name' => 'علبة', 'is_active' => 1]);
        $med   = Product::create(['name' => 'دواء', 'sku' => 'MED', 'is_active' => true, 'has_variants' => false, 'unit_id' => $piece->id]);
        $conv  = UnitConversion::create(['product_id' => $med->id, 'base_unit' => $piece->id, 'target_unit' => $box->id, 'conversion_rate' => 20, 'is_default' => 1]);
        $wh2   = Warehouse::create(['name' => 'فرع', 'is_active' => true]);

        // 3 علب × 40 = 120 → 60 حبة بتكلفة 2 للحبة
        $this->store(['paid' => 0, 'payment_type' => 'credit'], [['product_id' => $med->id, 'unit_conversion_id' => $conv->id, 'quantity' => 3, 'unit_cost' => 40, 'tax_rate' => 0]]);
        $this->assertSame(60.0, $this->stock(null, $med));
        $this->assertEquals(2, (float) InventoryTransaction::where('product_id', $med->id)->value('unit_cost'));
        $this->assertEquals(2, (float) $med->fresh()->average_cost);

        // 40 حبة بتكلفة 4.5 في مستودع آخر → (60×2 + 40×4.5)/100 = 3
        $this->store(['warehouse_id' => $wh2->id, 'paid' => 0, 'payment_type' => 'credit', 'purchase_number' => 'PO-W2'],
            [['product_id' => $med->id, 'quantity' => 40, 'unit_cost' => 4.5, 'tax_rate' => 0]]);
        $this->assertEquals(3, (float) $med->fresh()->average_cost);

        // حذف الفاتورة الثانية يعيد المتوسط إلى 2 (نفس المنطق، والملغاة لا تُحسب)
        $this->post(route('finance.purchases.delete'), ['id' => Purchase::where('purchase_number', 'PO-W2')->value('id')]);
        $this->assertEquals(2, (float) $med->fresh()->average_cost);
        $this->assertSame(60.0, $this->stock(null, $med));
    }

    // ---------------------------------------------------------------- 12.7 (1.6) / 12.3 / 12.9 / 12.12

    public function test_variant_is_required_for_product_with_variants(): void
    {
        $shirt = Product::create(['name' => 'قميص', 'sku' => 'SH', 'is_active' => true, 'has_variants' => true]);
        $red   = ProductVariant::create(['product_id' => $shirt->id, 'sku' => 'SH-R', 'is_active' => true]);

        $this->store([], [['product_id' => $shirt->id, 'quantity' => 1, 'unit_cost' => 5]])
            ->assertSessionHasErrors('items.0.variant_id');

        $this->store(['paid' => 5.75], [['product_id' => $shirt->id, 'variant_id' => $red->id, 'quantity' => 1, 'unit_cost' => 5, 'tax_rate' => 15]])
            ->assertSessionHasNoErrors();
        $this->assertSame(1.0, $this->stock($red->id, $shirt));
    }

    public function test_12_3_item_without_variant_key_is_accepted(): void
    {
        $this->store([], [['product_id' => $this->pen->id, 'quantity' => 10, 'unit_cost' => 5, 'tax_rate' => 15]])
            ->assertSessionHasNoErrors()->assertRedirect(route('finance.purchases.index'));
    }

    public function test_12_9_inactive_warehouse_is_rejected(): void
    {
        $inactive = Warehouse::create(['name' => 'موقوف', 'is_active' => false]);
        $this->store(['warehouse_id' => $inactive->id])->assertSessionHasErrors('warehouse_id');
    }

    public function test_12_12_purchase_number_kept_when_not_sent_on_update(): void
    {
        $this->store(['purchase_number' => 'PO-0002']);
        $p = Purchase::firstOrFail();
        $data = $this->payload(['id' => $p->id]);
        unset($data['purchase_number']);
        $this->post(route('finance.purchases.update'), $data);
        $this->assertSame('PO-0002', $p->fresh()->purchase_number);
    }

    // ---------------------------------------------------------------- 12.17

    public function test_12_17_bank_payment_with_bank_account_appears_in_bank_transactions(): void
    {
        $bank = Bank::create(['name' => 'بنك']);
        $ba   = BankAccount::create(['bank_id' => $bank->id, 'account_tree_id' => $this->acc('1102')->id, 'account_number' => 'EG1', 'currency' => 'SAR', 'balance' => 0]);

        $this->store(['payment_type' => 'bank_online', 'bank_account_id' => $ba->id, 'paid' => 57.5]);

        $this->assertSame(-57.5, $this->ledger('1102'));
        $this->assertSame(0.0, $this->ledger('1107'));
        $this->assertEquals(57.5, (float) BankTransaction::where('bank_account_id', $ba->id)->where('type', 'withdraw')->sum('amount'));
    }

    // ---------------------------------------------------------------- 12.18

    public function test_12_18_purchase_return_scenarios_are_disabled_and_have_accounts(): void
    {
        $this->assertSame(0, PostingScenario::where('code', 'like', 'PURCHASE_RETURN_%')->where('is_active', true)->count());
        $this->assertTrue(AccountTree::where('code', '2105')->exists());
    }

    // ---------------------------------------------------------------- 12.12 (generateNumber)

    public function test_generate_number_does_not_hang_when_last_row_plus_one_is_taken(): void
    {
        // آخر صف PO-0025 بينما PO-0026 محجوز لفاتورة أقدم (وضع قاعدة erp بعد خطأ 12.12 القديم)
        $this->store(['purchase_number' => 'PO-0026']);
        $this->store(['purchase_number' => 'PO-0025']);

        $this->assertSame('PO-0027', Purchase::generateNumber());
    }

    // ---------------------------------------------------------------- 12.19

    public function test_12_19_excel_import_applies_stock_wallet_and_entry(): void
    {
        $path = storage_path('framework/testing_purchase_import.xlsx');
        @mkdir(dirname($path), 0777, true);
        (new FastExcel(collect([[
            'المورد' => 'مورد', 'المستودع' => 'رئيسي', 'التاريخ' => '2026-11-18', 'نوع الدفع' => 'نقدي',
            'رمز المنتج' => 'PEN', 'الكمية' => 10, 'سعر الوحدة' => 5, 'الخصم' => 0, 'نسبة الضريبة' => 15, 'الملاحظات' => '',
        ]])))->export($path);

        $file = new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $this->post(route('finance.purchases.import'), ['file' => $file])->assertSessionHas('success');

        $p = Purchase::firstOrFail();
        $this->assertSame('received', $p->status);
        $this->assertEquals(57.5, (float) $p->paid);
        $this->assertSame(10.0, $this->stock());
        $this->assertCount(1, $this->activeEntries($p));
        $this->assertSame(-57.5, $this->ledger('1101'));
        @unlink($path);
    }

    public function test_12_19_csv_import_is_read_and_product_with_variants_needs_variant_sku(): void
    {
        $shirt = Product::create(['name' => 'قميص', 'sku' => 'SH', 'is_active' => true, 'has_variants' => true]);
        $red   = ProductVariant::create(['product_id' => $shirt->id, 'sku' => 'SH-R', 'is_active' => true]);

        $csv = "المورد,المستودع,التاريخ,نوع الدفع,رمز المنتج,الكمية,سعر الوحدة,الخصم,نسبة الضريبة,الملاحظات\r\n"
             . "مورد,رئيسي,2026-11-18,آجل,SH-R,2,20,0,0,\r\n"
             . "مورد,رئيسي,2026-11-18,آجل,SH,1,20,0,0,\r\n";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $this->post(route('finance.purchases.import'), ['file' => $file])->assertSessionHas('warning');

        $this->assertSame(1, Purchase::count());
        $this->assertSame(2.0, $this->stock($red->id, $shirt));
        $this->assertSame(-40.0, $this->ledger('2101'));
    }
}
