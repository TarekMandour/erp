<?php

namespace Database\Seeders\ClothesShop;

use Illuminate\Database\Seeder;
use App\Models\Finance\PostingScenario;
use App\Models\Finance\PostingRule;
use App\Models\Finance\AccountTree;
use Illuminate\Support\Facades\DB;

class PostingRuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        PostingRule::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ─── حسابات الدليل المحاسبي (مطابقة لـ AccountTreeSeeder) ───
        $cash         = AccountTree::where('code', '1101')->first(); // الصندوق (النقدية)
        $bank         = AccountTree::where('code', '1102')->first(); // البنك
        $inventory    = AccountTree::where('code', '1103')->first(); // المخزون
        $customers    = AccountTree::where('code', '1104')->first(); // العملاء (ذمم مدينة)
        $prepaidExp   = AccountTree::where('code', '1105')->first(); // مصروفات مدفوعة مقدماً
        $furniture    = AccountTree::where('code', '1201')->first(); // أثاث ومعدات
        $decor        = AccountTree::where('code', '1202')->first(); // ديكورات المحل
        $computers    = AccountTree::where('code', '1203')->first(); // أجهزة حاسب ونقاط بيع
        $suppliers    = AccountTree::where('code', '2101')->first(); // الموردين (ذمم دائنة)
        $vatPayable   = AccountTree::where('code', '2102')->first(); // ضريبة القيمة المضافة
        $accrued      = AccountTree::where('code', '2103')->first(); // مصروفات مستحقة
        $capital      = AccountTree::where('code', '3001')->first(); // رأس المال
        $retained     = AccountTree::where('code', '3002')->first(); // أرباح مبقاة
        $ownerDrawing = AccountTree::where('code', '3003')->first(); // جاري المالك
        $salesRev     = AccountTree::where('code', '4001')->first(); // إيرادات المبيعات
        $otherRev     = AccountTree::where('code', '4002')->first(); // إيرادات أخرى
        $discountEarn = AccountTree::where('code', '4003')->first(); // خصم مكتسب
        $cogs         = AccountTree::where('code', '5001')->first(); // تكلفة البضاعة المباعة
        $salaryExp    = AccountTree::where('code', '5002')->first(); // رواتب وأجور
        $rentExp      = AccountTree::where('code', '5003')->first(); // إيجار المحل
        $utilityExp   = AccountTree::where('code', '5004')->first(); // كهرباء ومياه
        $marketingExp = AccountTree::where('code', '5005')->first(); // مصروفات تسويق وإعلان
        $shippingExp  = AccountTree::where('code', '5006')->first(); // مصروفات شحن وتوصيل
        $miscExp      = AccountTree::where('code', '5007')->first(); // مصروفات متنوعة
        $discountGiven= AccountTree::where('code', '5008')->first(); // خصم ممنوح

        $rules = [];

        // ==================== 1. بيع نقدي (SALE_CASH) ====================
        // مجموعة 1: قيد الإيراد
        //   ح/ الصندوق (مدين - الإجمالي شامل الضريبة)
        //     ح/ إيرادات المبيعات (دائن - قبل الضريبة)
        //     ح/ ضريبة القيمة المضافة (دائن - الضريبة)
        // مجموعة 2: قيد تكلفة البضاعة
        //   ح/ تكلفة البضاعة المباعة (مدين)
        //     ح/ المخزون (دائن)
        $scenario = PostingScenario::where('code', 'SALE_CASH')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $cash?->id,      'credit_account_id' => null,          'amount_type' => 'total',         'amount_field' => 'total',    'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,             'credit_account_id' => $salesRev?->id, 'amount_type' => 'subtotal',      'amount_field' => 'subtotal', 'sort_order' => 2, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,             'credit_account_id' => $vatPayable?->id,'amount_type' => 'tax',           'amount_field' => 'tax',      'sort_order' => 3, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 2, 'debit_account_id' => $cogs?->id,      'credit_account_id' => null,          'amount_type' => 'quantity_cost', 'amount_field' => null,       'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 2, 'debit_account_id' => null,             'credit_account_id' => $inventory?->id,'amount_type' => 'quantity_cost', 'amount_field' => null,       'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 2. بيع آجل (SALE_CREDIT) ====================
        // مجموعة 1: قيد الإيراد
        //   ح/ العملاء (مدين - الإجمالي)
        //     ح/ إيرادات المبيعات (دائن - الصافي)
        //     ح/ ضريبة القيمة المضافة (دائن - الضريبة)
        // مجموعة 2: قيد التكلفة
        //   ح/ تكلفة البضاعة المباعة (مدين)
        //     ح/ المخزون (دائن)
        $scenario = PostingScenario::where('code', 'SALE_CREDIT')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $customers?->id, 'credit_account_id' => null,           'amount_type' => 'total',         'amount_field' => 'total',    'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $salesRev?->id,  'amount_type' => 'subtotal',      'amount_field' => 'subtotal', 'sort_order' => 2, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $vatPayable?->id, 'amount_type' => 'tax',           'amount_field' => 'tax',      'sort_order' => 3, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 2, 'debit_account_id' => $cogs?->id,     'credit_account_id' => null,           'amount_type' => 'quantity_cost', 'amount_field' => null,       'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 2, 'debit_account_id' => null,            'credit_account_id' => $inventory?->id, 'amount_type' => 'quantity_cost', 'amount_field' => null,       'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 3. مرتجع مبيعات نقدي (SALE_RETURN_CASH) ====================
        // عكس قيد بيع نقدي
        //   ح/ إيرادات المبيعات (مدين - الصافي)
        //   ح/ ضريبة القيمة المضافة (مدين - الضريبة)
        //     ح/ الصندوق (دائن - الإجمالي)
        // + إعادة المخزون: ح/المخزون مدين / ح/تكلفة البضاعة دائن
        $scenario = PostingScenario::where('code', 'SALE_RETURN_CASH')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $salesRev?->id,  'credit_account_id' => null,          'amount_type' => 'subtotal',      'amount_field' => 'subtotal', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $vatPayable?->id,'credit_account_id' => null,          'amount_type' => 'tax',           'amount_field' => 'tax',      'sort_order' => 2, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $cash?->id,    'amount_type' => 'total',         'amount_field' => 'total',    'sort_order' => 3, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 2, 'debit_account_id' => $inventory?->id,'credit_account_id' => null,          'amount_type' => 'quantity_cost', 'amount_field' => null,       'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 2, 'debit_account_id' => null,            'credit_account_id' => $cogs?->id,    'amount_type' => 'quantity_cost', 'amount_field' => null,       'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 4. مرتجع مبيعات آجل (SALE_RETURN_CREDIT) ====================
        // عكس قيد بيع آجل
        //   ح/ إيرادات المبيعات (مدين - الصافي)
        //   ح/ ضريبة القيمة المضافة (مدين - الضريبة)
        //     ح/ العملاء (دائن - الإجمالي) → تخفيض الذمة
        // + إعادة المخزون
        $scenario = PostingScenario::where('code', 'SALE_RETURN_CREDIT')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $salesRev?->id,  'credit_account_id' => null,           'amount_type' => 'subtotal',      'amount_field' => 'subtotal', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $vatPayable?->id,'credit_account_id' => null,           'amount_type' => 'tax',           'amount_field' => 'tax',      'sort_order' => 2, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $customers?->id, 'amount_type' => 'total',         'amount_field' => 'total',    'sort_order' => 3, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 2, 'debit_account_id' => $inventory?->id,'credit_account_id' => null,           'amount_type' => 'quantity_cost', 'amount_field' => null,       'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 2, 'debit_account_id' => null,            'credit_account_id' => $cogs?->id,     'amount_type' => 'quantity_cost', 'amount_field' => null,       'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 5. شراء نقدي (PURCHASE_CASH) ====================
        //   ح/ المخزون (مدين - الصافي)
        //   ح/ ضريبة القيمة المضافة (مدين - الضريبة)
        //     ح/ الصندوق (دائن - الإجمالي)
        $scenario = PostingScenario::where('code', 'PURCHASE_CASH')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $inventory?->id, 'credit_account_id' => null,          'amount_type' => 'subtotal', 'amount_field' => 'subtotal', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $vatPayable?->id,'credit_account_id' => null,          'amount_type' => 'tax',      'amount_field' => 'tax',      'sort_order' => 2, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $cash?->id,    'amount_type' => 'total',    'amount_field' => 'total',    'sort_order' => 3, 'is_required' => 1];
        }

        // ==================== 6. شراء آجل (PURCHASE_CREDIT) ====================
        //   ح/ المخزون (مدين - الصافي)
        //   ح/ ضريبة القيمة المضافة (مدين - الضريبة)
        //     ح/ الموردين (دائن - الإجمالي)
        $scenario = PostingScenario::where('code', 'PURCHASE_CREDIT')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $inventory?->id, 'credit_account_id' => null,            'amount_type' => 'subtotal', 'amount_field' => 'subtotal', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $vatPayable?->id,'credit_account_id' => null,            'amount_type' => 'tax',      'amount_field' => 'tax',      'sort_order' => 2, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $suppliers?->id, 'amount_type' => 'total',    'amount_field' => 'total',    'sort_order' => 3, 'is_required' => 1];
        }

        // ==================== 7. مرتجع مشتريات نقدي (PURCHASE_RETURN_CASH) ====================
        //   ح/ الصندوق (مدين - الإجمالي)
        //     ح/ المخزون (دائن - الصافي)
        //     ح/ ضريبة القيمة المضافة (دائن - الضريبة)
        $scenario = PostingScenario::where('code', 'PURCHASE_RETURN_CASH')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $cash?->id,       'credit_account_id' => null,           'amount_type' => 'total',    'amount_field' => 'total',    'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,             'credit_account_id' => $inventory?->id, 'amount_type' => 'subtotal', 'amount_field' => 'subtotal', 'sort_order' => 2, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,             'credit_account_id' => $vatPayable?->id,'amount_type' => 'tax',      'amount_field' => 'tax',      'sort_order' => 3, 'is_required' => 1];
        }

        // ==================== 8. مرتجع مشتريات آجل (PURCHASE_RETURN_CREDIT) ====================
        //   ح/ الموردين (مدين - الإجمالي) → تخفيض الذمة
        //     ح/ المخزون (دائن - الصافي)
        //     ح/ ضريبة القيمة المضافة (دائن - الضريبة)
        $scenario = PostingScenario::where('code', 'PURCHASE_RETURN_CREDIT')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $suppliers?->id, 'credit_account_id' => null,            'amount_type' => 'total',    'amount_field' => 'total',    'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $inventory?->id,  'amount_type' => 'subtotal', 'amount_field' => 'subtotal', 'sort_order' => 2, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $vatPayable?->id, 'amount_type' => 'tax',      'amount_field' => 'tax',      'sort_order' => 3, 'is_required' => 1];
        }

        // ==================== 9. قبض من عميل نقداً (RECEIPT_CUSTOMER_CASH) ====================
        //   ح/ الصندوق (مدين)
        //     ح/ العملاء (دائن) → تسوية الذمة المدينة
        $scenario = PostingScenario::where('code', 'RECEIPT_CUSTOMER_CASH')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $cash?->id,      'credit_account_id' => null,            'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $customers?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 10. دفع لمورد نقداً (PAYMENT_SUPPLIER_CASH) ====================
        //   ح/ الموردين (مدين) → تسوية الذمة الدائنة
        //     ح/ الصندوق (دائن)
        $scenario = PostingScenario::where('code', 'PAYMENT_SUPPLIER_CASH')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $suppliers?->id, 'credit_account_id' => null,       'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $cash?->id,  'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 11. زيادة جرد مخزون (INVENTORY_ADJUSTMENT_INC) ====================
        //   ح/ المخزون (مدين)
        //     ح/ مصروفات متنوعة (دائن) → فروق الجرد
        $scenario = PostingScenario::where('code', 'INVENTORY_ADJUSTMENT_INC')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $inventory?->id, 'credit_account_id' => null,         'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $miscExp?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 12. نقصان جرد مخزون (INVENTORY_ADJUSTMENT_DEC) ====================
        //   ح/ مصروفات متنوعة (مدين) → عجز الجرد
        //     ح/ المخزون (دائن)
        $scenario = PostingScenario::where('code', 'INVENTORY_ADJUSTMENT_DEC')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $miscExp?->id,   'credit_account_id' => null,           'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $inventory?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 13. شطب مخزون تالف (INVENTORY_WRITE_OFF) ====================
        //   ح/ مصروفات متنوعة (مدين) → خسارة البضاعة التالفة
        //     ح/ المخزون (دائن)
        $scenario = PostingScenario::where('code', 'INVENTORY_WRITE_OFF')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $miscExp?->id,  'credit_account_id' => null,           'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,           'credit_account_id' => $inventory?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 14. صرف رواتب وأجور (EXPENSE_CASH_SALARY) ====================
        //   ح/ رواتب وأجور (مدين)
        //     ح/ الصندوق (دائن)
        $scenario = PostingScenario::where('code', 'EXPENSE_CASH_SALARY')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $salaryExp?->id, 'credit_account_id' => null,       'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $cash?->id,  'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 15. دفع إيجار المحل (EXPENSE_CASH_RENT) ====================
        //   ح/ إيجار المحل (مدين)
        //     ح/ الصندوق (دائن)
        $scenario = PostingScenario::where('code', 'EXPENSE_CASH_RENT')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $rentExp?->id,  'credit_account_id' => null,       'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,           'credit_account_id' => $cash?->id,  'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 16. دفع كهرباء ومياه (EXPENSE_CASH_UTILITIES) ====================
        //   ح/ كهرباء ومياه (مدين)
        //     ح/ الصندوق (دائن)
        $scenario = PostingScenario::where('code', 'EXPENSE_CASH_UTILITIES')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $utilityExp?->id, 'credit_account_id' => null,      'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,             'credit_account_id' => $cash?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 17. مصروف تسويق وإعلان (EXPENSE_CASH_MARKETING) ====================
        //   ح/ مصروفات تسويق وإعلان (مدين)
        //     ح/ الصندوق (دائن)
        $scenario = PostingScenario::where('code', 'EXPENSE_CASH_MARKETING')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $marketingExp?->id, 'credit_account_id' => null,      'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,               'credit_account_id' => $cash?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 18. مصروف شحن وتوصيل (EXPENSE_CASH_SHIPPING) ====================
        //   ح/ مصروفات شحن وتوصيل (مدين)
        //     ح/ الصندوق (دائن)
        $scenario = PostingScenario::where('code', 'EXPENSE_CASH_SHIPPING')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $shippingExp?->id, 'credit_account_id' => null,      'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,              'credit_account_id' => $cash?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 19. مصروفات متنوعة (EXPENSE_CASH_MISC) ====================
        //   ح/ مصروفات متنوعة (مدين)
        //     ح/ الصندوق (دائن)
        $scenario = PostingScenario::where('code', 'EXPENSE_CASH_MISC')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $miscExp?->id,  'credit_account_id' => null,       'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,           'credit_account_id' => $cash?->id,  'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 20. مصروف نقدي عام ديناميكي (EXPENSE_CASH_GENERAL) ====================
        //   ح/ [حساب المصروف - يُحدد ديناميكياً] (مدين)
        //     ح/ الصندوق (دائن)
        $scenario = PostingScenario::where('code', 'EXPENSE_CASH_GENERAL')->first();
        if ($scenario) {
            $rules[] = [
                'scenario_id'      => $scenario->id,
                'rule_group'       => 1,
                'debit_account_id' => null, // يُحدد ديناميكياً من طلب المستخدم
                'credit_account_id'=> null,
                'amount_type'      => 'total',
                'amount_field'     => 'amount',
                'sort_order'       => 1,
                'is_required'      => 1,
                'conditions'       => json_encode(['debit_account' => ['source' => 'voucher_details', 'field' => 'expense_account_id']]),
            ];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null, 'credit_account_id' => $cash?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 21. شراء أصل ثابت نقداً (ASSET_PURCHASE_CASH) ====================
        //   ح/ أثاث ومعدات (مدين) [أو أجهزة / ديكورات - ديناميكي]
        //     ح/ الصندوق (دائن)
        $scenario = PostingScenario::where('code', 'ASSET_PURCHASE_CASH')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $furniture?->id, 'credit_account_id' => null,      'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $cash?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 22. إهلاك أصل ثابت (ASSET_DEPRECIATION) ====================
        // ملاحظة: لا يوجد في الدليل حساب مجمع إهلاك منفصل.
        // يُستخدم المصروفات المتنوعة كبديل، أو يمكن إضافة حساب 1204 لاحقاً.
        //   ح/ مصروفات متنوعة (مدين - قسط الإهلاك)
        //     ح/ أثاث ومعدات (دائن - تخفيض قيمة الأصل)
        $scenario = PostingScenario::where('code', 'ASSET_DEPRECIATION')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $miscExp?->id,   'credit_account_id' => null,          'amount_type' => 'fixed', 'amount_value' => 0, 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,            'credit_account_id' => $furniture?->id,'amount_type' => 'fixed', 'amount_value' => 0, 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 23. سداد ضريبة القيمة المضافة (VAT_PAYMENT) ====================
        //   ح/ ضريبة القيمة المضافة (مدين - تصفية الضريبة المستحقة)
        //     ح/ البنك (دائن - الدفع للجهة الحكومية)
        $scenario = PostingScenario::where('code', 'VAT_PAYMENT')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $vatPayable?->id, 'credit_account_id' => null,       'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,             'credit_account_id' => $bank?->id,  'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 24. زيادة رأس المال نقدي (CAPITAL_INCREASE_CASH) ====================
        //   ح/ الصندوق (مدين) أو البنك حسب طريقة الإيداع
        //     ح/ رأس المال (دائن)
        $scenario = PostingScenario::where('code', 'CAPITAL_INCREASE_CASH')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $cash?->id,    'credit_account_id' => null,         'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,          'credit_account_id' => $capital?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 25. سحب المالك (OWNER_WITHDRAWAL) ====================
        //   ح/ جاري المالك (مدين)
        //     ح/ الصندوق (دائن)
        $scenario = PostingScenario::where('code', 'OWNER_WITHDRAWAL')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $ownerDrawing?->id, 'credit_account_id' => null,      'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,               'credit_account_id' => $cash?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 26. ترحيل الأرباح (PROFIT_TRANSFER) ====================
        //   ح/ إيرادات المبيعات (مدين - إقفال الإيرادات)
        //     ح/ أرباح مبقاة (دائن)
        // + ح/ أرباح مبقاة (مدين - تحميل المصروفات)
        //     ح/ تكلفة البضاعة المباعة (دائن) وباقي المصروفات
        $scenario = PostingScenario::where('code', 'PROFIT_TRANSFER')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $salesRev?->id, 'credit_account_id' => null,          'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,           'credit_account_id' => $retained?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 27. خصم ممنوح للعميل (DISCOUNT_GIVEN) ====================
        //   ح/ خصم ممنوح (مدين)
        //     ح/ العملاء (دائن) أو الصندوق
        $scenario = PostingScenario::where('code', 'DISCOUNT_GIVEN')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $discountGiven?->id, 'credit_account_id' => null,            'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,                'credit_account_id' => $customers?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 28. خصم مكتسب من المورد (DISCOUNT_RECEIVED) ====================
        //   ح/ الموردين (مدين)
        //     ح/ خصم مكتسب (دائن)
        $scenario = PostingScenario::where('code', 'DISCOUNT_RECEIVED')->first();
        if ($scenario) {
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $suppliers?->id,   'credit_account_id' => null,              'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null,              'credit_account_id' => $discountEarn?->id, 'amount_type' => 'total', 'amount_field' => 'amount', 'sort_order' => 2, 'is_required' => 1];
        }

        // ==================== 29. أرصدة افتتاحية (OPENING_BALANCE) ====================
        // يعمل ديناميكياً - بدون قواعد ثابتة (يُدخل يدوياً)
        // لا توجد قواعد ثابتة لهذا السيناريو - يُستخدم مع قيد يدوي

        // ==================== 30. تسوية عامة (ADJUSTMENT) ====================
        // يعمل ديناميكياً - بدون قواعد ثابتة

        // ==================== 31. قيد إقفال نهاية السنة (CLOSING_ENTRY) ====================
        //   ح/ إيرادات المبيعات (مدين)
        //   ح/ إيرادات أخرى (مدين)
        //   ح/ خصم مكتسب (مدين)
        //     ح/ تكلفة البضاعة المباعة (دائن)
        //     ح/ رواتب وأجور (دائن)
        //     ح/ إيجار المحل (دائن)
        //     ح/ كهرباء ومياه (دائن)
        //     ح/ مصروفات تسويق (دائن)
        //     ح/ مصروفات شحن (دائن)
        //     ح/ مصروفات متنوعة (دائن)
        //     ح/ خصم ممنوح (دائن)
        //     ح/ أرباح مبقاة (دائن - صافي الربح)
        $scenario = PostingScenario::where('code', 'CLOSING_ENTRY')->first();
        if ($scenario) {
            // إقفال الإيرادات (مدين)
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $salesRev?->id,    'credit_account_id' => null, 'amount_type' => 'formula', 'amount_field' => null, 'sort_order' => 1, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $otherRev?->id,    'credit_account_id' => null, 'amount_type' => 'formula', 'amount_field' => null, 'sort_order' => 2, 'is_required' => 0];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => $discountEarn?->id,'credit_account_id' => null, 'amount_type' => 'formula', 'amount_field' => null, 'sort_order' => 3, 'is_required' => 0];
            // إقفال المصروفات (دائن)
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null, 'credit_account_id' => $cogs?->id,         'amount_type' => 'formula', 'amount_field' => null, 'sort_order' => 4, 'is_required' => 1];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null, 'credit_account_id' => $salaryExp?->id,    'amount_type' => 'formula', 'amount_field' => null, 'sort_order' => 5, 'is_required' => 0];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null, 'credit_account_id' => $rentExp?->id,      'amount_type' => 'formula', 'amount_field' => null, 'sort_order' => 6, 'is_required' => 0];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null, 'credit_account_id' => $utilityExp?->id,   'amount_type' => 'formula', 'amount_field' => null, 'sort_order' => 7, 'is_required' => 0];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null, 'credit_account_id' => $marketingExp?->id, 'amount_type' => 'formula', 'amount_field' => null, 'sort_order' => 8, 'is_required' => 0];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null, 'credit_account_id' => $shippingExp?->id,  'amount_type' => 'formula', 'amount_field' => null, 'sort_order' => 9, 'is_required' => 0];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null, 'credit_account_id' => $miscExp?->id,      'amount_type' => 'formula', 'amount_field' => null, 'sort_order' => 10,'is_required' => 0];
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null, 'credit_account_id' => $discountGiven?->id,'amount_type' => 'formula', 'amount_field' => null, 'sort_order' => 11,'is_required' => 0];
            // صافي الربح → الأرباح المبقاة
            $rules[] = ['scenario_id' => $scenario->id, 'rule_group' => 1, 'debit_account_id' => null, 'credit_account_id' => $retained?->id,     'amount_type' => 'formula', 'amount_field' => null, 'sort_order' => 12,'is_required' => 1];
        }

        // إدراج جميع القواعد
        foreach ($rules as $rule) {
            PostingRule::create($rule);
        }

        $this->command->info('PostingRule seeded successfully! Total: ' . count($rules));
    }
}