<?php

namespace App\Listeners\Orders;

use App\Events\Finance\Orders\OrderEvent;
use App\Services\Finance\TransactionsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PostOrderJournalEntry implements ShouldQueue
{
    use InteractsWithQueue;

    public int   $tries   = 3;
    public array $backoff = [30, 60, 120];
    public int   $timeout = 60;

    public function __construct(private TransactionsService $service) {}

    public function handle(OrderEvent $event): void
    {
        $order = $event->order;

        if ($event->action === 'create' && $order->journal_entry_id) {
            Log::info('PostOrderJournalEntry: journal entry already exists, skipping', [
                'order_id'         => $order->id,
                'journal_entry_id' => $order->journal_entry_id,
            ]);
            return;
        }

        Log::info('PostOrderJournalEntry: processing', [
            'order_id' => $order->id,
            'action'   => $event->action,
        ]);

        $entry = match ($event->action) {
            'create' => $this->service->process($order),
            'cancel' => $this->service->reverse($order),
            'update' => $this->service->regenerate($order),
        };

        Log::info('PostOrderJournalEntry: done', [
            'order_id'         => $order->id,
            'journal_entry_id' => $entry->id,
            'entry_number'     => $entry->entry_number,
        ]);
    }

    public function failed(OrderEvent $event, \Throwable $e): void
    {
        Log::error('PostOrderJournalEntry: all retries exhausted', [
            'order_id' => $event->order->id,
            'error'    => $e->getMessage(),
            'trace'    => $e->getTraceAsString(),
        ]);
    }
}
