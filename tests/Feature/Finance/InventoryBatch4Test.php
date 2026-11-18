<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerWallet;
use App\Models\Finance\InventoryItem;
use App\Models\Finance\InventoryTransaction;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\Supplier;
use App\Models\Finance\SupplierWallet;
use App\Models\Finance\Unit;
use App\Models\Finance\UnitConversion;
use App\Models\Finance\Warehouse;
use App\Models\Finance\WarehouseTransaction;
use Illuminate\Support\Facades\DB;

/**
 * QA Batch 4 — المنتجات والمخزون والمحافظ (سيناريوهات 2.x، 4.x، 5.x في ERP_Finance_QA_Report.xlsx)
 */
class InventoryBatch4Test extends FinanceTestCase
{
    private Warehouse $wh;
    private Warehouse $wh2;
    private Product $pen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wh  = Warehouse::create(['name' => 'رئيسي', 'is_active' => true]);
        $this->wh2 = Warehouse::create(['name' => 'فرع', 'is_active' => true]);
        $this->pen = Product::create(['name' => 'قلم', 'sku' => 'PEN', 'is_active' => true, 'has_variants' => false]);
    }

    private function move(array $o)
    {
        return $this->post(route('finance.warehouse-transactions.store'), array_merge([
            'warehouse_id' => $this->wh->id, 'product_id' => $this->pen->id, 'type' => 'in', 'quantity' => 100,
            'unit_cost' => 5, 'counter_account_id' => $this->acc('3001')->id,
        ], $o));
    }

    private function adjust(array $o)
    {
        return $this->post(route('finance.inventory.store-adjust'), array_merge([
            'warehouse_id' => $this->wh->id, 'product_id' => $this->pen->id, 'type' => 'in', 'quantity' => 100,
            'unit_cost' => 5, 'counter_account_id' => $this->acc('3001')->id,
        ], $o));
    }

    private function qty(?Warehouse $wh = null, ?Product $p = null, ?int $variant = null): float
    {
        return (float) InventoryItem::where('warehouse_id', ($wh ?? $this->wh)->id)->where('product_id', ($p ?? $this->pen)->id)
            ->where('variant_id', $variant)->value('quantity');
    }

    // ── 5.2 رصيد أول المدة: تكلفة + الجدولان + قيد ─────────────────────────

    /** 5.2 */
    public function test_opening_stock_writes_both_logs_cost_and_entry(): void
    {
        $this->adjust([])->assertSessionHasNoErrors();

        $this->assertEquals(100, $this->qty());
        $this->assertEquals(5, (float) $this->pen->fresh()->average_cost);
        $this->assertSame(1, InventoryTransaction::count());
        $this->assertSame(1, WarehouseTransaction::count());
        $this->assertEquals(500, $this->ledger('1103'));
        $this->assertEquals(-500, $this->ledger('3001'));
    }

    /** 5.2 — متوسط التكلفة المرجّح عبر الحركات */
    public function test_weighted_average_cost_updates_on_in(): void
    {
        $this->move([]);                                        // 100 @ 5
        $this->move(['quantity' => 50, 'unit_cost' => 8]);      // 50 @ 8 → (500+400)/150 = 6
        $this->assertEquals(6, (float) $this->pen->fresh()->average_cost);

        $this->move(['quantity' => 10, 'unit_cost' => 0]);      // بلا تكلفة → بالمتوسط 6 ولا يتغير
        $this->assertEquals(6, (float) $this->pen->fresh()->average_cost);
        $this->assertEquals(960, $this->ledger('1103'));
    }

    // ── 5.5 / 5.11 الصادر والتعديل والوحدات ─────────────────────────────

    /** 5.5 */
    public function test_out_greater_than_available_is_rejected(): void
    {
        $this->adjust([]);
        $this->adjust(['type' => 'out', 'quantity' => 500])->assertSessionHasErrors('quantity');
        $this->move(['type' => 'out', 'quantity' => 9999])->assertSessionHasErrors('quantity');

        $this->assertEquals(100, $this->qty());
        $this->assertEquals(500, $this->ledger('1103'));
    }

    /** 5.11 (أ) التعديل = تحديد الكمية النهائية */
    public function test_adjustment_sets_final_quantity(): void
    {
        $this->move([]);                                                        // 100 @ 5
        $this->move(['type' => 'adjustment', 'quantity' => 80, 'counter_account_id' => $this->acc('5012')->id])->assertSessionHasNoErrors();
        $this->assertEquals(80, $this->qty());
        $this->assertEquals(400, $this->ledger('1103'));
        $this->assertEquals(100, $this->ledger('5012'));                        // عجز جرد 20 × 5

        $this->move(['type' => 'adjustment', 'quantity' => 95, 'unit_cost' => 0, 'counter_account_id' => $this->acc('5012')->id])->assertSessionHasNoErrors();
        $this->assertEquals(95, $this->qty());
        $this->assertEquals(475, $this->ledger('1103'));

        $this->move(['type' => 'adjustment', 'quantity' => 0, 'counter_account_id' => $this->acc('5012')->id])->assertSessionHasNoErrors();
        $this->assertEquals(0, $this->qty());
        $this->assertEquals(0, $this->ledger('1103'));
    }

    /** 5.11 (ب) الوحدة تُحوَّل لوحدة الأساس */
    public function test_unit_conversion_is_applied(): void
    {
        $piece = Unit::create(['name' => 'حبة', 'is_active' => 1]);
        $box   = Unit::create(['name' => 'علبة', 'is_active' => 1]);
        $med   = Product::create(['name' => 'دواء', 'sku' => 'MED', 'is_active' => true, 'has_variants' => false, 'unit_id' => $piece->id]);
        $conv  = UnitConversion::create(['product_id' => $med->id, 'base_unit' => $piece->id, 'target_unit' => $box->id, 'conversion_rate' => 20, 'is_default' => 1]);

        $this->move(['product_id' => $med->id, 'quantity' => 3, 'unit_cost' => 40, 'unit_conversion_id' => $conv->id])->assertSessionHasNoErrors();

        $this->assertEquals(60, $this->qty(null, $med));
        $this->assertEquals(2, (float) $med->fresh()->average_cost);
        $this->assertEquals(120, $this->ledger('1103'));
    }

    /** 5.3 */
    public function test_variant_required_for_product_with_variants(): void
    {
        $shirt = Product::create(['name' => 'تيشرت', 'sku' => 'TS', 'is_active' => true, 'has_variants' => true]);
        $red   = ProductVariant::create(['product_id' => $shirt->id, 'sku' => 'TS-R', 'is_active' => true, 'attributes' => ['1' => 'أحمر']]);
        $other = ProductVariant::create(['product_id' => $this->pen->id, 'sku' => 'X', 'is_active' => true]);

        $this->adjust(['product_id' => $shirt->id])->assertSessionHasErrors('variant_id');
        $this->adjust(['product_id' => $shirt->id, 'variant_id' => $other->id])->assertSessionHasErrors('variant_id');
        $this->adjust(['product_id' => $shirt->id, 'variant_id' => $red->id, 'quantity' => 10, 'unit_cost' => 40])->assertSessionHasNoErrors();

        $this->assertEquals(10, $this->qty(null, $shirt, $red->id));
        $this->assertEquals(40, (float) $red->fresh()->average_cost);
        $this->assertFalse(InventoryItem::where('product_id', $shirt->id)->whereNull('variant_id')->exists());
    }

    /** حذف الحركة اليدوية = حركة وقيد معاكسان */
    public function test_reversing_manual_movement_restores_stock_and_ledger(): void
    {
        $this->move([]);
        $wt = WarehouseTransaction::firstOrFail();

        $this->post(route('finance.warehouse-transactions.delete'), ['id' => $wt->id])->assertSessionHasNoErrors();

        $this->assertEquals(0, $this->qty());
        $this->assertEquals(0, $this->ledger('1103'));
        $this->post(route('finance.warehouse-transactions.delete'), ['id' => $wt->id])->assertSessionHasErrors('id');
    }

    // ── 5.9 التحويل بتكلفة ───────────────────────────────────────────────

    /** 5.9 */
    public function test_transfer_carries_average_cost_without_changing_ledger(): void
    {
        $this->move([]);
        $this->post(route('finance.inventory.transfers.store'), [
            'from_warehouse_id' => $this->wh->id, 'to_warehouse_id' => $this->wh2->id, 'product_id' => $this->pen->id, 'quantity' => 20,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(80, $this->qty());
        $this->assertEquals(20, $this->qty($this->wh2));
        $this->assertSame(0, InventoryTransaction::where('notes', 'like', 'تحويل%')->where('unit_cost', 0)->count());
        $this->assertEquals(5, (float) InventoryTransaction::where('notes', 'like', 'تحويل%')->value('unit_cost'));
        $this->assertEquals(500, $this->ledger('1103'));
        $this->assertEquals(5, (float) $this->pen->fresh()->average_cost);
    }

    // ── 2.x المنتجات والمتغيرات والتسعير ─────────────────────────────────

    /** 2.8 / 2.9 */
    public function test_product_saves_cost_price_average_cost_and_calories(): void
    {
        $this->post(route('finance.products.store'), [
            'sku' => 'PIZ', 'name' => 'بيتزا', 'purchase_price' => 30, 'selling_price' => 60,
            'cost_price' => 30, 'average_cost' => 30, 'calories' => 850,
        ])->assertSessionHasNoErrors();

        $pizza = Product::where('sku', 'PIZ')->firstOrFail();
        $this->assertEquals(30, (float) $pizza->cost_price);
        $this->assertEquals(30, (float) $pizza->average_cost);
        $this->assertEquals(850, (float) $pizza->calories);

        // بعد وجود مخزون لا يُقبل متوسط التكلفة من النموذج
        $this->move(['product_id' => $pizza->id, 'quantity' => 10, 'unit_cost' => 30]);
        $this->post(route('finance.products.update'), [
            'id' => $pizza->id, 'sku' => 'PIZ', 'name' => 'بيتزا', 'purchase_price' => 30, 'selling_price' => 60, 'average_cost' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(30, (float) $pizza->fresh()->average_cost);
    }

    /** 2.11 / 2.12 */
    public function test_variant_rules(): void
    {
        $shirt = Product::create(['name' => 'تيشرت', 'sku' => 'TS', 'is_active' => true, 'has_variants' => true]);
        $store = fn ($product, $sku, $attrs) => $this->post(route('finance.products.variants.store', $product->id), [
            'sku' => $sku, 'purchase_price' => 20, 'selling_price' => 40,
            'kt_docs_repeater_basic' => array_map(fn ($k, $v) => ['attri_keys' => $k, 'attri_values' => $v], array_keys($attrs), $attrs),
        ]);

        $store($shirt, 'TS-R-M', ['2' => 'أحمر', '3' => 'M'])->assertSessionHasNoErrors();
        $store($shirt, 'TS-R-M2', ['3' => 'm', '2' => 'أحمر'])->assertSessionHasErrors('attributes');   // نفس التركيبة
        $store($shirt, 'TS-NO', [])->assertSessionHasErrors('attributes');                              // بلا خصائص
        $store($this->pen, 'PEN-R', ['2' => 'أحمر'])->assertSessionHasErrors('attributes');            // منتج بلا متغيرات

        $this->assertSame(1, ProductVariant::count());
    }

    /** 5.14 */
    public function test_price_rules(): void
    {
        $base = ['product_id' => $this->pen->id, 'price' => 10, 'priority' => 0];
        $this->post(route('finance.variant-prices.store'), $base + ['sale_price' => 20])->assertSessionHasErrors('sale_price');
        $this->post(route('finance.variant-prices.store'), $base + ['tier_qty' => [-5], 'tier_price' => [8]])->assertSessionHasErrors('tier_qty.0');
        $this->post(route('finance.variant-prices.store'), $base + ['sale_price' => 8, 'tier_qty' => [10], 'tier_price' => [9]])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('product_variant_prices')->count());
    }

    // ── 4.x المحافظ اليدوية ───────────────────────────────────────────────

    /** 4.4 / 4.3 */
    public function test_manual_customer_wallet_posts_entry(): void
    {
        $c = Customer::create(['customer_code' => 'C1', 'name' => 'عميل', 'phone' => '0101', 'join_date' => '2026-01-01', 'account_status' => 'active']);
        $url = fn ($r) => route('finance.customers.wallet.' . $r, $c->id);
        $row = ['customer_id' => $c->id, 'date' => '2026-01-01', 'description' => 'رصيد أول المدة', 'counter_account_id' => $this->acc('3001')->id];

        $this->post($url('store'), $row + ['debit' => 300, 'credit' => 0])->assertSessionHasNoErrors();
        $this->post($url('store'), $row + ['debit' => 100, 'credit' => 100])->assertSessionHasErrors('debit');
        $this->post($url('store'), array_merge($row, ['debit' => 50, 'credit' => 0, 'counter_account_id' => $this->acc('1104')->id]))->assertSessionHasErrors('counter_account_id');

        $this->assertEquals(300, (float) $c->fresh()->wallet_balance);
        $this->assertEquals(300, $this->ledger('1104'));

        $w = CustomerWallet::firstOrFail();
        $this->post($url('update'), $row + ['id' => $w->id, 'debit' => 0, 'credit' => 200])->assertSessionHasNoErrors();
        $this->assertEquals(-200, (float) $c->fresh()->wallet_balance);
        $this->assertEquals(-200, $this->ledger('1104'));

        $this->post($url('delete'), ['id' => [$w->id]])->assertOk();
        $this->assertEquals(0, (float) $c->fresh()->wallet_balance);
        $this->assertEquals(0, $this->ledger('1104'));
    }

    /** 4.6 / 4.7 */
    public function test_manual_supplier_wallet_posts_entry(): void
    {
        $s = Supplier::create(['company_name' => 'مورد', 'name' => 'مورد', 'account_status' => 'active']);
        $row = ['supplier_id' => $s->id, 'date' => '2026-01-01', 'counter_account_id' => $this->acc('3001')->id];

        $this->post(route('finance.suppliers.wallet.store', $s->id), $row + ['debit' => 0, 'credit' => 1000])->assertSessionHasNoErrors();
        $this->post(route('finance.suppliers.wallet.store', $s->id), $row + ['debit' => 200, 'credit' => 0])->assertSessionHasNoErrors();

        $this->assertEquals(800, (float) $s->fresh()->wallet_balance);
        $this->assertEquals(-800, $this->ledger('2101'));       // دائن 800 = محفظة المورد
    }

    /** حركة محفظة من مستند لا تُعدَّل من شاشة المحفظة */
    public function test_document_wallet_rows_are_locked(): void
    {
        $locked = (new SupplierWallet())->forceFill(['id' => 1, 'supplier_id' => 1, 'purchase_id' => 999]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\Finance\WalletJournalService::class)->assertManual($locked);
    }

    // ── migration ─────────────────────────────────────────────────────────

    /** 5.2 / 5.10 / 4.4 / 4.7 — تصحيح البيانات القديمة */
    public function test_migration_unifies_logs_replays_cost_and_posts_differences(): void
    {
        // الحالة القديمة: رصيد افتتاحي من شاشة المخزون (سجل المخزون فقط، متوسط 0، بلا قيد)
        InventoryItem::create(['warehouse_id' => $this->wh->id, 'product_id' => $this->pen->id, 'quantity' => 100]);
        DB::table('inventory_transactions')->insert(['warehouse_id' => $this->wh->id, 'product_id' => $this->pen->id, 'type' => 'in', 'quantity' => 100, 'unit_cost' => 5, 'created_at' => now(), 'updated_at' => now()]);
        // حركة من شاشة حركات المستودع (سجل المستودع فقط)
        DB::table('warehouse_transactions')->insert(['warehouse_id' => $this->wh2->id, 'product_id' => $this->pen->id, 'type' => 'in', 'quantity' => 10, 'unit_cost' => 0, 'balance_after' => 10, 'created_at' => now()->addMinute(), 'updated_at' => now()]);
        InventoryItem::create(['warehouse_id' => $this->wh2->id, 'product_id' => $this->pen->id, 'quantity' => 10]);
        // محافظ يدوية بلا قيد
        $c = Customer::create(['customer_code' => 'C1', 'name' => 'عميل', 'phone' => '0101', 'join_date' => '2026-01-01', 'account_status' => 'active']);
        CustomerWallet::create(['customer_id' => $c->id, 'date' => '2026-01-01', 'debit' => 0, 'credit' => 500, 'balance' => 0]);
        $s = Supplier::create(['company_name' => 'مورد', 'name' => 'مورد', 'account_status' => 'active']);
        SupplierWallet::create(['supplier_id' => $s->id, 'date' => '2026-01-01', 'debit' => 0, 'credit' => 1000, 'balance' => 0, 'previous_balance' => 0]);

        $this->runMigrationUp('2026_11_18_000004_inventory_qa_batch4_fixes.php');

        $this->assertSame(2, InventoryTransaction::count());
        $this->assertSame(2, WarehouseTransaction::count());
        $this->assertEquals(5, (float) $this->pen->fresh()->average_cost);
        $this->assertEquals(550, $this->ledger('1103'));          // 110 × 5
        $this->assertEquals(-500, $this->ledger('1104'));
        $this->assertEquals(-1000, $this->ledger('2101'));
        $this->assertEquals(-550 + 500 + 1000, $this->ledger('3001'));

        // تشغيل ثانٍ لا يكرر شيئاً
        $before = JournalEntry::count();
        $this->runMigrationUp('2026_11_18_000004_inventory_qa_batch4_fixes.php');
        $this->assertSame($before, JournalEntry::count());
        $this->assertSame(2, WarehouseTransaction::count());
    }
}
