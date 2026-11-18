<?php

namespace App\Observers;

use App\Events\Finance\Vouchers\VoucherEvent;
use App\Models\Finance\Voucher;
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
        // QA 2.4: القيد يُرحَّل متزامناً من VouchersController::store داخل معاملة واحدة.
        // لا نطلق حدثاً للـ Queue هنا حتى لا يُنفَّذ الترحيل مرتين.
    }

    // =========================================================================
    //  Updated
    // =========================================================================

    public function updated(Voucher $voucher): void
    {
        // QA 2.4: VouchersController::update يعكس القيد القديم ويُنشئ الجديد متزامناً.
    }

    // =========================================================================
    //  Deleted
    // =========================================================================

    public function deleted(Voucher $voucher): void
    {
        // QA 2.4: VouchersController::destroy يعكس القيد والمحفظة داخل معاملة الحذف.
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
            'financial_account_id',
        ];

        return (bool) array_intersect($financialFields, array_keys($voucher->getChanges()));
    }
}
