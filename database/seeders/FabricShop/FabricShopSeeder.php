<?php

namespace Database\Seeders\FabricShop;

use App\Events\Finance\Purchases\PurchaseEvent;
use App\Models\Finance\Category;
use App\Models\Finance\Product;
use App\Models\Finance\Purchase;
use App\Models\Finance\PurchaseItem;
use App\Models\Finance\Supplier;
use App\Models\Finance\Unit;
use App\Models\Finance\UnitConversion;
use App\Models\Finance\Warehouse;
use App\Services\Finance\PurchaseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fabric shop example: purchasing fabric "bolts" while stock is tracked
 * in a finer base unit (meters / kilograms) via unit_conversions.
 *
 * Run directly with: php artisan db:seed --class="Database\Seeders\FabricShop\FabricShopSeeder"
 */
class FabricShopSeeder extends Seeder
{
    public function run(): void
    {
        // ── Warehouse & Supplier ─────────────────────────────────────────
        $warehouse = Warehouse::firstOrCreate(
            ['name' => 'مستودع الأقمشة'],
            ['is_active' => true, 'location' => null]
        );

        $supplier = Supplier::firstOrCreate(
            ['company_name' => 'مؤسسة الأقمشة الذهبية'],
            [
                'name'           => 'مؤسسة الأقمشة الذهبية',
                'phone'          => '0512345678',
                'account_status' => 'active',
                'wallet_balance' => 0,
                'total_orders'   => 0,
            ]
        );

        $category = Category::firstOrCreate(
            ['code' => 'FABRIC'],
            ['name' => 'أقمشة', 'sort' => 1, 'is_active' => true]
        );

        // ── Customers ────────────────────────────────────────────────────
        $customers = [];
        foreach ([
            ['customer_code' => 'CUST-C01', 'name' => 'سارة عبدالله',   'phone' => '0501234501', 'email' => 'sara@example.com'],
            ['customer_code' => 'CUST-C02', 'name' => 'محمد الغامدي',   'phone' => '0501234502', 'email' => 'mghamdi@example.com'],
            ['customer_code' => 'CUST-C03', 'name' => 'نور الرشيد',     'phone' => '0501234503', 'email' => 'nour@example.com'],
        ] as $c) {
            $customers[] = Customer::firstOrCreate(
                ['customer_code' => $c['customer_code']],
                array_merge($c, [
                    'account_status' => 'active',
                    'wallet_balance' => 0,
                    'total_orders'   => 0,
                    'total_spent'    => 0,
                    'join_date'      => now()->toDateString(),
                ])
            );
        }

        // ── Base unit (the actual stock-keeping unit for both fabrics) ───
        $unitMeter = Unit::firstOrCreate(['code' => 'MTR'], ['name' => 'متر']);
        $unitKilo  = Unit::firstOrCreate(['code' => 'KG'],  ['name' => 'كيلوجرام']);
        $unitBolt  = Unit::firstOrCreate(['code' => 'BOLT'], ['name' => 'لفة']);

        // ── Products: stock is kept in the base unit, "bolt" is only a
        //    purchase/selling convenience unit resolved via UnitConversion ─
        $blueFabric = Product::firstOrCreate(
            ['sku' => 'FAB-BLUE-001'],
            [
                'name'           => 'قماش أزرق',
                'category_id'    => $category->id,
                'unit_id'        => $unitMeter->id, // stock tracked in meters
                'purchase_price' => 14,              // cost per meter
                'selling_price'  => 22,               // sale price per meter
                'tax_rate'       => 15,
                'alert_quantity' => 20,
                'is_active'      => true,
            ]
        );

        $redFabric = Product::firstOrCreate(
            ['sku' => 'FAB-RED-001'],
            [
                'name'           => 'قماش أحمر',
                'category_id'    => $category->id,
                'unit_id'        => $unitMeter->id, // stock tracked in meters (bolt & kilo are just conversions of it)
                'purchase_price' => 8.24,             // cost per meter (≈ 17m per kilo × 8.24 ≈ old 140/kilo feel)
                'selling_price'  => 13,                // sale price per meter
                'tax_rate'       => 15,
                'alert_quantity' => 20,
                'is_active'      => true,
            ]
        );

        // ── Unit conversions: base_unit/target_unit reference Unit IDs ─────
        // Blue bolt = 50 meters
        $blueBoltConversion = UnitConversion::firstOrCreate(
            ['product_id' => $blueFabric->id, 'variant_id' => null, 'base_unit' => $unitMeter->id, 'target_unit' => $unitBolt->id],
            [
                'conversion_rate' => 50,
                'is_default'      => true,
                'allow_fractions' => false,
                'decimal_places'  => 0,
            ]
        );

        // Red fabric: 1 bolt = 34 meters, and 1 kilo = 17 meters (i.e. 1 bolt = 34m = 2 kilos)
        // Both conversions share the same base unit (meter), so the product can be
        // bought/sold/transferred interchangeably by bolt, meter, or kilo.
        $redBoltConversion = UnitConversion::firstOrCreate(
            ['product_id' => $redFabric->id, 'variant_id' => null, 'base_unit' => $unitMeter->id, 'target_unit' => $unitBolt->id],
            [
                'conversion_rate' => 34,
                'is_default'      => true,
                'allow_fractions' => false,
                'decimal_places'  => 0,
            ]
        );

        $redKiloConversion = UnitConversion::firstOrCreate(
            ['product_id' => $redFabric->id, 'variant_id' => null, 'base_unit' => $unitMeter->id, 'target_unit' => $unitKilo->id],
            [
                'conversion_rate' => 17, // 1 kilo = 17 meters → 2 kilos = 34 meters = 1 bolt
                'is_default'      => false,
                'allow_fractions' => true,
                'decimal_places'  => 2,
            ]
        );

        // ── Purchase invoice: 3 blue bolts + 2 red bolts ─────────────────
        $purchase = null;

        $dispatcher = Purchase::getEventDispatcher();
        Purchase::unsetEventDispatcher();

        try {
            DB::transaction(function () use (
                &$purchase, $supplier, $warehouse,
                $blueFabric, $redFabric, $blueBoltConversion, $redBoltConversion
            ) {
                $purchase = Purchase::create([
                    'purchase_number' => Purchase::generateNumber(),
                    'supplier_id'     => $supplier->id,
                    'warehouse_id'    => $warehouse->id,
                    'date'            => now()->toDateString(),
                    'payment_type'    => 'cash',
                    'status'          => 'received',
                    'notes'           => 'فاتورة شراء أقمشة (تحويل وحدات: لفة ⇄ متر / كيلوجرام)',
                    'paid'            => 0,
                ]);

                // 3 bolts of blue fabric @ 700 SAR/bolt (= 50m × 14) → adds 150m to stock
                $blueQty   = 3;
                $blueCost  = 700;
                PurchaseItem::create([
                    'purchase_id'        => $purchase->id,
                    'product_id'         => $blueFabric->id,
                    'unit_conversion_id' => $blueBoltConversion->id,
                    'quantity'           => $blueQty,
                    'unit_cost'          => $blueCost,
                    'discount'           => 0,
                    'tax_rate'           => 15,
                    'total'              => round($blueQty * $blueCost * 1.15, 2),
                ]);

                // 2 bolts of red fabric @ 280 SAR/bolt (= 34m × 8.24) → adds 68m to stock
                $redQty  = 2;
                $redCost = 280;
                PurchaseItem::create([
                    'purchase_id'        => $purchase->id,
                    'product_id'         => $redFabric->id,
                    'unit_conversion_id' => $redBoltConversion->id,
                    'quantity'           => $redQty,
                    'unit_cost'          => $redCost,
                    'discount'           => 0,
                    'tax_rate'           => 15,
                    'total'              => round($redQty * $redCost * 1.15, 2),
                ]);

                $items    = $purchase->items()->get();
                $subtotal = $items->sum(fn($i) => (float) $i->quantity * (float) $i->unit_cost - (float) $i->discount);
                $tax      = $items->sum(fn($i) => round(((float) $i->quantity * (float) $i->unit_cost - (float) $i->discount) * (float) $i->tax_rate / 100, 2));

                $purchase->update([
                    'subtotal' => round($subtotal, 2),
                    'discount' => 0,
                    'tax'      => round($tax, 2),
                    'total'    => round($subtotal + $tax, 2),
                ]);
            });
        } finally {
            Purchase::setEventDispatcher($dispatcher);
        }

        // Post the journal entry, then apply inventory/warehouse-transaction effects
        // (this is where quantity gets resolved: bolts × conversion_rate = base units)
        PurchaseEvent::dispatch($purchase->fresh(), 'create');
        app(PurchaseService::class)->apply($purchase->fresh(['items.unitConversion']));

        $this->command?->info("Fabric purchase {$purchase->purchase_number} created:");
        $this->command?->info(' - قماش أزرق: 3 لفات × 50م = 150 متر مضافة للمخزون');
        $this->command?->info(' - قماش أحمر: 2 لفة × 34م = 68 متر مضافة للمخزون (1 لفة = 34م = 2 كيلوجرام)');
    }
}
