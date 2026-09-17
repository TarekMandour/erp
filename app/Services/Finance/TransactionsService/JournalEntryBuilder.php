<?php

namespace App\Services\Finance\TransactionsService;

use App\Models\Finance\JournalEntry;
use App\Models\Finance\PostingScenario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * إنشاء رأس القيد المحاسبي، ترقيمه، التحقق من توازنه،
 * وتحديث الأرصدة الجارية في شجرة الحسابات
 */
class JournalEntryBuilder
{
    public function __construct(
        private EntryDescriptionBuilder $descriptionBuilder,
        private SourceModelHelper $sourceModelHelper,
    ) {
    }

    // =========================================================================
    //  إنشاء رأس القيد
    // =========================================================================

    /**
     * إنشاء سجل رأس القيد المحاسبي
     */
    public function createJournalEntryHeader(Model $sourceModel, PostingScenario $scenario, string $entryType): JournalEntry
    {
        return JournalEntry::create([
            'entry_number'   => $this->generateEntryNumber($sourceModel, $scenario),
            'entry_type'     => $entryType,
            'date'           => $this->sourceModelHelper->getTransactionDate($sourceModel),
            'description'    => $this->descriptionBuilder->generateDescription($sourceModel, $scenario),
            'reference_type' => get_class($sourceModel),
            'reference_id'   => $sourceModel->id,
            'is_reversed'    => false,
            'created_by'     => $this->sourceModelHelper->getCreatedBy($sourceModel),
            'status'         => 'posted',
        ]);
    }

    // =========================================================================
    //  التحقق من التوازن وتحديث شجرة الحسابات
    // =========================================================================

    /**
     * التحقق من توازن القيد من الذاكرة (بدون استعلام DB إضافي)
     *
     * @throws \RuntimeException إذا كان القيد غير متوازن
     */
    public function assertBalanced(float $totalDebit, float $totalCredit, string $entryNumber): void
    {
        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new \RuntimeException(
                "القيد {$entryNumber} غير متوازن – مدين: {$totalDebit}، دائن: {$totalCredit}"
            );
        }
    }

    /**
     * تحديث الرصيد الجاري (running balance) في شجرة الحسابات
     *
     * بدلاً من حساب SUM() في كل مرة (يبطأ مع نمو البيانات)
     * نحتفظ بأعمدة total_debit / total_credit / balance على account_tree
     * ونحدثها تلقائياً عند كل قيد
     *
     * ملاحظة: هذا يتطلب إضافة الأعمدة التالية لجدول account_tree:
     *   total_debit  DECIMAL(18,4) DEFAULT 0
     *   total_credit DECIMAL(18,4) DEFAULT 0
     *   balance      DECIMAL(18,4) DEFAULT 0
     */
    public function updateAccountBalance(int $accountId, float $debit, float $credit): void
    {
        DB::statement(
            'UPDATE account_trees
             SET total_debit  = total_debit  + ?,
                 total_credit = total_credit + ?,
                 balance      = balance      + ?
             WHERE id = ?',
            [$debit, $credit, $debit - $credit, $accountId]
        );
    }

    // =========================================================================
    //  ترقيم القيود
    // =========================================================================

    /**
     * توليد رقم قيد تلقائي مع قفل للحماية من التزامن (race condition)
     */
    public function generateEntryNumber(Model $sourceModel, PostingScenario $scenario): string
    {
        $prefix        = $this->getEntryPrefix($scenario);
        $dateFormatted = date('Ymd', strtotime($this->sourceModelHelper->getTransactionDate($sourceModel)));
        $pattern       = "{$prefix}-{$dateFormatted}-%";

        // lockForUpdate() يمنع قراءتين متزامنتين تحصلان على نفس الرقم
        $lastEntry = JournalEntry::where('entry_number', 'LIKE', $pattern)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $nextNumber = $lastEntry
            ? str_pad((int) substr($lastEntry->entry_number, -4) + 1, 4, '0', STR_PAD_LEFT)
            : '0001';

        return "{$prefix}-{$dateFormatted}-{$nextNumber}";
    }

    /**
     * بادئة رقم القيد حسب نوع السيناريو
     */
    public function getEntryPrefix(PostingScenario $scenario): string
    {
        return match ($scenario->operation_type) {
            'sales'             => 'INV',
            'purchase'          => 'PRC',
            'customer_payment'  => 'REC',
            'supplier_payment'  => 'PAY',
            'expense'           => 'EXP',
            'salary'            => 'SAL',
            'asset_purchase'    => 'AST',
            'vat_payment'       => 'TAX',
            'loan_payment'      => 'LON',
            'adjustment'        => 'ADJ',
            'reversal'          => 'REV',
            default             => 'JRN',
        };
    }

    /**
     * توليد رقم القيد العكسي
     */
    public function generateReverseEntryNumber(string $originalNumber): string
    {
        return 'REV-' . $originalNumber;
    }
}
