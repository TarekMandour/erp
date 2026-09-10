<?php

namespace Database\Seeders\ClothesShop;

use Illuminate\Database\Seeder;
use App\Models\Finance\PostingScenario;
use Illuminate\Support\Facades\DB;

class PostingScenarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        PostingScenario::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $scenarios = [

            // ==================== المبيعات ====================
            [
                'code'           => 'SALE_CASH',
                'name'           => 'بيع نقدي',
                'operation_type' => 'sales',
                'priority'       => 10,
                'description'    => 'فاتورة مبيعات مدفوعة كاش',
                'is_active'      => 1,
            ],
            [
                'code'           => 'SALE_CREDIT',
                'name'           => 'بيع آجل',
                'operation_type' => 'sales',
                'priority'       => 10,
                'description'    => 'فاتورة مبيعات آجلة (على ذمة العميل)',
                'is_active'      => 1,
            ],
            [
                'code'           => 'SALE_RETURN_CASH',
                'name'           => 'مرتجع مبيعات نقدي',
                'operation_type' => 'sales_return',
                'priority'       => 10,
                'description'    => 'استرجاع منتج مع رد نقدي للعميل',
                'is_active'      => 1,
            ],
            [
                'code'           => 'SALE_RETURN_CREDIT',
                'name'           => 'مرتجع مبيعات آجل',
                'operation_type' => 'sales_return',
                'priority'       => 10,
                'description'    => 'استرجاع منتج مع إضافة رصيد للعميل',
                'is_active'      => 1,
            ],

            // ==================== المشتريات ====================
            [
                'code'           => 'PURCHASE_CASH',
                'name'           => 'شراء نقدي',
                'operation_type' => 'purchase',
                'priority'       => 10,
                'description'    => 'فاتورة مشتريات مدفوعة كاش',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PURCHASE_CREDIT',
                'name'           => 'شراء آجل',
                'operation_type' => 'purchase',
                'priority'       => 10,
                'description'    => 'فاتورة مشتريات آجلة (على ذمة المورد)',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PURCHASE_INSTALLMENTS',
                'name'           => 'شراء بالتقسيط',
                'operation_type' => 'purchase',
                'priority'       => 10,
                'description'    => 'فاتورة مشتريات بالتقسيط',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PURCHASE_WALLET',
                'name'           => 'شراء بالمحفظة الإلكترونية',
                'operation_type' => 'purchase',
                'priority'       => 10,
                'description'    => 'فاتورة مشتريات مدفوعة من المحفظة الإلكترونية',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PURCHASE_BANK_ONLINE',
                'name'           => 'شراء عن طريق البنك أونلاين',
                'operation_type' => 'purchase',
                'priority'       => 10,
                'description'    => 'فاتورة مشتريات مدفوعة عن طريق البنك أونلاين',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PURCHASE_BANK_DIRECT',
                'name'           => 'شراء عن طريق البنك مباشر',
                'operation_type' => 'purchase',
                'priority'       => 10,
                'description'    => 'فاتورة مشتريات مدفوعة عن طريق البنك مباشرة',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PURCHASE_CHECK',
                'name'           => 'شراء بشيك',
                'operation_type' => 'purchase',
                'priority'       => 10,
                'description'    => 'فاتورة مشتريات مدفوعة بشيك',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PURCHASE_RETURN_CASH',
                'name'           => 'مرتجع مشتريات نقدي',
                'operation_type' => 'purchase_return',
                'priority'       => 10,
                'description'    => 'استرجاع منتج لمورد مع استرداد نقدي',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PURCHASE_RETURN_CREDIT',
                'name'           => 'مرتجع مشتريات آجل',
                'operation_type' => 'purchase_return',
                'priority'       => 10,
                'description'    => 'استرجاع منتج لمورد مع تخفيض رصيده',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PURCHASE_RETURN_INSTALLMENTS',
                'name'           => 'مرتجع مشتريات بالتقسيط',
                'operation_type' => 'purchase_return',
                'priority'       => 10,
                'description'    => 'استرجاع منتج لمورد مع تخفيض رصيد الأقساط',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PURCHASE_RETURN_WALLET',
                'name'           => 'مرتجع مشتريات بالمحفظة الإلكترونية',
                'operation_type' => 'purchase_return',
                'priority'       => 10,
                'description'    => 'استرجاع مبلغ المرتجع إلى المحفظة الإلكترونية',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PURCHASE_RETURN_BANK_ONLINE',
                'name'           => 'مرتجع مشتريات البنك أونلاين',
                'operation_type' => 'purchase_return',
                'priority'       => 10,
                'description'    => 'استرجاع مبلغ المرتجع عبر البنك أونلاين',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PURCHASE_RETURN_BANK_DIRECT',
                'name'           => 'مرتجع مشتريات البنك مباشر',
                'operation_type' => 'purchase_return',
                'priority'       => 10,
                'description'    => 'استرجاع مبلغ المرتجع عبر البنك مباشرة',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PURCHASE_RETURN_CHECK',
                'name'           => 'مرتجع مشتريات بشيك',
                'operation_type' => 'purchase_return',
                'priority'       => 10,
                'description'    => 'تسوية مرتجع مشتريات مرتبط بشيك',
                'is_active'      => 1,
            ],

            // ==================== المدفوعات والمقبوضات ====================
            [
                'code'           => 'RECEIPT_CUSTOMER_CASH',
                'name'           => 'قبض من عميل نقداً',
                'operation_type' => 'customer_payment',
                'priority'       => 15,
                'description'    => 'استلام نقدية من عميل لتسوية ذمة مدينة',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PAYMENT_SUPPLIER_CASH',
                'name'           => 'دفع لمورد نقداً',
                'operation_type' => 'supplier_payment',
                'priority'       => 15,
                'description'    => 'دفع نقدي لمورد لتسوية ذمة دائنة',
                'is_active'      => 1,
            ],

            // ==================== المخزون ====================
            [
                'code'           => 'INVENTORY_ADJUSTMENT_INC',
                'name'           => 'زيادة جرد مخزون',
                'operation_type' => 'inventory_adjustment',
                'priority'       => 15,
                'description'    => 'زيادة كمية المخزون بعد الجرد',
                'is_active'      => 1,
            ],
            [
                'code'           => 'INVENTORY_ADJUSTMENT_DEC',
                'name'           => 'نقصان جرد مخزون',
                'operation_type' => 'inventory_adjustment',
                'priority'       => 15,
                'description'    => 'نقصان كمية المخزون بعد الجرد',
                'is_active'      => 1,
            ],
            [
                'code'           => 'INVENTORY_WRITE_OFF',
                'name'           => 'شطب مخزون تالف',
                'operation_type' => 'inventory_write_off',
                'priority'       => 15,
                'description'    => 'شطب منتجات تالفة أو منتهية الصلاحية',
                'is_active'      => 1,
            ],

            // ==================== المصروفات ====================
            [
                'code'           => 'EXPENSE_CASH_SALARY',
                'name'           => 'صرف رواتب وأجور',
                'operation_type' => 'expense',
                'priority'       => 20,
                'description'    => 'صرف رواتب الموظفين والعمالة',
                'is_active'      => 1,
            ],
            [
                'code'           => 'EXPENSE_CASH_RENT',
                'name'           => 'دفع إيجار المحل',
                'operation_type' => 'expense',
                'priority'       => 20,
                'description'    => 'دفع إيجار المحل أو المستودع',
                'is_active'      => 1,
            ],
            [
                'code'           => 'EXPENSE_CASH_UTILITIES',
                'name'           => 'دفع كهرباء ومياه',
                'operation_type' => 'expense',
                'priority'       => 20,
                'description'    => 'دفع فواتير الكهرباء والمياه',
                'is_active'      => 1,
            ],
            [
                'code'           => 'EXPENSE_CASH_MARKETING',
                'name'           => 'مصروف تسويق وإعلان',
                'operation_type' => 'expense',
                'priority'       => 20,
                'description'    => 'صرف مبالغ للتسويق والإعلان',
                'is_active'      => 1,
            ],
            [
                'code'           => 'EXPENSE_CASH_SHIPPING',
                'name'           => 'مصروف شحن وتوصيل',
                'operation_type' => 'expense',
                'priority'       => 20,
                'description'    => 'دفع تكاليف الشحن والتوصيل',
                'is_active'      => 1,
            ],
            [
                'code'           => 'EXPENSE_CASH_MISC',
                'name'           => 'مصروفات متنوعة',
                'operation_type' => 'expense',
                'priority'       => 20,
                'description'    => 'مصروفات أخرى متنوعة',
                'is_active'      => 1,
            ],
            [
                'code'           => 'EXPENSE_CASH_GENERAL',
                'name'           => 'مصروف نقدي عام',
                'operation_type' => 'expense',
                'priority'       => 20,
                'description'    => 'صرف نقدي لمصروف (ديناميكي)',
                'is_active'      => 1,
            ],

            // ==================== الأصول الثابتة ====================
            [
                'code'           => 'ASSET_PURCHASE_CASH',
                'name'           => 'شراء أصل ثابت نقداً',
                'operation_type' => 'asset_purchase',
                'priority'       => 25,
                'description'    => 'شراء أثاث أو معدات أو أجهزة نقداً',
                'is_active'      => 1,
            ],
            [
                'code'           => 'ASSET_DEPRECIATION',
                'name'           => 'إهلاك أصل ثابت',
                'operation_type' => 'asset_depreciation',
                'priority'       => 25,
                'description'    => 'تسجيل قسط إهلاك الأصول الثابتة',
                'is_active'      => 1,
            ],

            // ==================== ضريبة القيمة المضافة ====================
            [
                'code'           => 'VAT_PAYMENT',
                'name'           => 'سداد ضريبة القيمة المضافة',
                'operation_type' => 'vat_payment',
                'priority'       => 30,
                'description'    => 'سداد صافي ضريبة القيمة المضافة للجهة الحكومية',
                'is_active'      => 1,
            ],

            // ==================== رأس المال ====================
            [
                'code'           => 'CAPITAL_INCREASE_CASH',
                'name'           => 'زيادة رأس المال نقدي',
                'operation_type' => 'capital_increase',
                'priority'       => 45,
                'description'    => 'ضخ رأس مال نقدي في الشركة',
                'is_active'      => 1,
            ],
            [
                'code'           => 'OWNER_WITHDRAWAL',
                'name'           => 'سحب المالك',
                'operation_type' => 'salary',
                'priority'       => 45,
                'description'    => 'سحب المالك من الجاري الخاص به',
                'is_active'      => 1,
            ],
            [
                'code'           => 'PROFIT_TRANSFER',
                'name'           => 'ترحيل الأرباح',
                'operation_type' => 'profit_transfer',
                'priority'       => 45,
                'description'    => 'ترحيل صافي الربح إلى الأرباح المبقاة',
                'is_active'      => 1,
            ],

            // ==================== الخصومات ====================
            [
                'code'           => 'DISCOUNT_GIVEN',
                'name'           => 'خصم ممنوح للعميل',
                'operation_type' => 'sales',
                'priority'       => 10,
                'description'    => 'تسجيل خصم ممنوح على فاتورة بيع',
                'is_active'      => 1,
            ],
            [
                'code'           => 'DISCOUNT_RECEIVED',
                'name'           => 'خصم مكتسب من المورد',
                'operation_type' => 'purchase',
                'priority'       => 10,
                'description'    => 'تسجيل خصم مكتسب من فاتورة شراء',
                'is_active'      => 1,
            ],

            // ==================== التسويات والأرصدة الافتتاحية ====================
            [
                'code'           => 'OPENING_BALANCE',
                'name'           => 'أرصدة افتتاحية',
                'operation_type' => 'opening_balance',
                'priority'       => 5,
                'description'    => 'ترحيل أرصدة بداية السنة المالية',
                'is_active'      => 1,
            ],
            [
                'code'           => 'ADJUSTMENT',
                'name'           => 'تسوية عامة',
                'operation_type' => 'adjustment',
                'priority'       => 50,
                'description'    => 'تسوية محاسبية عامة',
                'is_active'      => 1,
            ],
            [
                'code'           => 'CLOSING_ENTRY',
                'name'           => 'قيد إقفال نهاية السنة',
                'operation_type' => 'closing_entry',
                'priority'       => 5,
                'description'    => 'إقفال حسابات الإيرادات والمصروفات في نهاية الفترة',
                'is_active'      => 1,
            ],
            [
                'code'           => 'REVERSAL',
                'name'           => 'عكس قيد',
                'operation_type' => 'reversal',
                'priority'       => 50,
                'description'    => 'عكس قيد محاسبي سابق',
                'is_active'      => 1,
            ],
            [
                'code'           => 'CORRECTION',
                'name'           => 'تصحيح قيد',
                'operation_type' => 'correction',
                'priority'       => 50,
                'description'    => 'تصحيح خطأ محاسبي',
                'is_active'      => 1,
            ],
        ];

        foreach ($scenarios as $scenario) {
            PostingScenario::create($scenario);
        }

        $this->command->info('PostingScenario seeded successfully! Total: ' . count($scenarios));
    }
}