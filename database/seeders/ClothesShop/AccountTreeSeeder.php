<?php

namespace Database\Seeders\ClothesShop;

use Illuminate\Database\Seeder;
use App\Models\Finance\AccountTree;
use Illuminate\Support\Facades\DB;

class AccountTreeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ─── Root accounts (Level 0) ───
        $assets      = AccountTree::firstOrCreate(['code' => '1000'], ['name' => 'الأصول', 'type' => 'asset', 'level' => 0, 'is_active' => true]);
        $liabilities = AccountTree::firstOrCreate(['code' => '2000'], ['name' => 'الخصوم', 'type' => 'liability', 'level' => 0, 'is_active' => true]);
        $equity      = AccountTree::firstOrCreate(['code' => '3000'], ['name' => 'حقوق الملكية', 'type' => 'equity', 'level' => 0, 'is_active' => true]);
        $revenue     = AccountTree::firstOrCreate(['code' => '4000'], ['name' => 'الإيرادات', 'type' => 'revenue', 'level' => 0, 'is_active' => true]);
        $expenses    = AccountTree::firstOrCreate(['code' => '5000'], ['name' => 'المصروفات', 'type' => 'expense', 'level' => 0, 'is_active' => true]);

        // ─── Assets (Level 1) ───
        $currentAssets = AccountTree::firstOrCreate(['code' => '1100'], ['name' => 'الأصول المتداولة', 'parent_id' => $assets->id, 'type' => 'asset', 'level' => 1, 'is_active' => true]);
        $fixedAssets   = AccountTree::firstOrCreate(['code' => '1200'], ['name' => 'الأصول الثابتة', 'parent_id' => $assets->id, 'type' => 'asset', 'level' => 1, 'is_active' => true]);

        // ─── Current Assets (Level 2) ───
        AccountTree::firstOrCreate(['code' => '1101'], ['name' => 'الصندوق (النقدية)', 'parent_id' => $currentAssets->id, 'type' => 'asset', 'level' => 2, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '1102'], ['name' => 'البنك', 'parent_id' => $currentAssets->id, 'type' => 'asset', 'level' => 2, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '1103'], ['name' => 'المخزون', 'parent_id' => $currentAssets->id, 'type' => 'asset', 'level' => 2, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '1104'], ['name' => 'العملاء (ذمم مدينة)', 'parent_id' => $currentAssets->id, 'type' => 'asset', 'level' => 2, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '1105'], ['name' => 'مصروفات مدفوعة مقدماً', 'parent_id' => $currentAssets->id, 'type' => 'asset', 'level' => 2, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '1106'], ['name' => 'المحافظ الإلكترونية', 'parent_id' => $currentAssets->id, 'type' => 'asset', 'level' => 2, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '1107'], ['name' => 'البنك - أونلاين', 'parent_id' => $currentAssets->id, 'type' => 'asset', 'level' => 2, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '1108'], ['name' => 'البنك - مباشر', 'parent_id' => $currentAssets->id, 'type' => 'asset', 'level' => 2, 'is_active' => true]);

        // ─── Fixed Assets (Level 2) ───
        AccountTree::firstOrCreate(['code' => '1201'], ['name' => 'أثاث ومعدات', 'parent_id' => $fixedAssets->id, 'type' => 'asset', 'level' => 2, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '1202'], ['name' => 'ديكورات المحل', 'parent_id' => $fixedAssets->id, 'type' => 'asset', 'level' => 2, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '1203'], ['name' => 'أجهزة حاسب ونقاط بيع', 'parent_id' => $fixedAssets->id, 'type' => 'asset', 'level' => 2, 'is_active' => true]);

        // ─── Liabilities (Level 1) ───
        $currentLiab = AccountTree::firstOrCreate(['code' => '2100'], ['name' => 'الخصوم المتداولة', 'parent_id' => $liabilities->id, 'type' => 'liability', 'level' => 1, 'is_active' => true]);

        // ─── Current Liabilities (Level 2) ───
        AccountTree::firstOrCreate(['code' => '2101'], ['name' => 'الموردين (ذمم دائنة)', 'parent_id' => $currentLiab->id, 'type' => 'liability', 'level' => 2, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '2102'], ['name' => 'ضريبة القيمة المضافة', 'parent_id' => $currentLiab->id, 'type' => 'liability', 'level' => 2, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '2103'], ['name' => 'مصروفات مستحقة', 'parent_id' => $currentLiab->id, 'type' => 'liability', 'level' => 2, 'is_active' => true]);

        // ─── Equity (Level 1) ───
        AccountTree::firstOrCreate(['code' => '3001'], ['name' => 'رأس المال', 'parent_id' => $equity->id, 'type' => 'equity', 'level' => 1, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '3002'], ['name' => 'أرباح مبقاة', 'parent_id' => $equity->id, 'type' => 'equity', 'level' => 1, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '3003'], ['name' => 'جاري المالك', 'parent_id' => $equity->id, 'type' => 'equity', 'level' => 1, 'is_active' => true]);

        // ─── Revenue (Level 1) ───
        AccountTree::firstOrCreate(['code' => '4001'], ['name' => 'إيرادات المبيعات', 'parent_id' => $revenue->id, 'type' => 'revenue', 'level' => 1, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '4002'], ['name' => 'إيرادات أخرى', 'parent_id' => $revenue->id, 'type' => 'revenue', 'level' => 1, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '4003'], ['name' => 'خصم مكتسب', 'parent_id' => $revenue->id, 'type' => 'revenue', 'level' => 1, 'is_active' => true]);

        // ─── Expenses (Level 1) ───
        AccountTree::firstOrCreate(['code' => '5001'], ['name' => 'تكلفة البضاعة المباعة', 'parent_id' => $expenses->id, 'type' => 'expense', 'level' => 1, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '5002'], ['name' => 'رواتب وأجور', 'parent_id' => $expenses->id, 'type' => 'expense', 'level' => 1, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '5003'], ['name' => 'إيجار المحل', 'parent_id' => $expenses->id, 'type' => 'expense', 'level' => 1, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '5004'], ['name' => 'كهرباء ومياه', 'parent_id' => $expenses->id, 'type' => 'expense', 'level' => 1, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '5005'], ['name' => 'مصروفات تسويق وإعلان', 'parent_id' => $expenses->id, 'type' => 'expense', 'level' => 1, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '5006'], ['name' => 'مصروفات شحن وتوصيل', 'parent_id' => $expenses->id, 'type' => 'expense', 'level' => 1, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '5007'], ['name' => 'مصروفات متنوعة', 'parent_id' => $expenses->id, 'type' => 'expense', 'level' => 1, 'is_active' => true]);
        AccountTree::firstOrCreate(['code' => '5008'], ['name' => 'خصم ممنوح', 'parent_id' => $expenses->id, 'type' => 'expense', 'level' => 1, 'is_active' => true]);

    }
}