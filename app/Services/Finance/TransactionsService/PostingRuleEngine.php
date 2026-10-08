<?php

namespace App\Services\Finance\TransactionsService;

use App\Models\Finance\AccountTree;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\PostingRule;
use App\Models\Finance\Voucher;
use App\Models\Finance\BankAccount;
use App\Models\Finance\Treasury;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * تطبيق قواعد الترحيل على عملية معينة: جلب القواعد، حساب المبالغ،
 * تحديد الحسابات ومراكز التكلفة، وبناء بنود القيد الجاهزة للإدراج
 */
class PostingRuleEngine
{
    public function __construct(
        private SafeFormulaEvaluator $formulaEvaluator,
        private EntryDescriptionBuilder $descriptionBuilder,
    ) {
    }

    // =========================================================================
    //  قواعد الترحيل وبناء بنود القيد
    // =========================================================================

    /**
     * جلب قواعد الترحيل للسيناريو (مع كاش)
     */
    public function getPostingRules(int $scenarioId): Collection
    {
        return Cache::remember(
            "posting_rules:{$scenarioId}",
            now()->addMinutes(5),
            fn () => PostingRule::where('scenario_id', $scenarioId)
                ->orderBy('rule_group')
                ->orderBy('sort_order')
                ->get()
        );
    }

    /**
     * بناء بيانات بند القيد (صف واحد جاهز للإدراج) – لا يُنشئ سجلاً في DB
     * يُعيد null إذا لم يكن هناك مبلغ أو حساب صالح
     *
     * @return array<string, mixed>|null
     */
    public function buildJournalEntryLine(
        JournalEntry $journalEntry,
        PostingRule $rule,
        Model $sourceModel
    ): ?array {
        $amount = $this->calculateAmount($rule, $sourceModel);

        if ($amount <= 0) {
            return null;
        }

        $accountId = $this->determineAccountId($rule, $sourceModel);

        if (! $accountId) {
            Log::warning('PostingRuleEngine: no account found for rule', [
                'rule_id'    => $rule->id,
                'source'     => get_class($sourceModel),
                'source_id'  => $sourceModel->id,
            ]);
            return null;
        }

        return [
            'journal_entry_id' => $journalEntry->id,
            'account_tree_id'  => $accountId,
            'debit'            => $rule->debit_account_id  ? $amount : 0.0,
            'credit'           => $rule->credit_account_id ? $amount : 0.0,
            'cost_center_id'   => $this->determineCostCenter($rule, $sourceModel),
            'description'      => $this->descriptionBuilder->generateItemDescription($rule, $sourceModel),
            'created_at'       => now(),
            'updated_at'       => now(),
        ];
    }

    // =========================================================================
    //  حساب المبالغ
    // =========================================================================

    /**
     * حساب المبلغ حسب نوع القاعدة
     */
    public function calculateAmount(PostingRule $rule, Model $sourceModel): float
    {
        $baseAmount = $this->getFieldValue($sourceModel, $rule->amount_field ?: 'total_amount');

        return match ($rule->amount_type) {
            'fixed'         => (float) ($rule->amount_value ?? 0),
            'total',
            'subtotal',
            'tax'           => $baseAmount,
            'percentage'    => $baseAmount * ((float) ($rule->amount_value ?? 0) / 100),
            'quantity_cost' => $this->calculateQuantityCost($sourceModel),
            'formula'       => $this->formulaEvaluator->evaluateFormula($rule->formula, $sourceModel),
            default         => $baseAmount,
        };
    }

    /**
     * حساب تكلفة الكميات (للمبيعات والمشتريات)
     */
    public function calculateQuantityCost(Model $sourceModel): float
    {
        if (! method_exists($sourceModel, 'items')) {
            return 0.0;
        }

        return (float) $sourceModel->items->sum(
            fn ($item) => ($item->quantity ?? 0) * ($item->unit_cost ?? $item->purchase_price ?? 0)
        );
    }

    // =========================================================================
    //  تحديد الحسابات ومراكز التكلفة
    // =========================================================================

    /**
     * تحديد معرف الحساب المناسب للبند
     */
    public function determineAccountId(PostingRule $rule, Model $sourceModel): ?int
    {
        $defaultAccountId = $rule->debit_account_id ?: $rule->credit_account_id;

        /*
         * أهم قاعدة في اختيار الحساب:
         *
         * إذا كان السند يحتوي على financial_account_id، وكان الحساب
         * الافتراضي لهذه القاعدة مرتبطاً فعلياً بخزنة أو حساب بنكي،
         * فإن اختيار المحاسب يكون هو الحساب المستخدم في القيد.
         *
         * نضع هذا قبل account_source لأن بعض قواعد السيناريو قد تحتوي
         * على conditions/account_source، وإلا سيتم إرجاع الحساب الديناميكي
         * قبل أن يصل التنفيذ إلى الـ financial account override.
         */
        if (
            $sourceModel instanceof Voucher
            && $sourceModel->financial_account_id
            && $this->isVoucherFinancialRule($rule, $sourceModel)
        ) {
            $selectedAccountId = (int) $sourceModel->financial_account_id;

            if ($this->isFinancialAccount($selectedAccountId)) {
                return $selectedAccountId;
            }
        }

        /*
         * الحسابات الديناميكية مثل العملاء والموردين.
         * لا تتأثر باختيار البند المالي.
         */
        if ($rule->conditions) {
            $conditions = json_decode($rule->conditions, true);

            if (isset($conditions['account_source'])) {
                return $this->getDynamicAccount(
                    $sourceModel,
                    $conditions['account_source']
                );
            }
        }

        return $defaultAccountId ? (int) $defaultAccountId : null;
    }

    /**
     * هل هذه القاعدة هي الطرف المالي في سند القبض/الصرف؟
     *
     * سند القبض: الطرف المالي مدين.
     * سند الصرف: الطرف المالي دائن.
     *
     * لا نعتمد على كون الحساب الافتراضي مربوطاً فعلياً بخزنة/بنك،
     * لأن الحساب الافتراضي في السيناريو قد يكون حساباً عاماً (مثل 1101/1102)
     * بينما الحساب المختار من المستخدم هو الحساب الفرعي الفعلي.
     */
    private function isVoucherFinancialRule(PostingRule $rule, Voucher $voucher): bool
    {
        if ($voucher->isReceiptType()) {
            return (bool) $rule->debit_account_id && ! $rule->credit_account_id;
        }

        if ($voucher->isPaymentType()) {
            return (bool) $rule->credit_account_id && ! $rule->debit_account_id;
        }

        return false;
    }

    /**
     * هل حساب شجرة الحسابات مرتبط فعلياً بخزنة أو حساب بنكي؟
     */
    private function isFinancialAccount(int $accountTreeId): bool
    {
        return BankAccount::where('account_tree_id', $accountTreeId)->exists()
            || Treasury::where('account_tree_id', $accountTreeId)
                ->where('is_active', true)
                ->exists();
    }

    /**
     * Override للحساب المالي فقط في سندات القبض والصرف.
     *
     * أبقينا الدالة مستقلة لأن اختيار الحساب المالي هو سلوك خاص بالسند،
     * بينما بقية قواعد الـ Scenario تستمر في العمل بشكلها الطبيعي.
     */
    private function resolveVoucherFinancialAccount(int $defaultAccountId, Model $sourceModel): int
    {
        if (
            ! $sourceModel instanceof Voucher
            || ! $sourceModel->financial_account_id
            || ! $this->isFinancialAccount($defaultAccountId)
        ) {
            return $defaultAccountId;
        }

        $selectedAccountId = (int) $sourceModel->financial_account_id;

        return $this->isFinancialAccount($selectedAccountId)
            ? $selectedAccountId
            : $defaultAccountId;
    }

    /**
     * جلب حساب ديناميكي (مثل حساب العميل أو المورد)
     * مخزّن في كاش لتقليل استعلامات DB المتكررة
     */
    public function getDynamicAccount(Model $sourceModel, array $sourceConfig): ?int
    {
        $field       = $sourceConfig['field'] ?? null;
        $accountType = $sourceConfig['account_type'] ?? null;

        if (! $field || ! property_exists($sourceModel, $field)) {
            return null;
        }

        $accountCode = match ($accountType) {
            'customer' => '1130',  // حساب العملاء
            'supplier' => '2110',  // حساب الموردين
            default    => null,
        };

        if (! $accountCode) {
            return null;
        }

        return Cache::remember(
            "account_tree_code:{$accountCode}",
            now()->addHour(),
            fn () => AccountTree::where('code', $accountCode)->value('id')
        );
    }

    /**
     * الحصول على قيمة حقل من الموديل مع دعم الحقول المتداخلة (مثل: 'data.total')
     */
    public function getFieldValue(Model $model, string $field): float
    {
        if (! str_contains($field, '.')) {
            return (float) ($model->{$field} ?? 0);
        }

        $value = $model;
        foreach (explode('.', $field) as $part) {
            $value = is_array($value) ? ($value[$part] ?? 0) : ($value->{$part} ?? 0);
        }

        return (float) $value;
    }

    /**
     * تحديد مركز التكلفة للبند
     */
    public function determineCostCenter(PostingRule $rule, Model $sourceModel): ?int
    {
        if ($rule->cost_center_source === 'fixed' && $rule->fixed_cost_center_id) {
            return $rule->fixed_cost_center_id;
        }

        if (method_exists($sourceModel, 'getCostCenterId')) {
            return $sourceModel->getCostCenterId();
        }

        return $sourceModel->cost_center_id ?? null;
    }
}
