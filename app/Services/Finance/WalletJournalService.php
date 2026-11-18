<?php

namespace App\Services\Finance;

use App\Models\Finance\AccountTree;
use App\Models\Finance\CustomerWallet;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\SupplierWallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * قيود حركات المحفظة اليدوية للعملاء والموردين (QA 4.4 / 4.7).
 *
 * المحفظة تتبع حساب الطرف في الشجرة: مدين المحفظة = مدين حساب الطرف، ودائنها = دائنه
 * (العميل 1104، المورد 2101)، والحساب المقابل يختاره المستخدم.
 * الحركات الناتجة عن سند أو فاتورة أو طلب تُدار من مستندها ولا تُعدَّل من شاشة المحفظة.
 */
class WalletJournalService
{
    public function __construct(private ManualJournalService $journal)
    {
    }

    public function partyAccountId(Model $row): int
    {
        $code = $row instanceof CustomerWallet ? '1104' : '2101';

        return (int) AccountTree::where('code', $code)->value('id');
    }

    public function assertManual(Model $row): void
    {
        $source = match (true) {
            ! empty($row->voucher_id)  => 'سند #' . $row->voucher_id,
            ! empty($row->order_id)    => 'طلب #' . $row->order_id,
            ! empty($row->purchase_id) => 'فاتورة شراء #' . $row->purchase_id,
            default                    => null,
        };

        if ($source) {
            throw ValidationException::withMessages(['debit' => "هذه الحركة ناتجة عن {$source}، عدّلها أو احذفها من مستندها"]);
        }
    }

    /**
     * إنشاء قيد الحركة أو تحديثه
     */
    public function sync(Model $row, int $counterAccountId): JournalEntry
    {
        $party  = $this->partyAccountId($row);
        $debit  = round((float) $row->debit, 2);
        $credit = round((float) $row->credit, 2);
        $desc   = $this->description($row);

        $lines = [
            ['account_tree_id' => $party,            'debit' => $debit,  'credit' => $credit, 'description' => $desc],
            ['account_tree_id' => $counterAccountId, 'debit' => $credit, 'credit' => $debit,  'description' => $desc],
        ];
        $this->journal->validateLines($lines, true, 'counter_account_id');

        $entry = $this->entryOf($row);
        $type  = $row instanceof CustomerWallet
            ? ($debit > 0 ? 'customer_debit_note' : 'customer_credit_note')
            : ($debit > 0 ? 'supplier_debit_note' : 'supplier_credit_note');

        $header = [
            'entry_type'  => $type,
            'date'        => $row->date,
            'description' => $desc,
        ];

        if ($entry) {
            $entry->update($header);
        } else {
            $entry = JournalEntry::create($header + [
                'entry_number'   => $this->journal->nextEntryNumber(),
                'reference_type' => get_class($row),
                'reference_id'   => $row->id,
                'created_by'     => auth()->guard('admin')->id(),
                'status'         => 'posted',
            ]);
        }

        $this->journal->replaceItems($entry, $lines);

        return $entry;
    }

    /**
     * حذف الحركة اليدوية = إلغاء قيدها بقيد عكسي
     */
    public function cancel(Model $row): void
    {
        if ($entry = $this->entryOf($row)) {
            $this->journal->cancel($entry, 'إلغاء ' . $this->description($row));
        }
    }

    public function entryOf(Model $row): ?JournalEntry
    {
        return JournalEntry::where('reference_type', get_class($row))
            ->where('reference_id', $row->id)
            ->where('status', 'posted')
            ->where('entry_type', '!=', 'reversal')
            ->first();
    }

    public function counterAccountId(Model $row): ?int
    {
        $entry = $this->entryOf($row);

        return $entry?->items()->where('account_tree_id', '!=', $this->partyAccountId($row))->value('account_tree_id');
    }

    /**
     * الحسابات المسموح بها كحساب مقابل (فرعية نشطة، ليست خزنة/بنكاً، وليست حساب الطرف نفسه)
     */
    public function counterAccounts(string $partyCode)
    {
        return $this->journal->counterAccounts()->where('code', '!=', $partyCode)->values();
    }

    private function description(Model $row): string
    {
        $who = $row instanceof CustomerWallet
            ? 'العميل ' . ($row->customer->name ?? '#' . $row->customer_id)
            : 'المورد ' . ($row->supplier->name ?? '#' . $row->supplier_id);

        return 'حركة محفظة يدوية #' . $row->id . ' - ' . $who . ($row->description ? ' — ' . $row->description : '');
    }
}
