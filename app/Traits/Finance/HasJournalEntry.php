<?php

namespace App\Traits\Finance;

use Illuminate\Support\Carbon;

/**
 * Trait HasJournalEntry
 *
 * يُضاف لأي موديل يحتاج ترحيل قيد محاسبي تلقائي.
 * الموديل يُعيد تعريف (override) فقط ما يختلف عن القيم الافتراضية.
 *
 * كيفية الاستخدام:
 *   1. أضف `use HasJournalEntry;` على الموديل
 *   2. أعِد تعريف الدوال التي تختلف (getScenarioCode, getOperationType...إلخ)
 *   3. أضف Observer → Event → Listener — لا تعديل في أي Service مطلوب
 */
trait HasJournalEntry
{
    // =========================================================================
    //  كود السيناريو والنوع
    // =========================================================================

    /**
     * كود السيناريو المطلوب بحثه في جدول posting_scenarios
     * مثال: VOUCHER_RECEIPT_CASH_CUSTOMER | SALE_CREDIT | PURCHASE_CASH
     */
    public function getScenarioCode(): string
    {
        return 'ADJUSTMENT';
    }

    /**
     * كود السيناريو الاحتياطي عندما لا يُوجد سيناريو بالكود الأساسي
     */
    public function getFallbackScenarioCode(): string
    {
        return 'ADJUSTMENT';
    }

    /**
     * نوع العملية (يُستخدم داخلياً لاختيار السيناريو)
     * مثال: voucher | order | purchase | adjustment
     */
    public function getOperationType(): string
    {
        return 'adjustment';
    }

    /**
     * قيمة entry_type المُدرَجة في journal_entries
     * يجب أن تكون ضمن VALID_ENTRY_TYPES في ScenarioResolver
     * مثال: customer_payment | supplier_payment | sales | purchase
     */
    public function getEntryType(): string
    {
        return 'adjustment';
    }

    // =========================================================================
    //  تحديد اتجاه العملية (قبض / صرف)
    // =========================================================================

    /**
     * هل العملية من نوع قبض (مدين → الصندوق أو البنك)؟
     */
    public function isReceiptType(): bool
    {
        return false;
    }

    /**
     * هل العملية من نوع صرف (دائن ← الصندوق أو البنك)؟
     */
    public function isPaymentType(): bool
    {
        return false;
    }

    // =========================================================================
    //  بيانات مساعدة للقيد
    // =========================================================================

    /**
     * تاريخ العملية المُدرَج في حقل date بالقيد
     */
    public function getTransactionDate(): string
    {
        if (! empty($this->date)) {
            return $this->date instanceof Carbon
                ? $this->date->toDateString()
                : (string) $this->date;
        }

        return $this->created_at?->toDateString() ?? now()->toDateString();
    }

    /**
     * المبلغ الإجمالي للعملية
     */
    public function getTransactionAmount(): float
    {
        return (float) ($this->total_amount ?? $this->total ?? 0);
    }

    /**
     * الوصف العربي المُدرَج في رأس القيد وبنوده
     * إذا أُعيد '' تعود EntryDescriptionBuilder إلى منطقها الاحتياطي
     */
    public function getPostableDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * تسمية نوع الدفع بالعربية (نقدي / آجل / شيك...)
     * تُستخدم في بناء الوصف
     */
    public function getPaymentTypeLabel(): string
    {
        return $this->payment_type ?? '';
    }
}
