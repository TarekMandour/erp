<?php

namespace App\Services\Finance\TransactionsService;

use App\Models\Finance\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * أدوات مساعدة لاستخراج البيانات من الموديل المصدر وربطه بالقيد المحاسبي
 */
class SourceModelHelper
{
    /**
     * كاش لنتيجة Schema::hasColumn() حتى لا نستعلم DB في كل معاملة
     * @var array<string, bool>
     */
    private array $columnCache = [];

    // =========================================================================
    //  ربط/فك ربط المصدر بالقيد
    // =========================================================================

    /**
     * ربط العملية المصدر بالقيد المحاسبي (إذا كان الجدول يملك journal_entry_id)
     */
    public function linkSourceToJournalEntry(Model $sourceModel, ?JournalEntry $journalEntry): void
    {
        if (! $journalEntry) {
            return;
        }

        if ($this->tableHasJournalEntryColumn($sourceModel)) {
            $sourceModel->update(['journal_entry_id' => $journalEntry->id]);
        }
    }

    /**
     * فك الربط بين العملية المصدر والقيد
     */
    public function unlinkSourceFromJournalEntry(Model $sourceModel): void
    {
        if ($this->tableHasJournalEntryColumn($sourceModel)) {
            $sourceModel->update(['journal_entry_id' => null]);
        }
    }

    /**
     * جلب معرف القيد المرتبط بالعملية
     */
    public function getLinkedJournalEntryId(Model $sourceModel): ?int
    {
        if ($this->tableHasJournalEntryColumn($sourceModel)) {
            return $sourceModel->journal_entry_id;
        }

        return JournalEntry::where('reference_type', get_class($sourceModel))
            ->where('reference_id', $sourceModel->id)
            ->where('is_reversed', false)
            ->value('id');
    }

    /**
     * التحقق من وجود عمود journal_entry_id في جدول الموديل
     * النتيجة مخزّنة في كاش بسيط على مستوى الطلب (لا تستدعي DB أكثر من مرة للجدول نفسه)
     */
    public function tableHasJournalEntryColumn(Model $sourceModel): bool
    {
        $table = $sourceModel->getTable();

        return $this->columnCache[$table]
            ??= Schema::hasColumn($table, 'journal_entry_id');
    }

    // =========================================================================
    //  بيانات مساعدة من الموديل
    // =========================================================================

    /**
     * تاريخ العملية
     */
    public function getTransactionDate(Model $sourceModel): string
    {
        if (method_exists($sourceModel, 'getTransactionDate')) {
            return $sourceModel->getTransactionDate();
        }

        if (! empty($sourceModel->date)) {
            return $sourceModel->date;
        }

        return $sourceModel->created_at?->toDateString() ?? now()->toDateString();
    }

    /**
     * معرف المستخدم الذي أنشأ العملية
     */
    public function getCreatedBy(Model $sourceModel): ?int
    {
        return $sourceModel->created_by ?? auth()->id();
    }

    /**
     * المبلغ الإجمالي للعملية
     */
    public function getTransactionAmount(Model $sourceModel): float
    {
        if (method_exists($sourceModel, 'getTransactionAmount')) {
            return $sourceModel->getTransactionAmount();
        }

        return (float) ($sourceModel->total_amount ?? $sourceModel->total ?? 0);
    }

    /**
     * هل العملية من نوع قبض؟
     */
    public function isReceiptType(Model $sourceModel): bool
    {
        if (method_exists($sourceModel, 'isReceiptType')) {
            return $sourceModel->isReceiptType();
        }

        return false;
    }

    /**
     * هل العملية من نوع صرف؟
     */
    public function isPaymentType(Model $sourceModel): bool
    {
        if (method_exists($sourceModel, 'isPaymentType')) {
            return $sourceModel->isPaymentType();
        }

        return false;
    }
}
