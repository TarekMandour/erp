<?php

namespace App\Services\Finance;

use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryItem;
use App\Services\Finance\TransactionsService\EntryDescriptionBuilder;
use App\Services\Finance\TransactionsService\JournalEntryBuilder;
use App\Services\Finance\TransactionsService\PostingRuleEngine;
use App\Services\Finance\TransactionsService\ScenarioResolver;
use App\Services\Finance\TransactionsService\SourceModelHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * خدمة موحدة لإنشاء القيود المحاسبية لجميع أنواع العمليات
 *
 * هذا الكلاس هو المنسّق (orchestrator) فقط. منطق التنفيذ مقسّم إلى:
 *  - ScenarioResolver       : اكتشاف نوع العملية وتحديد السيناريو
 *  - PostingRuleEngine      : تطبيق قواعد الترحيل وحساب المبالغ والحسابات
 *  - SafeFormulaEvaluator   : تقييم المعادلات الرياضية بأمان (بدون eval)
 *  - JournalEntryBuilder    : إنشاء رأس القيد، ترقيمه، والتحقق من توازنه
 *  - EntryDescriptionBuilder: توليد الأوصاف النصية للقيد وبنوده
 *  - SourceModelHelper      : استخراج بيانات من الموديل المصدر وربطه بالقيد
 *
 * جميع الكلاسات أعلاه تُحقن عبر الـ constructor لتسهيل الاختبار (mocking)
 */
class TransactionsService
{
    public function __construct(
        private ScenarioResolver $scenarioResolver,
        private PostingRuleEngine $ruleEngine,
        private JournalEntryBuilder $entryBuilder,
        private EntryDescriptionBuilder $descriptionBuilder,
        private SourceModelHelper $sourceModelHelper,
    ) {
    }

    // =========================================================================
    //  الواجهة العامة
    // =========================================================================

    /**
     * نقطة الدخول الرئيسية – تنشئ قيداً محاسبياً لأي عملية
     *
     * @throws \Throwable
     */
    public function process(Model $sourceModel, ?string $scenarioCode = null): JournalEntry
    {
        $operationType = $this->scenarioResolver->detectOperationType($sourceModel);
        $scenario      = $this->scenarioResolver->determineScenario($sourceModel, $operationType, $scenarioCode);

        if (! $scenario) {
            return $this->createFallbackJournalEntry($sourceModel, $operationType);
        }

        $rules = $this->ruleEngine->getPostingRules($scenario->id);

        if ($rules->isEmpty()) {
            return $this->createFallbackJournalEntry($sourceModel, $operationType);
        }

        return DB::transaction(function () use ($sourceModel, $scenario, $rules) {
            $groupedRules  = $rules->groupBy('rule_group');
            $createdEntries = [];

            foreach ($groupedRules as $groupRules) {
                $journalEntry = $this->entryBuilder->createJournalEntryHeader($sourceModel, $scenario);

                // نجمع الأرصدة في الذاكرة لنتحقق من التوازن بدون استعلام إضافي
                $totalDebit  = 0.0;
                $totalCredit = 0.0;
                $itemsToInsert = [];

                foreach ($groupRules as $rule) {
                    $line = $this->ruleEngine->buildJournalEntryLine($journalEntry, $rule, $sourceModel);
                    if ($line === null) {
                        continue;
                    }
                    $totalDebit  += $line['debit'];
                    $totalCredit += $line['credit'];
                    $itemsToInsert[] = $line;
                }

                // التحقق من التوازن قبل الإدراج
                $this->entryBuilder->assertBalanced($totalDebit, $totalCredit, $journalEntry->entry_number);

                // إدراج جميع البنود بعملية واحدة
                JournalEntryItem::insert($itemsToInsert);

                // تحديث الأرصدة الجارية في شجرة الحسابات
                foreach ($itemsToInsert as $line) {
                    $this->entryBuilder->updateAccountBalance(
                        $line['account_tree_id'],
                        $line['debit'],
                        $line['credit']
                    );
                }

                $createdEntries[] = $journalEntry;
            }

            $this->sourceModelHelper->linkSourceToJournalEntry($sourceModel, $createdEntries[0] ?? null);

            return $createdEntries[0] ?? $this->createFallbackJournalEntry(
                $sourceModel,
                $this->scenarioResolver->detectOperationType($sourceModel)
            );
        });
    }

    /**
     * إلغاء قيد محاسبي لعملية معينة
     *
     * @throws \Throwable
     */
    public function reverse(Model $sourceModel): ?JournalEntry
    {
        $journalEntryId = $this->sourceModelHelper->getLinkedJournalEntryId($sourceModel);

        if (! $journalEntryId) {
            return null;
        }

        $oldJournalEntry = JournalEntry::with('items')->find($journalEntryId);

        if (! $oldJournalEntry || $oldJournalEntry->status === 'canceled') {
            return null;
        }

        return DB::transaction(function () use ($sourceModel, $oldJournalEntry) {
            $reverseEntry = JournalEntry::create([
                'entry_number'  => $this->entryBuilder->generateReverseEntryNumber($oldJournalEntry->entry_number),
                'entry_type'    => 'reversal',
                'date'          => now()->toDateString(),
                'description'   => 'إلغاء قيد: ' . $oldJournalEntry->entry_number,
                'reference_type' => get_class($sourceModel),
                'reference_id'  => $sourceModel->id,
                'is_reversed'   => true,
                'reversed_from' => $oldJournalEntry->id,
                'created_by'    => auth()->id(),
                'status'        => 'posted',
            ]);

            // عكس البنود بعملية إدراج واحدة
            $reversedItems = $oldJournalEntry->items->map(fn ($item) => [
                'journal_entry_id' => $reverseEntry->id,
                'account_tree_id'  => $item->account_tree_id,
                'debit'            => $item->credit,       // عكس الأطراف
                'credit'           => $item->debit,
                'cost_center_id'   => $item->cost_center_id,
                'description'      => 'إلغاء: ' . ($item->description ?? ''),
                'created_at'       => now(),
                'updated_at'       => now(),
            ])->toArray();

            JournalEntryItem::insert($reversedItems);

            // تحديث الأرصدة الجارية عكسياً
            foreach ($reversedItems as $line) {
                $this->entryBuilder->updateAccountBalance(
                    $line['account_tree_id'],
                    $line['debit'],
                    $line['credit']
                );
            }

            $oldJournalEntry->update(['status' => 'canceled']);

            // لا نحاول فك الربط إذا كان الموديل المصدر محذوفاً فعلاً من DB
            if ($sourceModel->exists) {
                $this->sourceModelHelper->unlinkSourceFromJournalEntry($sourceModel);
            }

            return $reverseEntry;
        });
    }

    /**
     * إعادة إنشاء القيد المحاسبي (بعد التعديل على المستند الأصلي)
     *
     * @throws \Throwable
     */
    public function regenerate(Model $sourceModel): JournalEntry
    {
        $this->reverse($sourceModel);

        return $this->process($sourceModel);
    }

    // =========================================================================
    //  القيد الاحتياطي
    // =========================================================================

    /**
     * إنشاء قيد احتياطي عندما لا يوجد سيناريو أو قواعد
     * الحالة: draft – يجب مراجعته يدوياً
     *
     * @throws \Throwable
     */
    protected function createFallbackJournalEntry(Model $sourceModel, string $operationType): JournalEntry
    {
        return DB::transaction(function () use ($sourceModel, $operationType) {
            $entryNumber = 'FALL-' . date('YmdHis') . '-' . $sourceModel->id;

            // $operationType هو تصنيف داخلي (voucher/order/wallet...) يُستخدم فقط
            // لاختيار السيناريو، وليس بالضرورة قيمة صالحة لعمود entry_type
            // (ENUM محدود في الـ migration) - لذلك يجب تحويله أولاً
            $entryType = $this->scenarioResolver->resolveEntryType($sourceModel, $operationType);

            $journalEntry = JournalEntry::create([
                'entry_number'   => $entryNumber,
                'entry_type'     => $entryType,
                'date'           => $this->sourceModelHelper->getTransactionDate($sourceModel),
                'description'    => 'قيد احتياطي – يرجى مراجعة الترحيل: ' . ($sourceModel->description ?? ''),
                'reference_type' => get_class($sourceModel),
                'reference_id'   => $sourceModel->id,
                'created_by'     => $this->sourceModelHelper->getCreatedBy($sourceModel),
                'status'         => 'draft',
            ]);

            $cashAccountId = Cache::remember(
                'account_tree_code:1110',
                now()->addHour(),
                fn () => \App\Models\Finance\AccountTree::where('code', '1110')->value('id')
            );

            $amount = $this->sourceModelHelper->getTransactionAmount($sourceModel);

            JournalEntryItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_tree_id'  => $cashAccountId,
                'debit'            => $this->sourceModelHelper->isReceiptType($sourceModel) ? $amount : 0.0,
                'credit'           => $this->sourceModelHelper->isPaymentType($sourceModel) ? $amount : 0.0,
                'description'      => 'قيد احتياطي – يرجى مراجعة الترحيل',
            ]);

            $this->sourceModelHelper->linkSourceToJournalEntry($sourceModel, $journalEntry);

            Log::warning('TransactionsService: fallback journal entry created', [
                'entry_number' => $entryNumber,
                'source'       => get_class($sourceModel),
                'source_id'    => $sourceModel->id,
                'operation'    => $operationType,
            ]);

            return $journalEntry;
        });
    }
}