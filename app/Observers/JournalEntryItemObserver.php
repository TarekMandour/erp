<?php

namespace App\Observers;


use App\Models\Finance\JournalEntryItem;
use App\Services\Finance\TransactionsService;
use App\Models\Finance\TreasuryTransaction;
use App\Models\Finance\Treasury;
use App\Models\Finance\BankTransaction;
use App\Models\Finance\BankAccount;
use Illuminate\Support\Facades\Log;

class JournalEntryItemObserver
{

    public function created(JournalEntryItem $journalEntryItem): void
    {
        // if ($journalEntryItem->status !== 'approved') {
        //     return;
        // }

        Log::info('JournalEntryItemObserver@created: dispatching JournalEntryItemCreated', [
            'journal_entry_item_id' => $journalEntryItem->id,
            'account_tree_id'       => $journalEntryItem->account_tree_id,
            'amount'     => $journalEntryItem->debit - $journalEntryItem->credit,
        ]);

        $bankAccount = BankAccount::where('account_tree_id', $journalEntryItem->account_tree_id)->first();
        $treasury = Treasury::where('account_tree_id', $journalEntryItem->account_tree_id)->first();

        if($bankAccount) {
            BankTransaction::create([
                'bank_account_id' => $bankAccount->id,
                'type'            => $journalEntryItem->debit > 0 ? 'deposit' : 'withdraw',
                'amount'          => abs($journalEntryItem->debit - $journalEntryItem->credit),
                'reference_type'  => JournalEntryItem::class,
                'reference_id'    => $journalEntryItem->id,
            ]);

            // تحديث رصيد الحساب البنكي
            $this->updateAccountBalance($bankAccount->id);

        }

        if($treasury) {
            TreasuryTransaction::create([
                'treasury_id' => $treasury->id,
                'type'        => $journalEntryItem->debit > 0 ? 'deposit' : 'withdraw',
                'amount'      => abs($journalEntryItem->debit - $journalEntryItem->credit),
                'reference_type' => JournalEntryItem::class,
                'reference_id'   => $journalEntryItem->id,
            ]);

            // تحديث رصيد الخزنة
            $this->updateTreasuryBalance($treasury->id);
        }
        
    }
    

    public function updated(JournalEntryItem $journalEntryItem): void
    {

        Log::info('JournalEntryItemObserver@updated: dispatching JournalEntryItemUpdated', [
            'journal_entry_item_id' => $journalEntryItem->id,
            'account_tree_id'       => $journalEntryItem->account_tree_id,
            'amount'     => $journalEntryItem->debit - $journalEntryItem->credit,
        ]);

        $bankAccount = BankAccount::where('account_tree_id', $journalEntryItem->account_tree_id)->first();
        $treasury = Treasury::where('account_tree_id', $journalEntryItem->account_tree_id)->first();

        if($bankAccount) {
            BankTransaction::where('bank_account_id', $bankAccount->id)
                ->where('reference_type', JournalEntryItem::class)
                ->where('reference_id', $journalEntryItem->id)
                ->delete();
                
            BankTransaction::create([
                'bank_account_id' => $bankAccount->id,
                'type'            => $journalEntryItem->debit > 0 ? 'deposit' : 'withdraw',
                'amount'          => abs($journalEntryItem->debit - $journalEntryItem->credit),
                'reference_type'  => JournalEntryItem::class,
                'reference_id'    => $journalEntryItem->id,
            ]);

            // تحديث رصيد الحساب البنكي
            $this->updateAccountBalance($bankAccount->id);

        }
        
        if($treasury) {
            TreasuryTransaction::where('treasury_id', $treasury->id)
                ->where('reference_type', JournalEntryItem::class)
                ->where('reference_id', $journalEntryItem->id)
                ->delete();
                
            TreasuryTransaction::create([
                'treasury_id' => $treasury->id,
                'type'        => $journalEntryItem->debit > 0 ? 'deposit' : 'withdraw',
                'amount'      => abs($journalEntryItem->debit - $journalEntryItem->credit),
                'reference_type' => JournalEntryItem::class,
                'reference_id'   => $journalEntryItem->id,
            ]);

            // تحديث رصيد الخزنة
            $this->updateTreasuryBalance($treasury->id);
        }
    }

    public function deleted(JournalEntryItem $journalEntryItem): void
    {
        Log::info('JournalEntryItemObserver@deleted: dispatching JournalEntryItemDeleted', [
            'journal_entry_item_id' => $journalEntryItem->id,
            'account_tree_id'       => $journalEntryItem->account_tree_id,
            'amount'     => $journalEntryItem->debit - $journalEntryItem->credit,
        ]);

        $bankAccount = BankAccount::where('account_tree_id', $journalEntryItem->account_tree_id)->first();
        $treasury = Treasury::where('account_tree_id', $journalEntryItem->account_tree_id)->first();

        if($bankAccount) {

            BankTransaction::where('bank_account_id', $bankAccount->id)
                ->where('reference_type', JournalEntryItem::class)
                ->where('reference_id', $journalEntryItem->id)
                ->delete();

            // تحديث رصيد الحساب البنكي
            $this->updateAccountBalance($bankAccount->id);

        }

        if($treasury) {
            TreasuryTransaction::where('treasury_id', $treasury->id)
                ->where('reference_type', JournalEntryItem::class)
                ->where('reference_id', $journalEntryItem->id)
                ->delete();

            // تحديث رصيد الخزنة
            $this->updateTreasuryBalance($treasury->id);
        }
    }

    private function updateAccountBalance(int $accountId): void
    {
        $account = BankAccount::find($accountId);
        if (!$account) return;

        $deposits  = BankTransaction::where('bank_account_id', $accountId)->where('type', 'deposit')->sum('amount');
        $withdraws = BankTransaction::where('bank_account_id', $accountId)->where('type', 'withdraw')->sum('amount');

        $account->update(['balance' => $deposits - $withdraws]);
    }

    private function updateTreasuryBalance(int $treasuryId): void
    {
        $treasury = Treasury::find($treasuryId);
        if (!$treasury) return;

        $deposits  = TreasuryTransaction::where('treasury_id', $treasuryId)->where('type', 'deposit')->sum('amount');
        $withdraws = TreasuryTransaction::where('treasury_id', $treasuryId)->where('type', 'withdraw')->sum('amount');

        $treasury->update(['balance' => $deposits - $withdraws]);
    }
    
}
