<?php

namespace App\Listeners\Vouchers;

use App\Events\Finance\Vouchers\VoucherEvent;
use App\Services\Finance\TransactionsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * مستمع قابل للتكرار (ShouldQueue) — يعمل في الخلفية
 *
 * $tries     : عدد محاولات إعادة التشغيل عند الفشل
 * $backoff   : الانتظار بالثواني بين المحاولات (قابل للتدرج)
 * $timeout   : أقصى وقت تنفيذ قبل اعتبار المهمة فاشلة
 */
class PostVoucherJournalEntry implements ShouldQueue
{
    use InteractsWithQueue;

    public int   $tries   = 3;
    public array $backoff = [30, 60, 120]; // ثواني بين المحاولات
    public int   $timeout = 60;

    public function __construct(private TransactionsService $service) {}

    public function handle(VoucherEvent $event): void
    {
        $voucher = $event->voucher;

        // تجنب إنشاء قيد مكرر فقط عند الإنشاء الأول
        if ($event->action === 'create' && $voucher->journal_entry_id) {
            Log::info('PostVoucherJournalEntry: journal entry already exists, skipping', [
                'voucher_id'       => $voucher->id,
                'journal_entry_id' => $voucher->journal_entry_id,
            ]);
            return;
        }

        Log::info('PostVoucherJournalEntry: processing', ['voucher_id' => $voucher->id]);
        
        switch ($event->action) {

            case 'create':
                $entry = $this->service->process($event->voucher);
                break;

            case 'cancel':
                $entry = $this->service->reverse($event->voucher);
                break;

            case 'update':
                $entry = $this->service->regenerate($event->voucher);
                break;
        }

        Log::info('PostVoucherJournalEntry: done', [
            'voucher_id'       => $voucher->id,
            'journal_entry_id' => $entry->id,
            'entry_number'     => $entry->entry_number,
        ]);
    }

    /**
     * يُستدعى بعد استنفاد جميع المحاولات
     */
    public function failed(VoucherEvent $event, \Throwable $e): void
    {
        Log::error('PostVoucherJournalEntry: all retries exhausted', [
            'voucher_id' => $event->voucher->id,
            'error'      => $e->getMessage(),
            'trace'      => $e->getTraceAsString(),
        ]);

        // يمكنك هنا: إرسال إشعار للمحاسب، أو إنشاء تذكرة، أو تعليم السند بـ posting_failed
        // $event->voucher->update(['posting_status' => 'failed']);
    }
}