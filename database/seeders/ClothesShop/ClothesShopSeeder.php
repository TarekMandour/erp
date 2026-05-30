<?php

namespace Database\Seeders\ClothesShop;

use App\Models\Finance\Attribute;
use App\Models\Finance\Brand;
use App\Models\Finance\Category;
use App\Models\Finance\Customer;
use App\Models\Finance\InventoryItem;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\Warehouse;
use Illuminate\Database\Seeder;

class ClothesShopSeeder extends Seeder
{
    public function run(): void
    {
        // ── Attributes ────────────────────────────────────────────────────
        $attrSize = Attribute::firstOrCreate(
            ['code' => 'SIZE'],
            ['name' => 'المقاس']
        );
        $attrColor = Attribute::firstOrCreate(
            ['code' => 'COLOR'],
            ['name' => 'اللون']
        );

        // ── Warehouse ─────────────────────────────────────────────────────
        $warehouse = Warehouse::firstOrCreate(
            ['name' => 'مستودع الملابس الرئيسي'],
            ['is_active' => true, 'location' => null]
        );

        // ── Brands ───────────────────────────────────────────────────────
        $brands = [];
        foreach ([
            ['name' => 'نايك',    'code' => 'NIKE'],
            ['name' => 'أديداس', 'code' => 'ADIDAS'],
            ['name' => 'زارا',   'code' => 'ZARA'],
        ] as $b) {
            $brands[$b['code']] = Brand::firstOrCreate(
                ['code' => $b['code']],
                ['name' => $b['name'], 'sort' => 1, 'is_active' => true]
            );
        }

        // ── Categories ────────────────────────────────────────────────────
        $catRoot = Category::firstOrCreate(
            ['code' => 'CLOTHES'],
            ['name' => 'الملابس', 'sort' => 1, 'is_active' => true]
        );
        $cats = [];
        foreach ([
            ['name' => 'تيشرتات',    'code' => 'TSHIRTS'],
            ['name' => 'بناطيل',     'code' => 'PANTS'],
            ['name' => 'فساتين',     'code' => 'DRESSES'],
            ['name' => 'جاكيتات',   'code' => 'JACKETS'],
            ['name' => 'ملابس أطفال', 'code' => 'KIDS'],
        ] as $c) {
            $cats[$c['code']] = Category::firstOrCreate(
                ['code' => $c['code']],
                ['name' => $c['name'], 'parent_id' => $catRoot->id, 'sort' => 1, 'is_active' => true]
            );
        }

        // ── Unit ──────────────────────────────────────────────────────────
        $unitPiece = \App\Models\Finance\Unit::firstOrCreate(
            ['code' => 'PCS'],
            ['name' => 'قطعة']
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

        // ── Products ─────────────────────────────────────────────────────
        $productsData = [
            // T-Shirts
            [
                'sku'   => 'TSH-001', 'name' => 'تيشرت قطني كلاسيك',
                'cat'   => 'TSHIRTS', 'brand' => 'NIKE',
                'buy'   => 40, 'sell' => 89,
                'tax'   => 15, 'alert' => 5,
                'variants' => [
                    ['size' => 'S',  'color' => 'أبيض',  'sku' => 'TSH-001-S-W',  'buy' => 40,  'sell' => 89],
                    ['size' => 'M',  'color' => 'أبيض',  'sku' => 'TSH-001-M-W',  'buy' => 40,  'sell' => 89],
                    ['size' => 'L',  'color' => 'أبيض',  'sku' => 'TSH-001-L-W',  'buy' => 40,  'sell' => 89],
                    ['size' => 'XL', 'color' => 'أبيض',  'sku' => 'TSH-001-XL-W', 'buy' => 42,  'sell' => 92],
                    ['size' => 'S',  'color' => 'أسود',  'sku' => 'TSH-001-S-B',  'buy' => 40,  'sell' => 89],
                    ['size' => 'M',  'color' => 'أسود',  'sku' => 'TSH-001-M-B',  'buy' => 40,  'sell' => 89],
                    ['size' => 'L',  'color' => 'أسود',  'sku' => 'TSH-001-L-B',  'buy' => 40,  'sell' => 89],
                    ['size' => 'XL', 'color' => 'أسود',  'sku' => 'TSH-001-XL-B', 'buy' => 42,  'sell' => 92],
                    ['size' => 'M',  'color' => 'أحمر',  'sku' => 'TSH-001-M-R',  'buy' => 40,  'sell' => 89],
                    ['size' => 'L',  'color' => 'أحمر',  'sku' => 'TSH-001-L-R',  'buy' => 40,  'sell' => 89],
                ], // placeholder — replaced below with attribute IDs
            ],
            [
                'sku'   => 'TSH-002', 'name' => 'تيشرت بولو',
                'cat'   => 'TSHIRTS', 'brand' => 'ADIDAS',
                'buy'   => 65, 'sell' => 129,
                'tax'   => 15, 'alert' => 3,
                'variants' => [
                    ['size' => 'S',  'color' => 'كحلي', 'sku' => 'TSH-002-S-N',  'buy' => 65, 'sell' => 129],
                    ['size' => 'M',  'color' => 'كحلي', 'sku' => 'TSH-002-M-N',  'buy' => 65, 'sell' => 129],
                    ['size' => 'L',  'color' => 'كحلي', 'sku' => 'TSH-002-L-N',  'buy' => 65, 'sell' => 129],
                    ['size' => 'XL', 'color' => 'كحلي', 'sku' => 'TSH-002-XL-N', 'buy' => 68, 'sell' => 135],
                    ['size' => 'M',  'color' => 'رمادي','sku' => 'TSH-002-M-G',  'buy' => 65, 'sell' => 129],
                    ['size' => 'L',  'color' => 'رمادي','sku' => 'TSH-002-L-G',  'buy' => 65, 'sell' => 129],
                ],
            ],
            // Pants
            [
                'sku'   => 'PNT-001', 'name' => 'بنطلون جينز كلاسيك',
                'cat'   => 'PANTS', 'brand' => 'ZARA',
                'buy'   => 85, 'sell' => 179,
                'tax'   => 15, 'alert' => 3,
                'variants' => [
                    ['size' => '28', 'color' => 'أزرق فاتح', 'sku' => 'PNT-001-28-LB', 'buy' => 85, 'sell' => 179],
                    ['size' => '30', 'color' => 'أزرق فاتح', 'sku' => 'PNT-001-30-LB', 'buy' => 85, 'sell' => 179],
                    ['size' => '32', 'color' => 'أزرق فاتح', 'sku' => 'PNT-001-32-LB', 'buy' => 85, 'sell' => 179],
                    ['size' => '34', 'color' => 'أزرق فاتح', 'sku' => 'PNT-001-34-LB', 'buy' => 87, 'sell' => 185],
                    ['size' => '30', 'color' => 'أسود',       'sku' => 'PNT-001-30-B',  'buy' => 85, 'sell' => 179],
                    ['size' => '32', 'color' => 'أسود',       'sku' => 'PNT-001-32-B',  'buy' => 85, 'sell' => 179],
                    ['size' => '34', 'color' => 'أسود',       'sku' => 'PNT-001-34-B',  'buy' => 87, 'sell' => 185],
                ],
            ],
            [
                'sku'   => 'PNT-002', 'name' => 'بنطلون رياضي',
                'cat'   => 'PANTS', 'brand' => 'ADIDAS',
                'buy'   => 55, 'sell' => 109,
                'tax'   => 15, 'alert' => 5,
                'variants' => [
                    ['size' => 'S',  'color' => 'أسود',  'sku' => 'PNT-002-S-B',  'buy' => 55, 'sell' => 109],
                    ['size' => 'M',  'color' => 'أسود',  'sku' => 'PNT-002-M-B',  'buy' => 55, 'sell' => 109],
                    ['size' => 'L',  'color' => 'أسود',  'sku' => 'PNT-002-L-B',  'buy' => 55, 'sell' => 109],
                    ['size' => 'XL', 'color' => 'أسود',  'sku' => 'PNT-002-XL-B', 'buy' => 58, 'sell' => 115],
                    ['size' => 'M',  'color' => 'رمادي', 'sku' => 'PNT-002-M-G',  'buy' => 55, 'sell' => 109],
                    ['size' => 'L',  'color' => 'رمادي', 'sku' => 'PNT-002-L-G',  'buy' => 55, 'sell' => 109],
                ],
            ],
            // Dresses
            [
                'sku'   => 'DRS-001', 'name' => 'فستان سهرة كلاسيك',
                'cat'   => 'DRESSES', 'brand' => 'ZARA',
                'buy'   => 120, 'sell' => 249,
                'tax'   => 15, 'alert' => 2,
                'variants' => [
                    ['size' => 'XS', 'color' => 'أسود',  'sku' => 'DRS-001-XS-B', 'buy' => 120, 'sell' => 249],
                    ['size' => 'S',  'color' => 'أسود',  'sku' => 'DRS-001-S-B',  'buy' => 120, 'sell' => 249],
                    ['size' => 'M',  'color' => 'أسود',  'sku' => 'DRS-001-M-B',  'buy' => 120, 'sell' => 249],
                    ['size' => 'L',  'color' => 'أسود',  'sku' => 'DRS-001-L-B',  'buy' => 122, 'sell' => 255],
                    ['size' => 'S',  'color' => 'أحمر',  'sku' => 'DRS-001-S-R',  'buy' => 120, 'sell' => 249],
                    ['size' => 'M',  'color' => 'أحمر',  'sku' => 'DRS-001-M-R',  'buy' => 120, 'sell' => 249],
                ],
            ],
            // Jackets
            [
                'sku'   => 'JKT-001', 'name' => 'جاكيت شتوي بهود',
                'cat'   => 'JACKETS', 'brand' => 'NIKE',
                'buy'   => 150, 'sell' => 299,
                'tax'   => 15, 'alert' => 2,
                'variants' => [
                    ['size' => 'S',  'color' => 'كحلي', 'sku' => 'JKT-001-S-N',  'buy' => 150, 'sell' => 299],
                    ['size' => 'M',  'color' => 'كحلي', 'sku' => 'JKT-001-M-N',  'buy' => 150, 'sell' => 299],
                    ['size' => 'L',  'color' => 'كحلي', 'sku' => 'JKT-001-L-N',  'buy' => 152, 'sell' => 309],
                    ['size' => 'XL', 'color' => 'كحلي', 'sku' => 'JKT-001-XL-N', 'buy' => 155, 'sell' => 319],
                    ['size' => 'M',  'color' => 'أسود', 'sku' => 'JKT-001-M-B',  'buy' => 150, 'sell' => 299],
                    ['size' => 'L',  'color' => 'أسود', 'sku' => 'JKT-001-L-B',  'buy' => 152, 'sell' => 309],
                ],
            ],
            // Kids
            [
                'sku'   => 'KDS-001', 'name' => 'بدلة أطفال رياضية',
                'cat'   => 'KIDS', 'brand' => 'ADIDAS',
                'buy'   => 45, 'sell' => 89,
                'tax'   => 15, 'alert' => 5,
                'variants' => [
                    ['size' => '2Y', 'color' => 'أزرق',  'sku' => 'KDS-001-2Y-BL',  'buy' => 45, 'sell' => 89],
                    ['size' => '4Y', 'color' => 'أزرق',  'sku' => 'KDS-001-4Y-BL',  'buy' => 45, 'sell' => 89],
                    ['size' => '6Y', 'color' => 'أزرق',  'sku' => 'KDS-001-6Y-BL',  'buy' => 45, 'sell' => 89],
                    ['size' => '8Y', 'color' => 'أزرق',  'sku' => 'KDS-001-8Y-BL',  'buy' => 48, 'sell' => 95],
                    ['size' => '4Y', 'color' => 'وردي',  'sku' => 'KDS-001-4Y-PK',  'buy' => 45, 'sell' => 89],
                    ['size' => '6Y', 'color' => 'وردي',  'sku' => 'KDS-001-6Y-PK',  'buy' => 45, 'sell' => 89],
                ],
            ],
        ];

        foreach ($productsData as $pd) {
            // Create / find product
            $product = Product::firstOrCreate(
                ['sku' => $pd['sku']],
                [
                    'name'           => $pd['name'],
                    'category_id'    => $cats[$pd['cat']]->id,
                    'brand_id'       => $brands[$pd['brand']]->id,
                    'unit_id'        => $unitPiece->id,
                    'purchase_price' => $pd['buy'],
                    'selling_price'  => $pd['sell'],
                    'tax_rate'       => $pd['tax'],
                    'alert_quantity' => $pd['alert'],
                    'is_active'      => true,
                    'has_variants'   => true,
                    'has_expiry'     => false,
                    'sort'           => 1,
                ]
            );

            $firstVariantId = null;

            foreach ($pd['variants'] as $i => $vd) {
                $variant = ProductVariant::firstOrCreate(
                    ['sku' => $vd['sku']],
                    [
                        'product_id'     => $product->id,
                        'attributes'     => [
                            $attrSize->id  => $vd['size'],
                            $attrColor->id => $vd['color'],
                        ],
                        'purchase_price' => $vd['buy'],
                        'selling_price'  => $vd['sell'],
                        'cost_price'     => $vd['buy'],
                        'alert_quantity' => $pd['alert'],
                        'is_active'      => true,
                        'is_default'     => $i === 0,
                        'sort_order'     => $i + 1,
                    ]
                );

                if ($i === 0) {
                    $firstVariantId = $variant->id;
                }

                // Seed inventory (10 units each)
                InventoryItem::firstOrCreate(
                    ['warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'variant_id' => $variant->id],
                    ['quantity' => 10]
                );
            }

            // Set default_variant_id if column exists
            if ($firstVariantId && \Illuminate\Support\Facades\Schema::hasColumn('products', 'default_variant_id')) {
                $product->update(['default_variant_id' => $firstVariantId]);
            }
        }

        $this->command->info('✔ ClothesShop seeded: ' . count($productsData) . ' products with size/color variants.');
    }
}
