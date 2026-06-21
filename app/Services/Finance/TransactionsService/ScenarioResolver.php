<?php

namespace App\Services\Finance\TransactionsService;

use App\Models\Finance\PostingScenario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * ?????? ??? ??????? ?????? ??????? ??????? ??????? ???
 *
 * ?? ????? ??? ?????? ?? ????? ??? ????? ????? ????.
 * ?? ?????? ????? ???????? ??????? ??? ?????? ??? trait HasJournalEntry.
 */
class ScenarioResolver
{
    /**
     * ????? ??????? ????? journal_entries.entry_type (ENUM)
     * ??? ?? ????? ?????? ??? enum() ?? migration ????? ??????.
     */
    public const VALID_ENTRY_TYPES = [
        'sales', 'sales_return', 'sales_discount', 'sales_installment',
        'purchase', 'purchase_return', 'purchase_discount',
        'inventory_in', 'inventory_out', 'inventory_transfer', 'inventory_adjustment',
        'inventory_write_off', 'inventory_revaluation',
        'cash_deposit', 'cash_withdraw', 'cash_transfer',
        'bank_deposit', 'bank_withdraw', 'bank_transfer',
        'cheque_received', 'cheque_issued', 'cheque_cashed',
        'wallet_deposit', 'wallet_withdraw', 'wallet_payment', 'wallet_refund',
        'customer_payment', 'customer_credit_note', 'customer_debit_note',
        'supplier_payment', 'supplier_credit_note', 'supplier_debit_note',
        'expense', 'revenue', 'accrued_expense', 'prepaid_expense',
        'depreciation', 'amortization',
        'salary', 'salary_advance', 'salary_loan', 'overtime', 'bonus', 'commission',
        'asset_purchase', 'asset_sale', 'asset_disposal', 'asset_depreciation',
        'vat_input', 'vat_output', 'vat_payment', 'vat_refund',
        'income_tax', 'withholding_tax',
        'loan_received', 'loan_payment', 'loan_interest',
        'capital_increase', 'capital_decrease', 'dividend_paid',
        'internal_transfer', 'cost_allocation', 'profit_transfer',
        'opening_balance', 'adjustment', 'revaluation', 'closing_entry',
        'refund', 'write_off', 'reversal', 'correction',
    ];

    /**
     * ?????? ??? ??????? ????????
     * ??????? ??????? ??? getOperationType() ??? ??? ?????? HasJournalEntry
     */
    public function detectOperationType(Model $sourceModel): string
    {
        if (method_exists($sourceModel, 'getOperationType')) {
            return $sourceModel->getOperationType();
        }

        return 'adjustment';
    }

    /**
     * ????? ????????? ??????? ??????? ?? ??? ?????? ????????? DB
     */
    public function determineScenario(
        Model $sourceModel,
        string $operationType,
        ?string $forcedCode = null
    ): ?PostingScenario {
        if ($forcedCode) {
            return $this->findScenarioByCode($forcedCode);
        }

        $code     = $this->buildScenarioCode($sourceModel, $operationType);
        $scenario = $this->findScenarioByCode($code);

        if (! $scenario) {
            $fallback = method_exists($sourceModel, 'getFallbackScenarioCode')
                ? $sourceModel->getFallbackScenarioCode()
                : 'ADJUSTMENT';

            $scenario = $this->findScenarioByCode($fallback);
        }

        return $scenario;
    }

    /**
     * ????? ?? ??????? ????? ?? ?????? ?? ??? (5 ?????)
     */
    public function findScenarioByCode(string $code): ?PostingScenario
    {
        return Cache::remember(
            "posting_scenario:{$code}",
            now()->addMinutes(5),
            fn () => PostingScenario::where('code', $code)->where('is_active', true)->first()
        );
    }

    /**
     * ???? ??? ????????? — ??????? ??????? ??? getScenarioCode()
     * ?? ????? ????? ??? ????? ????? ????
     */
    public function buildScenarioCode(Model $sourceModel, string $operationType): string
    {
        if (method_exists($sourceModel, 'getScenarioCode')) {
            return $sourceModel->getScenarioCode();
        }

        return 'ADJUSTMENT';
    }

    /**
     * ????? ??? ??????? ??? ???? ????? ????? journal_entries.entry_type
     * ??????? ??????? ??? getEntryType() ??? ??? ?????? HasJournalEntry
     */
    public function resolveEntryType(Model $sourceModel, string $operationType): string
    {
        if (method_exists($sourceModel, 'getEntryType')) {
            $type = $sourceModel->getEntryType();
            if (in_array($type, self::VALID_ENTRY_TYPES, true)) {
                return $type;
            }
        }

        if (in_array($operationType, self::VALID_ENTRY_TYPES, true)) {
            return $operationType;
        }

        return 'adjustment';
    }
}
