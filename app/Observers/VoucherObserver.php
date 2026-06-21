<?php

namespace App\Observers;

use App\Events\Finance\Vouchers\VoucherEvent;
use App\Models\Finance\Voucher;
use App\Services\Finance\TransactionsService;
use Illuminate\Support\Facades\Log;

/**
 * VoucherObserver
 *
 * المسؤولية الوحيدة هنا: إطلاق الأحداث (Events) فقط.
 * TransactionsService لا يُستدعى هنا مباشرة —
 * هذا يجعل الـ Observer قابلاً للاختبار بمعزل تام عن منطق القيود.
 *
 * تسلسل الأحداث:
 *   created  → status=approved          → VoucherApproved   → PostVoucherJournalEntry (queued)
 *   updated  → status changed=approved  → VoucherApproved   → PostVoucherJournalEntry (queued)
 *   updated  → status changed=canceled  → VoucherCanceled   → ReverseVoucherJournalEntry (queued)
 *   updated  → أي تغيير آخر مع قيد موجود → VoucherUpdated  → RegenerateVoucherJournalEntry (queued)
 */
class VoucherObserver
{
    // =========================================================================
    //  Created
    // =========================================================================

    /**
     * يُطلق عند إنشاء سند جديد.
     * فقط السندات المعتمدة (approved) تُولّد قيداً فورياً.
     * السندات المسودة (draft) لا تُرحَّل حتى يتم اعتمادها.
     */
    public function created(Voucher $voucher): void
    {
        // if ($voucher->status !== 'approved') {
        //     return;
        // }

        Log::info('VoucherObserver@created: dispatching VoucherApproved', [
            'voucher_id' => $voucher->id,
            'type'       => $voucher->type,
            'amount'     => $voucher->total_amount,
        ]);

        VoucherEvent::dispatch($voucher, 'create');
    }

    // =========================================================================
    //  Updated
    // =========================================================================

    /**
     * يُطلق عند تعديل سند موجود.
     *
     * الحالات:
     *  1. الحالة تغيّرت إلى approved  → أنشئ قيداً (لأول مرة أو بعد رفع الإلغاء)
     *  2. الحالة تغيّرت إلى canceled  → اعكس القيد الموجود
     *  3. بيانات مالية تغيّرت + قيد موجود → أعد إنشاء القيد
     */
    public function updated(Voucher $voucher): void
    {
        // الحالة 3: تعديل بيانات مالية على سند معتمد يملك قيداً مسبقاً
        if ( $this->financialFieldsChanged($voucher)
        ) {
            Log::info('VoucherObserver@updated: financial fields changed, dispatching VoucherUpdated', [
                'voucher_id'   => $voucher->id,
                'changed'      => array_keys($voucher->getChanges()),
            ]);
            VoucherEvent::dispatch($voucher, 'update');
        }
    }

    // =========================================================================
    //  Deleted / Restored (اختياري – فعّلهم إذا كنت تستخدم SoftDeletes)
    // =========================================================================

    /**
     * حذف السند → عكس القيد تلقائياً
     */
    public function deleted(Voucher $voucher): void
    {
        Log::info('VoucherObserver@deleted: reversing journal entry synchronously', [
            'voucher_id' => $voucher->id,
        ]);

        // لا نستخدم الـ queue هنا لأن السند سيُحذف من DB قبل تنفيذ الـ job،
        // مما يتسبب في فشل SerializesModels عند محاولة إعادة تحميل الموديل.
        try {
            app(TransactionsService::class)->reverse($voucher);
        } catch (\Throwable $e) {
            Log::error('VoucherObserver@deleted: failed to reverse journal entry', [
                'voucher_id' => $voucher->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * استعادة سند محذوف → أعد إنشاء القيد
     */
    public function restored(Voucher $voucher): void
    {
        if ($voucher->status === 'approved') {
            Log::info('VoucherObserver@restored: dispatching VoucherApproved', [
                'voucher_id' => $voucher->id,
            ]);
            VoucherEvent::dispatch($voucher, 'create');
        }
    }

    // =========================================================================
    //  Helpers
    // =========================================================================

    /**
     * هل تغيّرت حقول مالية تستوجب إعادة ترحيل القيد؟
     */
    private function financialFieldsChanged(Voucher $voucher): bool
    {
        $financialFields = [
            'total_amount',
            'payment_type',
            'party_id',
            'party_type',
            'type',
            'cost_center_id',
            'tax',
            'discount',
        ];

        return (bool) array_intersect($financialFields, array_keys($voucher->getChanges()));
    }
}
