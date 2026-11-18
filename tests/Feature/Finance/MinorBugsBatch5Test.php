<?php

namespace Tests\Feature\Finance;

use App\Models\Admin;
use App\Models\Finance\Attribute;
use App\Models\Finance\Brand;
use App\Models\Finance\Category;
use App\Models\Finance\InventoryItem;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\Treasury;
use App\Models\Finance\TreasuryTransaction;
use App\Models\Finance\Unit;
use App\Models\Finance\UnitConversion;
use App\Models\Finance\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * QA Batch 5 — أخطاء بسيطة (سيناريوهات 1.x، 2.x، 3.x، 7.5 في ERP_Finance_QA_Report.xlsx)
 */
class MinorBugsBatch5Test extends FinanceTestCase
{
    /** 2.14 */
    public function test_units_and_attributes_can_be_deleted_unless_used(): void
    {
        $free  = Unit::create(['name' => 'حرة', 'code' => 'FREE']);
        $used  = Unit::create(['name' => 'حبة', 'code' => 'PILL']);
        Product::create(['name' => 'دواء', 'sku' => 'MED', 'is_active' => true, 'unit_id' => $used->id]);

        $this->post(route('finance.units.delete'), ['id' => [$free->id]])->assertOk();
        $this->assertNull(Unit::find($free->id));
        $this->post(route('finance.units.delete'), ['id' => $used->id])->assertStatus(422);
        $this->assertNotNull(Unit::find($used->id));

        $color = Attribute::create(['name' => 'اللون', 'code' => 'COLOR']);
        $size  = Attribute::create(['name' => 'المقاس', 'code' => 'SIZE']);
        $shirt = Product::create(['name' => 'تيشرت', 'sku' => 'TS', 'is_active' => true, 'has_variants' => true]);
        ProductVariant::create(['product_id' => $shirt->id, 'sku' => 'TS-R', 'attributes' => [(string) $color->id => 'أحمر']]);

        $this->post(route('finance.attributes.delete'), ['ids' => [$color->id]])->assertStatus(422);
        $this->post(route('finance.attributes.delete'), ['ids' => [$size->id]])->assertOk();
        $this->assertNotNull(Attribute::find($color->id));
        $this->assertNull(Attribute::find($size->id));
    }

    /** 2.13 — كل العناصر المحددة تُفحص (كان الفحص على الأول فقط) */
    public function test_brand_and_category_delete_checks_every_selected_row(): void
    {
        $free = Brand::create(['name' => 'حرة', 'code' => 'B1', 'sort' => 1, 'is_active' => 1]);
        $used = Brand::create(['name' => 'مستخدمة', 'code' => 'B2', 'sort' => 1, 'is_active' => 1]);
        Product::create(['name' => 'قلم', 'sku' => 'PEN', 'is_active' => true, 'brand_id' => $used->id]);

        $this->post(route('finance.brands.delete'), ['id' => [$free->id, $used->id]])->assertStatus(422);
        $this->assertNotNull(Brand::find($free->id));
        $this->assertNotNull(Brand::find($used->id));
    }

    /** 2.3 / 2.4 */
    public function test_category_parent_rules_and_children_block_delete(): void
    {
        $parent = Category::create(['name' => 'أب', 'code' => 'C1', 'sort' => 1, 'is_active' => 1]);
        $child  = Category::create(['name' => 'ابن', 'code' => 'C2', 'sort' => 1, 'is_active' => 1, 'parent_id' => $parent->id]);

        $this->post(route('finance.category.update'), ['id' => $parent->id, 'name' => 'أب', 'code' => 'C1', 'sort' => 1, 'parent_id' => $parent->id])->assertSessionHasErrors('parent_id');
        $this->post(route('finance.category.update'), ['id' => $parent->id, 'name' => 'أب', 'code' => 'C1', 'sort' => 1, 'parent_id' => $child->id])->assertSessionHasErrors('parent_id');
        $this->assertNull($parent->fresh()->parent_id);

        $this->post(route('finance.category.delete'), ['id' => [$parent->id]])->assertStatus(422);
        $this->assertNotNull(Category::find($parent->id));
    }

    /** 2.1 / 2.2 / 2.5 / 2.6 */
    public function test_duplicate_codes_return_validation_errors_not_500(): void
    {
        Brand::create(['name' => 'ب', 'code' => 'DUP', 'sort' => 1, 'is_active' => 1]);
        $deleted = Category::create(['name' => 'محذوفة', 'code' => 'OLD', 'sort' => 1, 'is_active' => 1]);
        $deleted->delete();
        Unit::create(['name' => 'و', 'code' => 'U1']);
        Attribute::create(['name' => 'خ', 'code' => 'A1']);

        $this->post(route('finance.brands.store'), ['name' => 'ب2', 'code' => 'DUP', 'sort' => 1])->assertSessionHasErrors('code');
        $this->post(route('finance.category.store'), ['name' => 'ف', 'code' => 'OLD', 'sort' => 1])->assertSessionHasErrors('code');
        $this->post(route('finance.units.store'), ['name' => 'و2', 'code' => 'U1'])->assertSessionHasErrors('code');
        $this->post(route('finance.attributes.store'), ['name' => 'خ2', 'code' => 'A1'])->assertSessionHasErrors('code');
    }

    /** 2.15 */
    public function test_product_with_stock_or_purchases_cannot_be_deleted(): void
    {
        $wh   = Warehouse::create(['name' => 'رئيسي', 'is_active' => true]);
        $pen  = Product::create(['name' => 'قلم', 'sku' => 'PEN', 'is_active' => true]);
        $free = Product::create(['name' => 'حر', 'sku' => 'FREE', 'is_active' => true]);
        InventoryItem::create(['warehouse_id' => $wh->id, 'product_id' => $pen->id, 'quantity' => 5]);

        $this->post(route('finance.products.delete'), ['id' => [$pen->id]])->assertStatus(422);
        $this->assertNotNull(Product::find($pen->id));
        $this->post(route('finance.products.delete'), ['id' => [$free->id]])->assertOk();
        $this->assertNull(Product::find($free->id));

        // 7.5 / المستودع: فيه مخزون
        $this->post(route('finance.warehouses.delete'), ['id' => $wh->id])->assertStatus(422);
    }

    /** 7.5 — عقد الحذف الموحّد: id مفرد / ids، والخزنة التي لها حركات لا تُحذف */
    public function test_delete_accepts_id_or_ids_and_blocks_used_treasury(): void
    {
        $t1 = Treasury::create(['name' => 'خ1', 'account_tree_id' => $this->acc('1101')->id, 'currency' => 'SAR', 'balance' => 0, 'is_active' => true]);
        $t2 = Treasury::create(['name' => 'خ2', 'account_tree_id' => $this->acc('1102')->id, 'currency' => 'SAR', 'balance' => 0, 'is_active' => true]);
        TreasuryTransaction::create(['treasury_id' => $t2->id, 'type' => 'deposit', 'amount' => 1]);

        $this->post(route('finance.treasuries.delete'), ['id' => $t1->id])->assertOk();
        $this->assertNull(Treasury::find($t1->id));
        $this->post(route('finance.treasuries.delete'), ['ids' => [$t2->id]])->assertStatus(422);
        $this->assertNotNull(Treasury::find($t2->id));
    }

    /** 3.3 / 3.4 */
    public function test_unit_conversion_duplicates_and_chain_consistency(): void
    {
        $box   = Unit::create(['name' => 'علبة', 'code' => 'BOX']);
        $strip = Unit::create(['name' => 'شريط', 'code' => 'STRIP']);
        $pill  = Unit::create(['name' => 'حبة', 'code' => 'PILL']);
        $med   = Product::create(['name' => 'دواء', 'sku' => 'MED', 'is_active' => true, 'unit_id' => $pill->id]);
        $conv  = fn ($b, $t, $r) => $this->post(route('finance.products.unitconversions.store', $med->id), [
            'product_id' => $med->id, 'base_unit' => $b->id, 'target_unit' => $t->id, 'conversion_rate' => $r, 'decimal_places' => 2,
        ]);

        $conv($box, $strip, 2)->assertSessionHasNoErrors();
        $conv($strip, $pill, 10)->assertSessionHasNoErrors();
        $conv($box, $strip, 3)->assertSessionHasErrors('target_unit');        // تكرار
        $conv($box, $pill, 25)->assertSessionHasErrors('conversion_rate');    // السلسلة تعطي 20
        $conv($box, $pill, 20)->assertSessionHasNoErrors();

        $this->assertSame(3, UnitConversion::where('product_id', $med->id)->count());
        $this->assertEquals(2, (float) UnitConversion::where('base_unit', $box->id)->where('target_unit', $strip->id)->value('conversion_rate'));
    }

    /** 2.16 */
    public function test_sku_uses_selected_category_and_brand(): void
    {
        $cat   = Category::create(['name' => 'مكتبية', 'code' => 'OFFICE', 'sort' => 1, 'is_active' => 1]);
        $brand = Brand::create(['name' => 'باركر', 'code' => 'PARKER', 'sort' => 1, 'is_active' => 1]);
        Category::create(['name' => 'أخرى', 'code' => 'ZZZ', 'sort' => 1, 'is_active' => 1]);

        $res = $this->getJson(route('finance.products.generate-sku', 1) . '?category_id=' . $cat->id . '&brand_id=' . $brand->id)->assertOk()->json();
        $this->assertSame('OFF-PAR-0001', $res['sku']);

        Product::create(['name' => 'قلم', 'sku' => 'OFF-PAR-0001', 'is_active' => true]);
        $res = $this->getJson(route('finance.products.generate-sku', 1) . '?category_id=' . $cat->id . '&brand_id=' . $brand->id)->json();
        $this->assertSame('OFF-PAR-0002', $res['sku']);
    }

    /** 1.5 / 1.6 / 1.8 / 1.9 */
    public function test_admin_rules(): void
    {
        $inactive = Admin::create(['name' => 'موقوف', 'email' => 'off@test.local', 'phone' => '0111', 'password' => bcrypt('secret123'), 'is_active' => '0']);
        $this->assertSame('0', (string) $inactive->fresh()->is_active);               // لا يُحوَّل لمفعّل
        $this->assertArrayNotHasKey('password', $inactive->toArray());               // 1.5

        Auth::guard('admin')->logout();
        $this->post(route('admin.login.submit'), ['email' => 'off@test.local', 'password' => 'secret123'])->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('admin')->check());

        Admin::create(['name' => 'فعال', 'email' => 'on@test.local', 'phone' => '0112', 'password' => bcrypt('secret123'), 'is_active' => '1']);
        $this->post(route('admin.login.submit'), ['email' => 'on@test.local', 'password' => 'secret123']);
        $this->assertTrue(Auth::guard('admin')->check());

        $me = Auth::guard('admin')->user();
        $this->post(route('admin.admins.delete'), ['id' => $me->id])->assertStatus(422);       // 1.9
        $this->post(route('admin.admins.delete'), ['id' => $inactive->id])->assertOk();         // 1.8: id مفرد
        $this->assertNull(Admin::find($inactive->id));
    }

    /** migration */
    public function test_migration_fixes_category_parents_and_activates_main_admin(): void
    {
        $self = Category::create(['name' => 'ذاتية', 'code' => 'S', 'sort' => 1, 'is_active' => 1]);
        DB::table('categories')->where('id', $self->id)->update(['parent_id' => $self->id]);
        $dead  = Category::create(['name' => 'محذوفة', 'code' => 'D', 'sort' => 1, 'is_active' => 1]);
        $child = Category::create(['name' => 'يتيمة', 'code' => 'O', 'sort' => 1, 'is_active' => 1, 'parent_id' => $dead->id]);
        $dead->delete();

        $this->runMigrationUp('2026_11_18_000005_minor_qa_batch5_fixes.php');

        $this->assertNull(Category::withTrashed()->find($self->id)->parent_id);
        $this->assertNull($child->fresh()->parent_id);
    }
}
