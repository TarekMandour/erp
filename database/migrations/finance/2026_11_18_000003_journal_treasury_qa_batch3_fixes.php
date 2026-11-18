<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QA Batch 3 (القيود، الشجرة، الخزن والبنوك) — سيناريوهات 6.1، 6.2، 8.2، 8.4، 8.8، 8.9، 7.6
 *
 *  1. طبيعة الحساب (account_type) حسب النوع: الأصول والمصروفات مدين، والخصوم والملكية والإيرادات دائن
 *  2. الحسابات الناقصة (سلف، مواد خام، إنتاج تحت التشغيل، مجمع إهلاك، رواتب مستحقة، مردودات، فروقات جرد، عمولات بنكية، إهلاك، تكاليف صناعية)
 *  3. القيود اليدوية المرحّلة غير المتوازنة → مسودة (بلا أثر حتى تُصحَّح من الشاشة)
 *  4. القيود اليدوية الملغاة بلا قيد عكسي → قيد عكسي مرحّل (الإلغاء كان لا يعكس الأثر)
 *  5. حذف حركات الخزنة/البنك الناتجة عن بنود مسودة أو بمبلغ صفر أو لبنود محذوفة
 *  6. إعادة حساب أرصدة الشجرة من بنود القيود غير المسودة، وأرصدة الخزن والبنوك من حركاتها
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // 1. طبيعة الحساب
            DB::table('account_trees')->whereIn('type', ['asset', 'expense'])->update(['account_type' => 'debit']);
            DB::table('account_trees')->whereIn('type', ['liability', 'equity', 'revenue'])->update(['account_type' => 'credit']);

            // 2. الحسابات الناقصة (فقط إن كانت الشجرة مبذورة)
            $accounts = [
                ['1110', '1100', 'سلف الموظفين',               'asset',     'debit'],
                ['1111', '1100', 'مخزون مواد خام',             'asset',     'debit'],
                ['1112', '1100', 'إنتاج تحت التشغيل',          'asset',     'debit'],
                ['1204', '1200', 'مجمع الإهلاك',               'asset',     'credit'],
                ['2104', '2100', 'رواتب مستحقة',               'liability', 'credit'],
                ['4005', '4000', 'مردودات ومسموحات المبيعات',  'revenue',   'debit'],
                ['5011', '5000', 'مردودات ومسموحات المشتريات', 'expense',   'credit'],
                ['5012', '5000', 'فروقات جرد المخزون',         'expense',   'debit'],
                ['5013', '5000', 'عمولات ومصاريف بنكية',       'expense',   'debit'],
                ['5014', '5000', 'مصروف الإهلاك',              'expense',   'debit'],
                ['5015', '5000', 'تكاليف صناعية غير مباشرة',   'expense',   'debit'],
            ];
            foreach ($accounts as [$code, $parentCode, $name, $type, $nature]) {
                $parent = DB::table('account_trees')->where('code', $parentCode)->first();
                if (! $parent || DB::table('account_trees')->where('code', $code)->exists()) {
                    continue;
                }
                DB::table('account_trees')->insert([
                    'name' => $name, 'code' => $code, 'parent_id' => $parent->id, 'type' => $type,
                    'account_type' => $nature, 'level' => $parent->level + 1, 'is_active' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            // 3. قيود يدوية مرحّلة غير متوازنة → مسودة
            $unbalanced = DB::table('journal_entries as j')
                ->join('journal_entry_items as i', 'i.journal_entry_id', '=', 'j.id')
                ->whereNull('j.reference_type')
                ->where('j.status', 'posted')
                ->groupBy('j.id')
                ->havingRaw('ROUND(SUM(i.debit) - SUM(i.credit), 2) <> 0')
                ->pluck('j.id');
            DB::table('journal_entries')->whereIn('id', $unbalanced)->update(['status' => 'draft', 'updated_at' => now()]);

            // 4. قيود يدوية ملغاة بلا قيد عكسي
            $canceled = DB::table('journal_entries as j')
                ->whereNull('j.reference_type')
                ->where('j.status', 'canceled')
                ->whereNotExists(fn ($q) => $q->from('journal_entries as r')->whereColumn('r.reversed_from', 'j.id'))
                ->get();
            foreach ($canceled as $entry) {
                $reverseId = DB::table('journal_entries')->insertGetId([
                    'entry_number'  => 'REV-' . $entry->entry_number,
                    'entry_type'    => 'reversal',
                    'date'          => now()->toDateString(),
                    'description'   => 'عكس القيد الملغى رقم ' . $entry->entry_number . ' (تصحيح QA الدفعة 3)',
                    'is_reversed'   => 1,
                    'reversed_from' => $entry->id,
                    'status'        => 'posted',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                foreach (DB::table('journal_entry_items')->where('journal_entry_id', $entry->id)->get() as $item) {
                    // عبر النموذج ليُنشئ JournalEntryItemObserver حركات الخزنة/البنك المعاكسة إن وُجدت
                    \App\Models\Finance\JournalEntryItem::create([
                        'journal_entry_id' => $reverseId,
                        'account_tree_id'  => $item->account_tree_id,
                        'debit'            => $item->credit,
                        'credit'           => $item->debit,
                        'cost_center_id'   => $item->cost_center_id,
                        'description'      => $item->description,
                    ]);
                }
            }

            // 5. حركات خزنة/بنك بلا أثر حقيقي
            $itemClass = 'App\\Models\\Finance\\JournalEntryItem';
            foreach (['treasury_transactions', 'bank_transactions'] as $table) {
                $ids = DB::table($table . ' as t')
                    ->where('t.reference_type', $itemClass)
                    ->where(function ($q) {
                        $q->where('t.amount', 0)
                          ->orWhereNotExists(fn ($q) => $q->from('journal_entry_items as i')->whereColumn('i.id', 't.reference_id'))
                          ->orWhereExists(fn ($q) => $q->from('journal_entry_items as i')
                              ->join('journal_entries as j', 'j.id', '=', 'i.journal_entry_id')
                              ->whereColumn('i.id', 't.reference_id')
                              ->where('j.status', 'draft'));
                    })
                    ->pluck('t.id');
                DB::table($table)->whereIn('id', $ids)->delete();
            }

            // 6. إعادة حساب الأرصدة
            DB::statement("
                UPDATE account_trees a
                LEFT JOIN (
                    SELECT i.account_tree_id, SUM(i.debit) d, SUM(i.credit) c
                    FROM journal_entry_items i
                    JOIN journal_entries j ON j.id = i.journal_entry_id
                    WHERE j.status <> 'draft'
                    GROUP BY i.account_tree_id
                ) x ON x.account_tree_id = a.id
                SET a.total_debit = COALESCE(x.d, 0),
                    a.total_credit = COALESCE(x.c, 0),
                    a.balance = COALESCE(x.d, 0) - COALESCE(x.c, 0)
            ");
            DB::statement("
                UPDATE treasuries t SET t.balance = COALESCE((
                    SELECT SUM(CASE WHEN x.type = 'deposit' THEN x.amount ELSE -x.amount END)
                    FROM treasury_transactions x WHERE x.treasury_id = t.id), 0)
            ");
            DB::statement("
                UPDATE bank_accounts b SET b.balance = COALESCE((
                    SELECT SUM(CASE WHEN x.type = 'deposit' THEN x.amount ELSE -x.amount END)
                    FROM bank_transactions x WHERE x.bank_account_id = b.id), 0)
            ");
        });
    }

    public function down(): void
    {
        // تصحيح بيانات: لا رجوع آلي (توجد نسخة احتياطية قبل الدفعة 3)
    }
};
