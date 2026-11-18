<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\AccountTree;
use App\Models\Finance\Bank;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankTransaction;
use App\Models\Finance\Category;
use App\Models\Finance\Coupon;
use App\Models\Finance\CouponUsage;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerWallet;
use App\Models\Finance\InventoryItem;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\Offer;
use App\Models\Finance\Order;
use App\Models\Finance\PostingScenario;
use App\Models\Finance\Product;
use App\Models\Finance\Purchase;
use App\Models\Finance\Supplier;
use App\Models\Finance\SupplierWallet;
use App\Models\Finance\Treasury;
use App\Models\Finance\Unit;
use App\Models\Finance\UnitConversion;
use App\Models\Finance\Warehouse;
use App\Models\Finance\WarehouseTransaction;
use Illuminate\Support\Facades\DB;

/**
 * QA Batch 6 — المبيعات (العروض، الكوبونات، طلبات البيع) + القرارات المعلّقة 1-3
 * سيناريوهات «إعادة اختبار الدفعة 6» في ERP_Finance_QA_Report.xlsx
 */
class SalesBatch6Test extends FinanceTestCase
{
    private Customer $customer;
    private Warehouse $warehouse;
    private Product $pen;   // 10 ر.س، تكلفة 6، ضريبة 15%
    private Product $med;   // حبة بتكلفة 2، تُباع بالعلبة (20 حبة)
    private Category $cat;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['CASH', 'CREDIT', 'INSTALLMENTS', 'WALLET', 'BANK_ONLINE', 'BANK_DIRECT', 'CHECK'] as $t) {
            PostingScenario::create(['code' => "ORDER_$t", 'name' => "بيع $t", 'operation_type' => 'order', 'is_active' => 1, 'priority' => 10]);
            PostingScenario::create(['code' => "ORDER_RETURN_$t", 'name' => "مرتجع $t", 'operation_type' => 'sales_return', 'is_active' => 1, 'priority' => 10]);
        }
        AccountTree::firstOrCreate(['code' => '3001'], ['name' => 'رأس المال', 'type' => 'equity', 'account_type' => 'credit', 'level' => 1, 'is_active' => 1]);
        $this->runMigrationUp('2026_11_18_000006_sales_qa_batch6_fixes.php');

        Treasury::create(['name' => 'خزنة', 'account_tree_id' => $this->acc('1101')->id, 'currency' => 'SAR', 'balance' => 0, 'is_active' => true]);
        $this->customer  = Customer::create(['customer_code' => 'C1', 'name' => 'عميل', 'phone' => '0101', 'join_date' => '2026-01-01', 'account_status' => 'active']);
        $this->warehouse = Warehouse::create(['name' => 'رئيسي', 'is_active' => true]);
        $this->cat       = Category::create(['name' => 'مكتبية', 'code' => 'OFF', 'sort' => 1, 'is_active' => 1]);

        $this->pen = Product::create(['name' => 'قلم', 'sku' => 'PEN', 'is_active' => true, 'category_id' => $this->cat->id, 'average_cost' => 6, 'tax_rate' => 15]);
        InventoryItem::create(['warehouse_id' => $this->warehouse->id, 'product_id' => $this->pen->id, 'quantity' => 100]);

        $pill  = Unit::create(['name' => 'حبة', 'code' => 'PILL']);
        $box   = Unit::create(['name' => 'علبة', 'code' => 'BOX']);
        $strip = Unit::create(['name' => 'شريط', 'code' => 'STRIP']);
        $this->med = Product::create(['name' => 'دواء', 'sku' => 'MED', 'is_active' => true, 'unit_id' => $pill->id, 'average_cost' => 2]);
        InventoryItem::create(['warehouse_id' => $this->warehouse->id, 'product_id' => $this->med->id, 'quantity' => 100]);
        UnitConversion::create(['product_id' => $this->med->id, 'base_unit' => $box->id, 'target_unit' => $pill->id, 'conversion_rate' => 20, 'decimal_places' => 0]);
        UnitConversion::create(['product_id' => $this->med->id, 'base_unit' => $box->id, 'target_unit' => $strip->id, 'conversion_rate' => 2, 'decimal_places' => 0]);
    }

    // ---------------------------------------------------------------- helpers

    private function order(array $override = [], ?array $items = null)
    {
        return $this->post(route('finance.orders.store'), array_merge([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date'         => '2026-11-18',
            'payment_type' => 'cash',
            'status'       => 'confirmed',
            'shipping_cost'=> 0,
            'paid'         => 0,
            'items'        => $items ?? [['product_id' => $this->pen->id, 'quantity' => 5, 'unit_price' => 10, 'unit_cost' => 0, 'discount' => 0, 'tax_rate' => 15]],
        ], $override));
    }

    private function lastOrder(): Order
    {
        return Order::latest('id')->firstOrFail();
    }

    /** بنود القيود المرحّلة للطلب: [كود الحساب => مدين − دائن] */
    private function postedLines(Order $order): array
    {
        return DB::table('journal_entry_items as i')
            ->join('journal_entries as j', 'j.id', '=', 'i.journal_entry_id')
            ->join('account_trees as a', 'a.id', '=', 'i.account_tree_id')
            ->where('j.reference_type', Order::class)->where('j.reference_id', $order->id)
            ->where('j.status', '!=', 'draft')
            ->groupBy('a.code')
            ->selectRaw('a.code, ROUND(SUM(i.debit - i.credit), 2) as net')
            ->pluck('net', 'code')->map(fn ($v) => (float) $v)->filter(fn ($v) => abs($v) > 0.001)->all();
    }

    private function stock(Product $p): float
    {
        return (float) InventoryItem::where('product_id', $p->id)->where('warehouse_id', $this->warehouse->id)->value('quantity');
    }

    private function walletNet(): float
    {
        return round((float) CustomerWallet::where('customer_id', $this->customer->id)->sum(DB::raw('debit - credit')), 2);
    }

    private function coupon(array $a = []): Coupon
    {
        return Coupon::create(array_merge(['code' => 'C' . uniqid(), 'type' => 'fixed', 'value' => 10, 'used_count' => 0,
            'start_date' => '2026-11-18', 'end_date' => '2026-12-31', 'is_active' => 1], $a));
    }

    private function offer(array $a = [], array $items = []): Offer
    {
        $o = Offer::create(array_merge(['name' => 'عرض ' . uniqid(), 'type' => 'percentage', 'applies_to' => 'order', 'value' => 10,
            'start_date' => '2026-11-18', 'end_date' => '2026-12-31', 'is_active' => 1, 'is_stackable' => 1, 'priority' => 0, 'used_count' => 0], $a));
        foreach ($items as [$type, $id]) {
            DB::table('offer_items')->insert(['offer_id' => $o->id, 'item_type' => $type, 'item_id' => $id]);
        }

        return $o;
    }

    private function applyOffer(Offer $offer, array $items, array $extra = []): array
    {
        return $this->getJson(route('finance.orders.ajax.apply_offer') . '?' . http_build_query(array_merge(
            ['offer_id' => $offer->id, 'items' => $items, 'customer_id' => $this->customer->id, 'date' => '2026-11-18'], $extra)))->json();
    }

    // ================================================================ طلبات البيع

    /** 13.1 — آجل بلا مدفوع: كان 500 «قيد غير متوازن» والطلب يبقى مؤكداً بلا أي أثر */
    public function test_credit_sale_posts_receivable_stock_and_wallet(): void
    {
        $this->order(['payment_type' => 'credit'])->assertRedirect(route('finance.orders.index'));
        $o = $this->lastOrder();

        $this->assertEquals(['1104' => 57.5, '4001' => -50.0, '2102' => -7.5, '5001' => 30.0, '1103' => -30.0], $this->postedLines($o));
        $this->assertEquals(95, $this->stock($this->pen));
        $this->assertEquals(57.5, $this->walletNet());
        $this->assertEquals($this->walletNet(), $this->ledger('1104'));
    }

    /** 13.2 / 13.3 — آجل بعربون، وشيك على أوراق القبض */
    public function test_partial_credit_and_check_sales_balance(): void
    {
        $this->order(['payment_type' => 'credit', 'paid' => 20]);
        $this->assertEquals(['1101' => 20.0, '1104' => 37.5, '4001' => -50.0, '2102' => -7.5, '5001' => 30.0, '1103' => -30.0], $this->postedLines($this->lastOrder()));

        $this->order(['payment_type' => 'check', 'paid' => 57.5]);
        $this->assertEquals(['1109' => 57.5, '4001' => -50.0, '2102' => -7.5, '5001' => 30.0, '1103' => -30.0], $this->postedLines($this->lastOrder()));

        $this->order(['payment_type' => 'installments'])->assertSessionHasNoErrors();
        $this->assertSame('installments', $this->lastOrder()->payment_type);
    }

    /** 13.4 — خصم البند: كان الخصم يُطرح من الإيراد ثم يُرحَّل مديناً مرة ثانية (قيد غير متوازن) */
    public function test_item_discount_is_posted_once(): void
    {
        $this->order(['paid' => 51.75], [['product_id' => $this->pen->id, 'quantity' => 5, 'unit_price' => 10, 'discount' => 5, 'tax_rate' => 15]])
            ->assertSessionHasNoErrors();
        $this->assertEquals(['1101' => 51.75, '5008' => 5.0, '4001' => -50.0, '2102' => -6.75, '5001' => 30.0, '1103' => -30.0], $this->postedLines($this->lastOrder()));
    }

    /** 13.5 — مدفوع أكبر من الإجمالي يُرفض ولا يُحفظ شيء */
    public function test_overpayment_is_rejected_atomically(): void
    {
        $this->order(['paid' => 100])->assertSessionHasErrors('paid');
        $this->assertSame(0, Order::count());
        $this->assertEquals(100, $this->stock($this->pen));
    }

    /** 13.6 — البيع بوحدة تحويل: التكلفة = الكمية × 20 × متوسط التكلفة، وحركة المستودع بالتكلفة لا بسعر البيع */
    public function test_unit_conversion_sale_costs_base_quantity(): void
    {
        $boxToPill = UnitConversion::where('product_id', $this->med->id)->where('conversion_rate', 20)->first();
        $this->order(['paid' => 80], [['product_id' => $this->med->id, 'unit_conversion_id' => $boxToPill->id, 'quantity' => 2, 'unit_price' => 40, 'unit_cost' => 2, 'tax_rate' => 0]])
            ->assertSessionHasNoErrors();
        $o = $this->lastOrder();

        $this->assertEquals(60, $this->stock($this->med));
        $this->assertEquals(80.0, $this->postedLines($o)['5001']);       // كانت 2 × 2 = 4
        $this->assertEquals(40.0, (float) $o->items->first()->unit_cost); // تكلفة العلبة
        $this->assertEquals(2.0, (float) WarehouseTransaction::where('reference_id', $o->id)->value('unit_cost'));
    }

    /** 13.7 — تحويل بنكي مع حساب بنكي: المحصّل على حساب البنك ويظهر في حركاته */
    public function test_bank_sale_posts_to_selected_bank_account(): void
    {
        $bank = BankAccount::create(['bank_id' => Bank::create(['name' => 'بنك'])->id, 'account_tree_id' => $this->acc('1102')->id, 'account_number' => 'EG1', 'currency' => 'SAR', 'balance' => 0]);
        $this->order(['payment_type' => 'bank_online', 'bank_account_id' => $bank->id, 'paid' => 57.5])->assertSessionHasNoErrors();

        $this->assertEquals(57.5, $this->postedLines($this->lastOrder())['1102']);
        $this->assertEquals(57.5, (float) BankTransaction::where('bank_account_id', $bank->id)->sum('amount'));
    }

    /** 13.8 — تغيير الحالة بين الحالات الفعّالة لا يغيّر الرقم ولا يعكس الآثار؛ الإلغاء يعكس كل شيء */
    public function test_status_changes_keep_number_and_cancel_reverses_everything(): void
    {
        $c = $this->coupon(['value' => 5]);
        $this->order(['paid' => 51.75, 'coupon_id' => $c->id]);
        $o = $this->lastOrder();
        $number = $o->order_number;
        $entries = JournalEntry::count();

        $payload = fn ($status) => [
            'id' => $o->id, 'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id, 'date' => '2026-11-18',
            'payment_type' => 'cash', 'status' => $status, 'paid' => 51.75, 'coupon_id' => $c->id,
            'items' => [['product_id' => $this->pen->id, 'quantity' => 5, 'unit_price' => 10, 'tax_rate' => 15]],
        ];

        $this->post(route('finance.orders.update'), $payload('processing'))->assertSessionHasNoErrors();
        $this->assertSame($number, $o->fresh()->order_number);        // كان يتغير لرقم جديد
        $this->assertSame($entries, JournalEntry::count());           // لا عكس ولا إعادة ترحيل
        $this->assertEquals(95, $this->stock($this->pen));            // كان يُعاد المخزون وتبقى الإيرادات

        $this->post(route('finance.orders.update'), $payload('cancelled'))->assertSessionHasNoErrors();
        $this->assertSame([], $this->postedLines($o));
        $this->assertEquals(100, $this->stock($this->pen));
        $this->assertEquals(0, $this->walletNet());
        $this->assertSame(0, (int) $c->fresh()->used_count);
        $this->assertEquals(0, $this->ledger('4001'));
        $this->assertEquals(0, $this->ledger('1101'));
    }

    /** 13.9 — حذف طلب مؤكد يعكس قيد المبيعات أيضاً (كان يعكس قيد التكلفة فقط وتبقى النقدية والإيراد) */
    public function test_delete_reverses_sales_and_cost_entries(): void
    {
        $c = $this->coupon(['value' => 5]);
        $this->order(['paid' => 51.75, 'coupon_id' => $c->id]);
        $this->assertSame(1, (int) $c->fresh()->used_count);

        $this->post(route('finance.orders.delete'), ['id' => $this->lastOrder()->id]);

        $this->assertSame(0, Order::count());
        $this->assertEquals(0, $this->ledger('1101'));
        $this->assertEquals(0, $this->ledger('4001'));
        $this->assertEquals(0, $this->ledger('1103'));
        $this->assertSame(0, (int) $c->fresh()->used_count);
        $this->assertSame(0, CouponUsage::count());
    }

    /**
     * 15.15 — قرار المالك: خصم الكوبون/العرض قبل الضريبة.
     * قلم 50 (ضريبة 15%) + دواء 40 (0%) وكوبون 18 ← يُوزَّع 10 / 8، الضريبة = 40 × 15% = 6 (كانت 7.50)؛
     * الشحن المجاني يخص الشحن ولا يخفض وعاء الضريبة.
     */
    public function test_vat_is_calculated_after_coupon_and_offer(): void
    {
        $coupon = $this->coupon(['value' => 18]);
        $free   = $this->offer(['type' => 'free_shipping', 'value' => null, 'min_amount' => 50]);

        $this->order(['coupon_id' => $coupon->id, 'offer_id' => $free->id, 'shipping_cost' => 15, 'paid' => 78], [
            ['product_id' => $this->pen->id, 'quantity' => 5, 'unit_price' => 10, 'tax_rate' => 15],
            ['product_id' => $this->med->id, 'quantity' => 10, 'unit_price' => 4, 'tax_rate' => 0],
        ])->assertSessionHasNoErrors();
        $o = $this->lastOrder();

        $this->assertEquals(6.0, (float) $o->tax);
        $this->assertEquals(78.0, (float) $o->total);
        $this->assertEquals([46.0, 32.0], $o->items->sortBy('id')->pluck('total')->map(fn ($v) => (float) $v)->values()->all());
        $this->assertEquals(
            ['1101' => 78.0, '5009' => 18.0, '5010' => 15.0, '4001' => -90.0, '4004' => -15.0, '2102' => -6.0, '5001' => 50.0, '1103' => -50.0],
            $this->postedLines($o)
        );

        // الشاشة تعرض نفس الضريبة: ajax العرض يُعلِم أنه خصم شحن
        $res = $this->applyOffer($free, [['product_id' => $this->pen->id, 'quantity' => 5, 'unit_price' => 10, 'tax_rate' => 15]], ['shipping_cost' => 15]);
        $this->assertTrue($res['on_shipping']);
    }

    // ================================================================ الكوبونات

    /** 15.x — خصم الكوبون يُحسب على الخادم: لا خصم بلا كوبون، ولا خصم يتجاوز الطلب، والحدود تُفرض */
    public function test_coupon_discount_is_computed_and_limited_on_server(): void
    {
        $this->order(['paid' => 7.5, 'coupon_discount' => 50]);                 // تلاعب بلا كوبون
        $this->assertEquals(0, (float) $this->lastOrder()->coupon_discount);
        $this->assertEquals(57.5, (float) $this->lastOrder()->total);

        $big = $this->coupon(['type' => 'percentage', 'value' => 100, 'max_discount' => null]);
        $this->order(['coupon_id' => $big->id, 'coupon_discount' => 999]);
        $o = $this->lastOrder();
        $this->assertEquals(50, (float) $o->coupon_discount);                 // لا يتجاوز صافي البنود
        $this->assertGreaterThanOrEqual(0, (float) $o->total);

        $perUser = $this->coupon(['usage_per_user' => 1, 'value' => 2]);
        $this->order(['coupon_id' => $perUser->id])->assertSessionHasNoErrors();
        $this->order(['coupon_id' => $perUser->id])->assertSessionHasErrors('coupon_id');

        $once = $this->coupon(['usage_limit' => 1, 'value' => 2]);
        $this->order(['coupon_id' => $once->id, 'status' => 'draft'])->assertSessionHasNoErrors();
        $this->order(['coupon_id' => $once->id])->assertSessionHasErrors('coupon_id');
        $this->assertSame(1, (int) $once->fresh()->used_count);
    }

    /** 15.x — كوبون يبدأ اليوم صالح (كان «خارج نطاق التاريخ») ونسبة > 100% تُرفض عند الإنشاء */
    public function test_coupon_dates_and_validation(): void
    {
        $c = $this->coupon(['code' => 'TODAY', 'start_date' => now()->toDateString()]);
        $res = $this->getJson(route('finance.orders.ajax.coupon') . '?code=today&subtotal=100&customer_id=' . $this->customer->id)->json();
        $this->assertTrue($res['valid'], $res['message']);

        $this->post(route('finance.coupons.store'), ['code' => 'BIG', 'type' => 'percentage', 'value' => 120, 'start_date' => '2026-11-18', 'end_date' => '2026-12-31'])
            ->assertSessionHasErrors('value');

        $this->order(['coupon_id' => $c->id]);
        $this->post(route('finance.coupons.delete'), ['id' => $c->id])->assertSessionHas('error');
        $this->assertNotNull(Coupon::find($c->id));
    }

    // ================================================================ العروض

    /** 14.x — حساب كل أنواع العروض */
    public function test_offer_types_compute_correct_discounts(): void
    {
        $pen  = fn ($q) => ['product_id' => $this->pen->id, 'quantity' => $q, 'unit_price' => 10, 'discount' => 0, 'tax_rate' => 15];
        $med  = ['product_id' => $this->med->id, 'quantity' => 10, 'unit_price' => 4, 'discount' => 0, 'tax_rate' => 0]; // 40
        $mix  = [$pen(3), $med]; // 30 + 40 = 70

        $cases = [
            'نسبة على منتج فقط'   => [$this->offer(['applies_to' => 'product', 'value' => 10], [['product', $this->pen->id]]), $mix, 3.0],
            'خصم سعر المنتج'      => [$this->offer(['type' => 'product_price_discount', 'applies_to' => 'product', 'value' => 2], [['product', $this->pen->id]]), $mix, 6.0],
            'متدرج'               => [$this->offer(['type' => 'tiered', 'value' => null, 'tier_thresholds' => [['min_amount' => 50, 'discount_percentage' => 5], ['min_amount' => 100, 'discount_percentage' => 10]]]), $mix, 3.5],
            'حزمة 3 بـ 25'        => [$this->offer(['type' => 'bundle', 'applies_to' => 'product', 'value' => 25, 'buy_quantity' => 3], [['product', $this->pen->id]]), [$pen(6)], 10.0],
            'أول طلب 15%'         => [$this->offer(['type' => 'first_order', 'value' => 15]), $mix, 10.5],
            'فلاش 5 وحدات 20%'    => [$this->offer(['type' => 'flash', 'applies_to' => 'product', 'value' => null, 'flash_quantity' => 5, 'discount_percentage' => 20], [['product', $this->pen->id]]), [$pen(6)], 10.0],
            'اشتري 2 واحصل على 1' => [$this->offer(['type' => 'buy_x_get_y', 'applies_to' => 'product', 'value' => null, 'buy_quantity' => 2, 'get_quantity' => 1], [['product', $this->pen->id]]), [$pen(6)], 20.0],
            'فئة: اشتري 3 خصم 10%'=> [$this->offer(['type' => 'buy_x_get_discount', 'applies_to' => 'category', 'value' => null, 'buy_quantity' => 3, 'discount_percentage' => 10], [['category', $this->cat->id]]), $mix, 3.0],
        ];

        foreach ($cases as $label => [$offer, $items, $expected]) {
            $res = $this->applyOffer($offer, $items);
            $this->assertTrue($res['valid'] ?? false, $label . ': ' . ($res['message'] ?? ''));
            $this->assertEquals($expected, (float) $res['discount'], $label);
        }

        $free = $this->offer(['type' => 'free_shipping', 'value' => null, 'min_amount' => 50]);
        $this->assertEquals(15.0, (float) $this->applyOffer($free, $mix, ['shipping_cost' => 15])['discount']);
    }

    /** 14.2 / 14.3 — الحد الأقصى للاستخدام والتواريخ تُفرض عند الحفظ (كانت تُفحص في الشاشة فقط، ويوم البدء مرفوض) */
    public function test_offer_limits_and_dates_are_enforced_on_save(): void
    {
        $once = $this->offer(['value' => 50, 'max_uses' => 1, 'start_date' => now()->toDateString()]);
        $this->order(['offer_id' => $once->id, 'offer_discount' => 25])->assertSessionHasNoErrors();
        $this->assertEquals(25, (float) $this->lastOrder()->offer_discount);
        $this->order(['offer_id' => $once->id, 'offer_discount' => 25])->assertSessionHasErrors('offer_id');
        $this->assertSame(1, (int) $once->fresh()->used_count);

        $expired = $this->offer(['start_date' => '2026-11-01', 'end_date' => '2026-11-10']);
        $this->order(['offer_id' => $expired->id, 'offer_discount' => 5])->assertSessionHasErrors('offer_id');

        $future = $this->offer(['start_date' => '2026-11-20']);
        $this->order(['offer_id' => $future->id])->assertSessionHasErrors('offer_id');

        // عرض غير قابل للجمع مع كوبون
        $solo = $this->offer(['is_stackable' => 0]);
        $this->order(['offer_id' => $solo->id, 'coupon_id' => $this->coupon()->id])->assertSessionHasErrors('offer_id');

        // الخصم في القيد على 5010
        $this->assertEquals(25.0, $this->postedLines(Order::where('offer_id', $once->id)->first())['5010']);

        $this->post(route('finance.offers.delete'), ['id' => $once->id])->assertSessionHas('error');
    }

    /** 14.1 — تحقق الإنشاء لكل نوع */
    public function test_offer_request_validates_each_type(): void
    {
        $base = ['start_date' => '2026-11-18', 'end_date' => '2026-12-31', 'is_active' => 1];
        $this->post(route('finance.offers.store'), $base + ['name' => 'نسبة 150', 'type' => 'percentage', 'applies_to' => 'order', 'value' => 150])->assertSessionHasErrors('value');
        $this->post(route('finance.offers.store'), $base + ['name' => 'منتج بلا منتجات', 'type' => 'percentage', 'applies_to' => 'product', 'value' => 10])->assertSessionHasErrors('item_ids');
        $this->post(route('finance.offers.store'), $base + ['name' => 'متدرج فارغ', 'type' => 'tiered', 'applies_to' => 'order', 'tier_thresholds' => '[]'])->assertSessionHasErrors('tier_thresholds');
        $this->post(route('finance.offers.store'), $base + ['name' => 'فلاش بلا خصم', 'type' => 'flash', 'applies_to' => 'order', 'flash_quantity' => 5])->assertSessionHasErrors('discount_percentage');
        $this->post(route('finance.offers.store'), $base + ['name' => 'صحيح', 'type' => 'percentage', 'applies_to' => 'order', 'value' => 10])->assertSessionHasNoErrors();
    }

    // ================================================================ القرارات المعلّقة

    /** قرار 3 — الوحدات: «علبة (= 20 حبة)» وتحويلات لا تنتهي بوحدة المخزون لا تُعرض ولا تُقبل */
    public function test_unit_dropdown_labels_and_stock_unit_only(): void
    {
        // الاتجاه الآخر (الأساسية = وحدة المنتج) يُعرض بنفس الصيغة
        $carton = Unit::create(['name' => 'كرتون', 'code' => 'CTN']);
        UnitConversion::create(['product_id' => $this->med->id, 'base_unit' => $this->med->unit_id, 'target_unit' => $carton->id, 'conversion_rate' => 100, 'decimal_places' => 0]);

        $res = $this->getJson(route('finance.orders.ajax.units') . '?product_id=' . $this->med->id)->json('results');
        $this->assertCount(2, $res);
        $this->assertSame(['علبة (= 20 حبة)', 'كرتون (= 100 حبة)'], array_column($res, 'text'));

        $boxToStrip = UnitConversion::where('product_id', $this->med->id)->where('conversion_rate', 2)->first();
        $this->order([], [['product_id' => $this->med->id, 'unit_conversion_id' => $boxToStrip->id, 'quantity' => 1, 'unit_price' => 4, 'tax_rate' => 0]])
            ->assertSessionHasErrors('items.0');
    }

    /** قرار 2 / 7.2 — الخزنة على حساب نقدية فقط */
    public function test_treasury_requires_cash_account(): void
    {
        $this->post(route('finance.treasuries.store'), ['name' => 'على المخزون', 'currency' => 'SAR', 'account_tree_id' => $this->acc('1103')->id])
            ->assertSessionHasErrors('account_tree_id');
        $petty = AccountTree::create(['name' => 'عهدة نقدية', 'code' => '1113', 'parent_id' => $this->acc('1100')->id, 'type' => 'asset', 'account_type' => 'debit', 'level' => 2, 'is_active' => 1, 'is_cash' => 1]);
        $this->post(route('finance.treasuries.store'), ['name' => 'العهدة', 'currency' => 'SAR', 'account_tree_id' => $petty->id])
            ->assertSessionHasNoErrors();
    }

    /** قرار 1 + إصلاح الطلبات القديمة عبر migration */
    public function test_migration_reconciles_suppliers_and_repairs_orders(): void
    {
        $supplier = Supplier::create(['company_name' => 'مورد', 'name' => 'مورد', 'account_status' => 'active']);
        // فاتورة قديمة: مدفوع 20 من 57.5 لكن القيد رحّل 57.5 نقداً
        $p = Purchase::create(['purchase_number' => 'OLD-1', 'supplier_id' => $supplier->id, 'warehouse_id' => $this->warehouse->id, 'date' => '2026-11-01',
            'payment_type' => 'cash', 'status' => 'received', 'subtotal' => 50, 'tax' => 7.5, 'total' => 57.5, 'paid' => 20]);
        SupplierWallet::create(['supplier_id' => $supplier->id, 'purchase_id' => $p->id, 'debit' => 0, 'credit' => 57.5, 'balance' => 57.5, 'date' => '2026-11-01']);
        SupplierWallet::create(['supplier_id' => $supplier->id, 'purchase_id' => $p->id, 'debit' => 20, 'credit' => 0, 'balance' => 37.5, 'date' => '2026-11-01']);
        $je = JournalEntry::create(['entry_number' => 'PRC-OLD-1', 'entry_type' => 'purchase', 'date' => '2026-11-01', 'reference_type' => Purchase::class, 'reference_id' => $p->id, 'status' => 'posted']);
        foreach ([['1103', 50, 0], ['2102', 7.5, 0], ['1101', 0, 57.5]] as [$code, $dr, $cr]) {
            $je->items()->create(['account_tree_id' => $this->acc($code)->id, 'debit' => $dr, 'credit' => $cr]);
        }
        app(\App\Services\Finance\ManualJournalService::class)->recalculateAccounts([$this->acc('1103')->id, $this->acc('2102')->id, $this->acc('1101')->id]);

        // طلب قديم حُفظ «مؤكداً» بلا أي أثر (فشل القيد في الكود القديم)
        $o = Order::create(['order_number' => 'SO-OLD', 'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id, 'date' => '2026-11-10',
            'payment_type' => 'credit', 'status' => 'confirmed', 'subtotal' => 50, 'tax' => 7.5, 'total' => 57.5, 'paid' => 0]);
        $o->items()->create(['product_id' => $this->pen->id, 'quantity' => 5, 'unit_price' => 10, 'tax_rate' => 15, 'total' => 57.5]);

        $this->runMigrationUp('2026_11_18_000006_sales_qa_batch6_fixes.php');
        $this->runMigrationUp('2026_11_18_000006_sales_qa_batch6_fixes.php'); // لا يتكرر

        $this->assertEquals(-37.5, $this->ledger('2101'));       // دائن 37.5 = المحفظة
        $this->assertEquals(-20.0, $this->ledger('1101'));       // المدفوع الفعلي 20 فقط
        $this->assertEquals(['1104' => 57.5, '4001' => -50.0, '2102' => -7.5, '5001' => 30.0, '1103' => -30.0], $this->postedLines($o));
        $this->assertEquals(95, $this->stock($this->pen));
        $this->assertEquals(57.5, $this->walletNet());
    }
}
