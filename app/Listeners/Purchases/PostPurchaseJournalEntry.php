<?php

namespace App\Listeners\Purchases;

use App\Events\Finance\Purchases\PurchaseEvent;
use App\Models\Finance\JournalEntry;
use App\Services\Finance\TransactionsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostPurchaseJournalEntry implements ShouldQueue
{
    use InteractsWithQueue;

    public int   $tries   = 3;
    public array $backoff = [30, 60, 120];
    public int   $timeout = 60;

    public function __construct(private TransactionsService $service) {}

    public function handle(PurchaseEvent $event): void
    {
        $purchase = $event->purchase;

        // حماية من التكرار: قفل أتوماتيكي لضمان قيد واحد فقط لكل عملية create
        if ($event->action === 'create') {
            $alreadyExists = DB::transaction(function () use ($purchase) {
                // قفل مشترك لمنع تنفيذ موازي للنفس الفاتورة
                $exists = JournalEntry::where('reference_type', get_class($purchase))
                    ->where('reference_id', $purchase->id)
                    ->where('entry_type', '!=', 'reversal')
                    ->lockForUpdate()
                    ->exists();

                return $exists;
            });

            if ($alreadyExists) {
                Log::info('PostPurchaseJournalEntry: journal entry already exists, skipping duplicate', [
                    'purchase_id' => $purchase->id,
                ]);
                return;
            }
        }

        Log::info('PostPurchaseJournalEntry: processing', [
            'purchase_id' => $purchase->id,
            'action'      => $event->action,
        ]);

        $entry = match ($event->action) {
            'create' => $this->service->process($purchase),
            'cancel' => $this->service->reverse($purchase),
            'update' => $this->service->regenerate($purchase),
        };

        Log::info('PostPurchaseJournalEntry: done', [
            'purchase_id'      => $purchase->id,
            'journal_entry_id' => $entry->id,
            'entry_number'     => $entry->entry_number,
        ]);
    }

    public function failed(PurchaseEvent $event, \Throwable $e): void
    {
        Log::error('PostPurchaseJournalEntry: all retries exhausted', [
            'purchase_id' => $event->purchase->id,
            'error'       => $e->getMessage(),
            'trace'       => $e->getTraceAsString(),
        ]);
    }
}
