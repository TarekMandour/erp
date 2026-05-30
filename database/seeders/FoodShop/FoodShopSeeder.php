<?php

namespace Database\Seeders\FoodShop;

use App\Models\Finance\Brand;
use App\Models\Finance\Category;
use App\Models\Finance\Customer;
use App\Models\Finance\InventoryItem;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\Unit;
use App\Models\Finance\UnitConversion;
use App\Models\Finance\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class FoodShopSeeder extends Seeder
{
    public function run(): void
    {
        // ── Warehouse ─────────────────────────────────────────────────────
        $warehouse = Warehouse::firstOrCreate(
            ['name' => 'مستودع المواد الغذائية'],
            ['is_active' => true, 'location' => null]
        );

        // ── Brands ───────────────────────────────────────────────────────
        $brands = [];
        foreach ([
            ['name' => 'الراعي',   'code' => 'ALRAI'],
            ['name' => 'لولو',     'code' => 'LULU'],
            ['name' => 'داريم',    'code' => 'DARIM'],
            ['name' => 'لوكر',     'code' => 'LOCKER'],
            ['name' => 'بيتي',     'code' => 'PITY'],
        ] as $b) {
            $brands[$b['code']] = Brand::firstOrCreate(
                ['code' => $b['code']],
                ['name' => $b['name'], 'sort' => 1, 'is_active' => true]
            );
        }

        // ── Root category ─────────────────────────────────────────────────
        $catFood = Category::firstOrCreate(
            ['code' => 'FOOD'],
            ['name' => 'المواد الغذائية', 'sort' => 1, 'is_active' => true]
        );

        $cats = [];
        foreach ([
            ['name' => 'الأجبان والألبان',  'code' => 'DAIRY'],
            ['name' => 'الحلويات والبسكويت','code' => 'BISCUITS'],
            ['name' => 'الشوكولاتة',        'code' => 'CHOCOLATE'],
            ['name' => 'المعلبات',          'code' => 'CANNED'],
            ['name' => 'التوابل والبهارات', 'code' => 'SPICES'],
        ] as $c) {
            $cats[$c['code']] = Category::firstOrCreate(
                ['code' => $c['code']],
                ['name' => $c['name'], 'parent_id' => $catFood->id, 'sort' => 1, 'is_active' => true]
            );
        }

        // ── Units ─────────────────────────────────────────────────────────
        $unitGram    = Unit::firstOrCreate(['code' => 'G'],   ['name' => 'غرام']);
        $unitKg      = Unit::firstOrCreate(['code' => 'KG'],  ['name' => 'كيلوغرام']);
        $unitPiece   = Unit::firstOrCreate(['code' => 'PCS'], ['name' => 'قطعة']);
        $unitPack    = Unit::firstOrCreate(['code' => 'PACK'],['name' => 'علبة']);
        $unitCarton  = Unit::firstOrCreate(['code' => 'CTN'], ['name' => 'كرتون']);

        // ── Customers ────────────────────────────────────────────────────
        $customers = [];
        foreach ([
            ['customer_code' => 'CUST-F01', 'name' => 'بقالة النور',      'phone' => '0551234601', 'email' => 'alnoor@example.com'],
            ['customer_code' => 'CUST-F02', 'name' => 'سوبرماركت الأمل',  'phone' => '0551234602', 'email' => 'alamal@example.com'],
            ['customer_code' => 'CUST-F03', 'name' => 'مطعم الطيبات',     'phone' => '0551234603', 'email' => 'altaybat@example.com'],
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

        // ══════════════════════════════════════════════════════════════════
        // SECTION A: CHEESE — sold by gram, base unit = gram
        // UnitConversions: gram → 250g pack, 500g pack, 1kg, 5kg block
        // ══════════════════════════════════════════════════════════════════
        $cheeseProducts = [
            [
                'sku'  => 'CHZ-001', 'name' => 'جبن شيدر مقطع',
                'cat'  => 'DAIRY',   'brand' => 'ALRAI',
                'unit' => $unitGram,
                // price per gram
                'buy'  => 0.05, 'sell' => 0.08,
                'tax'  => 15,   'alert' => 500,
                'has_variants' => false,
                // stock in grams
                'stock' => 20000,
                // conversions: [name, target_unit_id, rate, allow_fractions, decimal_places, is_default]
                'conversions' => [
                    ['label' => 'عبوة 250 جرام', 'unit' => $unitPack,  'rate' => 250,  'fractions' => false, 'decimals' => 0, 'default' => false],
                    ['label' => 'عبوة 500 جرام', 'unit' => $unitPack,  'rate' => 500,  'fractions' => false, 'decimals' => 0, 'default' => true],
                    ['label' => 'كيلو',           'unit' => $unitKg,   'rate' => 1000, 'fractions' => true,  'decimals' => 3, 'default' => false],
                    ['label' => 'بلوك 5 كيلو',   'unit' => $unitKg,   'rate' => 5000, 'fractions' => false, 'decimals' => 0, 'default' => false],
                ],
            ],
            [
                'sku'  => 'CHZ-002', 'name' => 'جبن موزاريلا',
                'cat'  => 'DAIRY',   'brand' => 'DARIM',
                'unit' => $unitGram,
                'buy'  => 0.06, 'sell' => 0.10,
                'tax'  => 15,   'alert' => 500,
                'has_variants' => false,
                'stock' => 15000,
                'conversions' => [
                    ['label' => 'عبوة 250 جرام', 'unit' => $unitPack, 'rate' => 250,  'fractions' => false, 'decimals' => 0, 'default' => false],
                    ['label' => 'عبوة 500 جرام', 'unit' => $unitPack, 'rate' => 500,  'fractions' => false, 'decimals' => 0, 'default' => true],
                    ['label' => 'كيلو',           'unit' => $unitKg,  'rate' => 1000, 'fractions' => true,  'decimals' => 3, 'default' => false],
                ],
            ],
            [
                'sku'  => 'CHZ-003', 'name' => 'جبن جودة سوداء',
                'cat'  => 'DAIRY',   'brand' => 'LULU',
                'unit' => $unitGram,
                'buy'  => 0.07, 'sell' => 0.12,
                'tax'  => 15,   'alert' => 300,
                'has_variants' => false,
                'stock' => 10000,
                'conversions' => [
                    ['label' => 'عبوة 200 جرام', 'unit' => $unitPack, 'rate' => 200,  'fractions' => false, 'decimals' => 0, 'default' => false],
                    ['label' => 'كيلو',           'unit' => $unitKg,  'rate' => 1000, 'fractions' => true,  'decimals' => 3, 'default' => true],
                    ['label' => 'بلوك 3 كيلو',   'unit' => $unitKg,  'rate' => 3000, 'fractions' => false, 'decimals' => 0, 'default' => false],
                ],
            ],
            // Yogurt / Labneh sold by gram
            [
                'sku'  => 'LBN-001', 'name' => 'لبنة كريمية',
                'cat'  => 'DAIRY',   'brand' => 'ALRAI',
                'unit' => $unitGram,
                'buy'  => 0.03, 'sell' => 0.05,
                'tax'  => 0,    'alert' => 1000,
                'has_variants' => false,
                'stock' => 30000,
                'conversions' => [
                    ['label' => 'كوب 200 جرام',  'unit' => $unitPack, 'rate' => 200,  'fractions' => false, 'decimals' => 0, 'default' => false],
                    ['label' => 'عبوة 500 جرام', 'unit' => $unitPack, 'rate' => 500,  'fractions' => false, 'decimals' => 0, 'default' => true],
                    ['label' => 'كيلو',           'unit' => $unitKg,  'rate' => 1000, 'fractions' => true,  'decimals' => 3, 'default' => false],
                ],
            ],
        ];

        // ══════════════════════════════════════════════════════════════════
        // SECTION B: BISCUITS — sold by piece / pack / carton
        // ══════════════════════════════════════════════════════════════════
        $biscuitProducts = [
            [
                'sku'  => 'BSC-001', 'name' => 'بسكويت أوريو',
                'cat'  => 'BISCUITS', 'brand' => 'LOCKER',
                'unit' => $unitPiece,
                'buy'  => 1.50, 'sell' => 2.75,
                'tax'  => 15,   'alert' => 24,
                'has_variants' => false,
                'stock' => 480,
                'conversions' => [
                    ['label' => 'علبة 12 قطعة', 'unit' => $unitPack,   'rate' => 12,  'fractions' => false, 'decimals' => 0, 'default' => true],
                    ['label' => 'كرتون 24 علبة','unit' => $unitCarton, 'rate' => 288, 'fractions' => false, 'decimals' => 0, 'default' => false],
                ],
            ],
            [
                'sku'  => 'BSC-002', 'name' => 'بسكويت شيبس أوري بالشوكولاتة',
                'cat'  => 'BISCUITS', 'brand' => 'PITY',
                'unit' => $unitPiece,
                'buy'  => 2.00, 'sell' => 3.50,
                'tax'  => 15,   'alert' => 20,
                'has_variants' => false,
                'stock' => 360,
                'conversions' => [
                    ['label' => 'علبة 10 قطع',  'unit' => $unitPack,   'rate' => 10,  'fractions' => false, 'decimals' => 0, 'default' => true],
                    ['label' => 'كرتون 20 علبة','unit' => $unitCarton, 'rate' => 200, 'fractions' => false, 'decimals' => 0, 'default' => false],
                ],
            ],
            [
                'sku'  => 'BSC-003', 'name' => 'كراكر مالح',
                'cat'  => 'BISCUITS', 'brand' => 'LULU',
                'unit' => $unitPiece,
                'buy'  => 0.80, 'sell' => 1.50,
                'tax'  => 15,   'alert' => 50,
                'has_variants' => false,
                'stock' => 1200,
                'conversions' => [
                    ['label' => 'علبة 30 قطعة', 'unit' => $unitPack,   'rate' => 30,  'fractions' => false, 'decimals' => 0, 'default' => true],
                    ['label' => 'كرتون 12 علبة','unit' => $unitCarton, 'rate' => 360, 'fractions' => false, 'decimals' => 0, 'default' => false],
                ],
            ],
            // Chocolate bars — sold by piece
            [
                'sku'  => 'CHC-001', 'name' => 'شوكولاتة كيت كات',
                'cat'  => 'CHOCOLATE', 'brand' => 'LOCKER',
                'unit' => $unitPiece,
                'buy'  => 1.20, 'sell' => 2.25,
                'tax'  => 15,   'alert' => 30,
                'has_variants' => false,
                'stock' => 600,
                'conversions' => [
                    ['label' => 'صندوق 24 قطعة','unit' => $unitPack,   'rate' => 24,  'fractions' => false, 'decimals' => 0, 'default' => true],
                    ['label' => 'كرتون 12 صندوق','unit' => $unitCarton,'rate' => 288, 'fractions' => false, 'decimals' => 0, 'default' => false],
                ],
            ],
            // Canned — sold by piece (can)
            [
                'sku'  => 'CND-001', 'name' => 'تونة في زيت',
                'cat'  => 'CANNED', 'brand' => 'DARIM',
                'unit' => $unitPiece,
                'buy'  => 4.50, 'sell' => 7.50,
                'tax'  => 0,    'alert' => 12,
                'has_variants' => false,
                'stock' => 240,
                'conversions' => [
                    ['label' => 'علبة 6 عبوات',  'unit' => $unitPack,   'rate' => 6,   'fractions' => false, 'decimals' => 0, 'default' => true],
                    ['label' => 'كرتون 24 علبة', 'unit' => $unitCarton, 'rate' => 144, 'fractions' => false, 'decimals' => 0, 'default' => false],
                ],
            ],
            // Spices sold by gram
            [
                'sku'  => 'SPC-001', 'name' => 'كمون مطحون',
                'cat'  => 'SPICES', 'brand' => 'LULU',
                'unit' => $unitGram,
                'buy'  => 0.02, 'sell' => 0.04,
                'tax'  => 0,    'alert' => 500,
                'has_variants' => false,
                'stock' => 25000,
                'conversions' => [
                    ['label' => 'عبوة 100 جرام', 'unit' => $unitPack, 'rate' => 100,  'fractions' => false, 'decimals' => 0, 'default' => false],
                    ['label' => 'عبوة 500 جرام', 'unit' => $unitPack, 'rate' => 500,  'fractions' => false, 'decimals' => 0, 'default' => true],
                    ['label' => 'كيلو',           'unit' => $unitKg,  'rate' => 1000, 'fractions' => true,  'decimals' => 3, 'default' => false],
                ],
            ],
        ];

        $allProducts = array_merge($cheeseProducts, $biscuitProducts);

        foreach ($allProducts as $pd) {
            $product = Product::firstOrCreate(
                ['sku' => $pd['sku']],
                [
                    'name'           => $pd['name'],
                    'category_id'    => $cats[$pd['cat']]->id,
                    'brand_id'       => $brands[$pd['brand']]->id,
                    'unit_id'        => $pd['unit']->id,
                    'purchase_price' => $pd['buy'],
                    'selling_price'  => $pd['sell'],
                    'tax_rate'       => $pd['tax'],
                    'alert_quantity' => $pd['alert'],
                    'is_active'      => true,
                    'has_variants'   => $pd['has_variants'],
                    'has_expiry'     => true,
                    'sort'           => 1,
                ]
            );

            // Default single variant for non-variant products
            $variant = ProductVariant::firstOrCreate(
                ['sku' => $pd['sku'] . '-DEFAULT'],
                [
                    'product_id'     => $product->id,
                    'attributes'     => [],
                    'purchase_price' => $pd['buy'],
                    'selling_price'  => $pd['sell'],
                    'cost_price'     => $pd['buy'],
                    'alert_quantity' => $pd['alert'],
                    'is_active'      => true,
                    'is_default'     => true,
                    'sort_order'     => 1,
                ]
            );

            if (Schema::hasColumn('products', 'default_variant_id')) {
                $product->update(['default_variant_id' => $variant->id]);
            }

            // Inventory
            InventoryItem::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'variant_id' => $variant->id],
                ['quantity' => $pd['stock']]
            );

            // Unit conversions
            foreach ($pd['conversions'] as $cv) {
                UnitConversion::firstOrCreate(
                    [
                        'product_id'  => $product->id,
                        'variant_id'  => $variant->id,
                        'target_unit' => $cv['unit']->id,
                    ],
                    [
                        'base_unit'       => $pd['unit']->id,
                        'conversion_rate' => $cv['rate'],
                        'is_default'      => $cv['default'],
                        'allow_fractions' => $cv['fractions'],
                        'decimal_places'  => $cv['decimals'],
                    ]
                );
            }
        }

        $this->command->info('✔ FoodShop seeded: ' . count($allProducts) . ' products (cheese by gram + biscuits by piece) with unit conversions.');
    }
}
