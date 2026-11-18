<?php

namespace App\Services\Finance;

use App\Models\Finance\AccountTree;
use App\Models\Finance\BankAccount;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryItem;
use App\Models\Finance\Treasury;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * القيود اليدوية (شاشة القيود، حركات الخزنة/البنك، الأرصدة الافتتاحية).
 *
 * قاعدة الأرصدة الموحّدة في النظام:
 *   رصيد الحساب في الشجرة = مجموع (مدين − دائن) لبنود القيود غير المسودة.
 *   القيد الملغى يبقى محسوباً ويقابله قيد عكسي (REV-) مرحّل، تماماً كما يفعل TransactionsService.
 */
class ManualJournalService
{
    /**
     * التحقق من سطور القيد. ترمي ValidationException برسائل عربية.
     *
     * @param array $items [['account_tree_id'=>..,'debit'=>..,'credit'=>..], ...]
     */
    public function validateLines(array $items, bool $mustBalance = true, string $field = 'items'): void
    {
        $errors = [];

        if (count($items) < 2) {
            $errors[$field][] = 'يجب أن يحتوي القيد على سطرين على الأقل';
        }

        $seen        = [];
        $totalDebit  = 0.0;
        $totalCredit = 0.0;
        $accountIds  = array_filter(array_map(fn ($i) => $i['account_tree_id'] ?? null, $items));
        $accounts    = AccountTree::withCount('children')->whereIn('id', $accountIds)->get()->keyBy('id');

        foreach (array_values($items) as $n => $item) {
            $line   = $n + 1;
            $debit  = round((float) ($item['debit'] ?? 0), 2);
            $credit = round((float) ($item['credit'] ?? 0), 2);
            $accId  = $item['account_tree_id'] ?? null;

            if (($debit > 0) === ($credit > 0)) {
                $errors["{$field}.{$n}"][] = "السطر {$line}: يجب إدخال مبلغ مدين أو دائن (أحدهما فقط وأكبر من صفر)";
            }

            $account = $accounts->get($accId);
            if (! $account) {
                $errors["{$field}.{$n}"][] = "السطر {$line}: الحساب غير موجود";
            } else {
                if ($account->children_count > 0) {
                    $errors["{$field}.{$n}"][] = "السطر {$line}: لا يمكن الترحيل على الحساب الرئيسي «{$account->code} {$account->name}»، اختر حساباً فرعياً";
                }
                if (! $account->is_active) {
                    $errors["{$field}.{$n}"][] = "السطر {$line}: الحساب «{$account->code}» غير نشط";
                }
            }

            if ($accId && isset($seen[$accId])) {
                $errors["{$field}.{$n}"][] = "السطر {$line}: الحساب مكرر في نفس القيد";
            }
            $seen[$accId] = true;

            $totalDebit  += $debit;
            $totalCredit += $credit;
        }

        if ($mustBalance && empty($errors)) {
            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                $errors[$field][] = 'القيد غير متوازن: مجموع المدين ' . number_format($totalDebit, 2)
                    . ' ≠ مجموع الدائن ' . number_format($totalCredit, 2);
            } elseif ($totalDebit <= 0) {
                $errors[$field][] = 'لا يمكن ترحيل قيد مجموعه صفر';
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * استبدال بنود القيد (إنشاء أو تعديل) ثم إعادة حساب أرصدة الحسابات المتأثرة.
     * JournalEntryItemObserver ينشئ/يحذف حركات الخزنة والبنك للبنود (غير المسودة).
     */
    public function replaceItems(JournalEntry $entry, array $items): void
    {
        $affected = $entry->items()->pluck('account_tree_id')->all();

        $entry->items()->get()->each(fn ($item) => $item->delete());

        foreach ($items as $item) {
            $entry->items()->create([
                'account_tree_id' => $item['account_tree_id'],
                'debit'           => round((float) ($item['debit'] ?? 0), 2),
                'credit'          => round((float) ($item['credit'] ?? 0), 2),
                'cost_center_id'  => $item['cost_center_id'] ?? null,
                'description'     => $item['description'] ?? null,
            ]);
            $affected[] = $item['account_tree_id'];
        }

        $this->recalculateAccounts($affected);
    }

    /**
     * إلغاء قيد مرحّل بقيد عكسي مرحّل (لا تُحذف بنود الأصل)
     */
    public function cancel(JournalEntry $entry, ?string $description = null): JournalEntry
    {
        if ($entry->status !== 'posted') {
            throw ValidationException::withMessages(['status' => 'يمكن إلغاء القيود المرحّلة فقط']);
        }

        return DB::transaction(function () use ($entry, $description) {
            $entry->loadMissing('items');

            $reverse = JournalEntry::create([
                'entry_number'   => 'REV-' . $entry->entry_number,
                'entry_type'     => 'reversal',
                'date'           => now()->toDateString(),
                'description'    => $description ?? ('عكس القيد رقم ' . $entry->entry_number),
                'reference_type' => $entry->reference_type,
                'reference_id'   => $entry->reference_id,
                'is_reversed'    => true,
                'reversed_from'  => $entry->id,
                'created_by'     => auth()->guard('admin')->id(),
                'status'         => 'posted',
            ]);

            $this->replaceItems($reverse, $entry->items->map(fn ($i) => [
                'account_tree_id' => $i->account_tree_id,
                'debit'           => $i->credit,
                'credit'          => $i->debit,
                'cost_center_id'  => $i->cost_center_id,
                'description'     => $i->description,
            ])->all());

            $entry->update(['status' => 'canceled']);

            return $reverse;
        });
    }

    /**
     * حذف قيد غير مرحّل (مسودة) مع بنوده
     */
    public function deleteDraft(JournalEntry $entry): void
    {
        $affected = $entry->items()->pluck('account_tree_id')->all();
        $entry->items()->get()->each(fn ($item) => $item->delete());
        $entry->delete();
        $this->recalculateAccounts($affected);
    }

    /**
     * إعادة حساب رصيد حساب من بنود القيود غير المسودة
     */
    public function recalculateAccounts(array $accountIds): void
    {
        foreach (array_unique(array_filter($accountIds)) as $accountId) {
            $totals = JournalEntryItem::where('account_tree_id', $accountId)
                ->whereHas('entry', fn ($q) => $q->where('status', '!=', 'draft'))
                ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
                ->first();

            AccountTree::where('id', $accountId)->update([
                'total_debit'  => $totals->d,
                'total_credit' => $totals->c,
                'balance'      => $totals->d - $totals->c,
            ]);
        }
    }

    /**
     * رقم قيد يدوي JE-YYYY-NNNN
     */
    public function nextEntryNumber(string $prefix = 'JE'): string
    {
        $year   = now()->year;
        $prefix = $prefix . '-' . $year . '-';

        $last = JournalEntry::where('entry_number', 'LIKE', $prefix . '%')
            ->lockForUpdate()
            ->orderByRaw('CAST(SUBSTRING(entry_number, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->value('entry_number');

        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * الحسابات المسموح بها كحساب مقابل: فرعية نشطة وليست خزنة أو حساباً بنكياً
     */
    public function counterAccounts()
    {
        $financial = Treasury::pluck('account_tree_id')
            ->merge(BankAccount::pluck('account_tree_id'))->filter()->all();

        return AccountTree::where('is_active', true)
            ->whereDoesntHave('children')
            ->whereNotIn('id', $financial)
            ->orderBy('code')
            ->get();
    }
}
