<?php

namespace App\Services\Finance;

use App\Models\Finance\BankAccount;
use App\Models\Finance\BankTransaction;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryItem;
use App\Models\Finance\Treasury;
use App\Models\Finance\TreasuryTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * حركات الخزنة والبنك اليدوية.
 *
 * كل حركة يدوية = قيد مرحّل من سطرين (الخزنة/البنك ↔ الحساب المقابل).
 * حركة الخزنة/البنك نفسها ينشئها JournalEntryItemObserver من بند القيد، فيبقى
 * رصيد الخزنة مطابقاً لحسابها في الشجرة دائماً.
 */
class CashMovementService
{
    public function __construct(private ManualJournalService $journal)
    {
    }

    /**
     * @param Treasury|BankAccount $holder
     */
    public function create(Model $holder, string $type, float $amount, int $counterAccountId, ?string $description): Model
    {
        return DB::transaction(function () use ($holder, $type, $amount, $counterAccountId, $description) {
            $this->assertNotOverdrawn($holder, $this->effect($type, $amount));

            $entry = JournalEntry::create([
                'entry_number'   => $this->journal->nextEntryNumber(),
                'entry_type'     => $this->entryType($holder, $type),
                'date'           => now()->toDateString(),
                'description'    => $description ?: $this->defaultDescription($holder, $type),
                'reference_type' => get_class($holder),
                'reference_id'   => $holder->id,
                'created_by'     => auth()->guard('admin')->id(),
                'status'         => 'posted',
            ]);

            return $this->writeLines($entry, $holder, $type, $amount, $counterAccountId, $description);
        });
    }

    public function update(Model $txn, Model $holder, string $type, float $amount, int $counterAccountId, ?string $description): Model
    {
        $entry = $this->editableEntry($txn);

        return DB::transaction(function () use ($txn, $entry, $holder, $type, $amount, $counterAccountId, $description) {
            $oldHolder = $this->holderOf($txn);
            $delta     = $this->effect($type, $amount);
            if ($oldHolder && get_class($oldHolder) === get_class($holder) && $oldHolder->id === $holder->id) {
                $delta -= $this->effect($txn->type, (float) $txn->amount);
            } elseif ($oldHolder) {
                $this->assertNotOverdrawn($oldHolder, -$this->effect($txn->type, (float) $txn->amount));
            }
            $this->assertNotOverdrawn($holder, $delta);

            $entry->update([
                'entry_type'     => $this->entryType($holder, $type),
                'description'    => $description ?: $this->defaultDescription($holder, $type),
                'reference_type' => get_class($holder),
                'reference_id'   => $holder->id,
            ]);

            return $this->writeLines($entry, $holder, $type, $amount, $counterAccountId, $description);
        });
    }

    /**
     * حذف حركة يدوية = إلغاء قيدها بقيد عكسي (لا حذف لقيد مرحّل)
     */
    public function delete(Model $txn): void
    {
        $entry  = $this->editableEntry($txn);
        $holder = $this->holderOf($txn);

        DB::transaction(function () use ($txn, $entry, $holder) {
            if ($holder) {
                $this->assertNotOverdrawn($holder, -$this->effect($txn->type, (float) $txn->amount));
            }
            $this->journal->cancel($entry, 'إلغاء حركة ' . ($txn instanceof TreasuryTransaction ? 'خزنة' : 'بنك') . ' #' . $txn->id);
        });
    }

    /**
     * القيد اليدوي المرتبط بالحركة، أو ValidationException إن كانت الحركة غير قابلة للتعديل من هذه الشاشة
     */
    public function editableEntry(Model $txn): JournalEntry
    {
        $entry = $this->entryOf($txn);

        if (! $entry || ! in_array($entry->reference_type, [Treasury::class, BankAccount::class], true)) {
            throw ValidationException::withMessages(['amount' => 'هذه الحركة ناتجة عن مستند أو قيد (' . ($entry->entry_number ?? 'بدون قيد') . ')، عدّلها من مصدرها']);
        }
        if ($entry->status !== 'posted' || $entry->entry_type === 'reversal') {
            throw ValidationException::withMessages(['amount' => 'هذه الحركة ملغاة أو حركة عكسية، لا يمكن تعديلها']);
        }

        return $entry;
    }

    public function entryOf(Model $txn): ?JournalEntry
    {
        if ($txn->reference_type !== JournalEntryItem::class) {
            return null;
        }

        return JournalEntryItem::find($txn->reference_id)?->entry;
    }

    /**
     * الحساب المقابل لحركة يدوية (السطر الآخر في قيدها)
     */
    public function counterAccountId(Model $txn): ?int
    {
        $entry = $this->entryOf($txn);
        if (! $entry) {
            return null;
        }

        return $entry->items()->where('id', '!=', $txn->reference_id)->value('account_tree_id');
    }

    // -------------------------------------------------------------------------

    private function writeLines(JournalEntry $entry, Model $holder, string $type, float $amount, int $counterAccountId, ?string $description): Model
    {
        $holderAccount = (int) $holder->account_tree_id;
        $isDeposit     = $type === 'deposit';

        $lines = [
            ['account_tree_id' => $holderAccount,    'debit' => $isDeposit ? $amount : 0, 'credit' => $isDeposit ? 0 : $amount, 'description' => $description],
            ['account_tree_id' => $counterAccountId, 'debit' => $isDeposit ? 0 : $amount, 'credit' => $isDeposit ? $amount : 0, 'description' => $description],
        ];

        $this->journal->validateLines($lines, true, 'counter_account_id');
        $this->journal->replaceItems($entry, $lines);

        $holderItemId = $entry->items()->where('account_tree_id', $holderAccount)->value('id');

        $txn = $holder instanceof Treasury
            ? TreasuryTransaction::where('treasury_id', $holder->id)->where('reference_type', JournalEntryItem::class)->where('reference_id', $holderItemId)->firstOrFail()
            : BankTransaction::where('bank_account_id', $holder->id)->where('reference_type', JournalEntryItem::class)->where('reference_id', $holderItemId)->firstOrFail();

        if ($txn instanceof TreasuryTransaction && $description) {
            $txn->update(['description' => $description]);
        }

        return $txn;
    }

    private function assertNotOverdrawn(Model $holder, float $delta): void
    {
        $holder->refresh();
        $after = round((float) $holder->balance + $delta, 2);

        if ($delta < 0 && $after < 0) {
            throw ValidationException::withMessages([
                'amount' => 'الرصيد غير كافٍ: الرصيد الحالي ' . number_format((float) $holder->balance, 2)
                    . '، والرصيد بعد العملية سيكون ' . number_format($after, 2),
            ]);
        }
    }

    private function effect(string $type, float $amount): float
    {
        return $type === 'deposit' ? $amount : -$amount;
    }

    private function holderOf(Model $txn): ?Model
    {
        return $txn instanceof TreasuryTransaction ? Treasury::find($txn->treasury_id) : BankAccount::find($txn->bank_account_id);
    }

    private function entryType(Model $holder, string $type): string
    {
        return ($holder instanceof Treasury ? 'cash_' : 'bank_') . ($type === 'deposit' ? 'deposit' : 'withdraw');
    }

    private function defaultDescription(Model $holder, string $type): string
    {
        $what = $type === 'deposit' ? 'إيداع' : 'سحب';

        return $holder instanceof Treasury
            ? "{$what} يدوي - خزنة {$holder->name}"
            : "{$what} يدوي - حساب بنكي {$holder->account_number}";
    }
}
