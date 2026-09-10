<?php

namespace App\Services\Finance\TransactionsService;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * حاسبة معادلات آمنة (بدون eval)
 *
 * تستبدل متغيرات النموذج بقيمها الفعلية ثم تحلّل التعبير الرياضي
 * يدوياً عبر Recursive Descent Parser يدعم: + - * / ( ) والأعداد العشرية/السالبة
 *
 * هذا الكلاس مستقل تماماً عن باقي الخدمة ويمكن اختباره بشكل منفرد
 */
class SafeFormulaEvaluator
{
    /**
     * تقييم معادلة بشكل آمن بعد استبدال متغيرات الموديل بقيمها
     */
    public function evaluateFormula(?string $formula, Model $sourceModel): float
    {
        if (! $formula) {
            return 0.0;
        }

        $expression = $this->replaceVariables($formula, $sourceModel);

        try {
            return $this->safeEvaluate($expression);
        } catch (\Throwable $e) {
            Log::warning('SafeFormulaEvaluator: formula evaluation failed', [
                'formula'    => $formula,
                'expression' => $expression,
                'error'      => $e->getMessage(),
            ]);
            return 0.0;
        }
    }

    /**
     * استبدال المتغيرات في المعادلة بقيمها الفعلية
     */
    protected function replaceVariables(string $formula, Model $sourceModel): string
    {
        $map = [
            'total_amount' => (float) ($sourceModel->total_amount ?? 0),
            'total'        => (float) ($sourceModel->total ?? 0),
            'subtotal'     => (float) ($sourceModel->subtotal ?? 0),
            'tax'          => (float) ($sourceModel->tax ?? 0),
            'discount'     => (float) ($sourceModel->discount ?? 0),
            'shipping'     => (float) ($sourceModel->shipping_cost ?? 0),
            'paid'         => (float) ($sourceModel->paid ?? 0),
        ];

        // Replace {{variable}} syntax first
        foreach ($map as $key => $value) {
            $formula = str_replace('{{' . $key . '}}', $value, $formula);
        }

        // Replace plain word syntax using word boundaries to avoid partial matches
        foreach ($map as $key => $value) {
            $formula = preg_replace('/\b' . preg_quote($key, '/') . '\b/', $value, $formula);
        }

        return $formula;
    }

    /**
     * حاسبة رياضية آمنة تدعم: + - * / ( ) والأعداد العشرية
     * لا تستخدم eval() – تحلل التعبير يدوياً
     *
     * @throws \InvalidArgumentException إذا احتوى التعبير على رموز غير مسموح بها
     */
    public function safeEvaluate(string $expression): float
    {
        // السماح فقط بالأرقام والعمليات الحسابية الأساسية والفراغات
        if (! preg_match('/^[\d\s\+\-\*\/\(\)\.]+$/', $expression)) {
            throw new \InvalidArgumentException(
                "Formula contains invalid characters: {$expression}"
            );
        }

        // إزالة المسافات
        $expression = preg_replace('/\s+/', '', $expression);

        $pos = 0;
        return $this->parseExpression($expression, $pos);
    }

    /**
     * مُحلل تعابير رياضية تعاودي (Recursive Descent Parser)
     * يدعم: + - * / ( ) والأعداد السالبة/العشرية
     */
    private function parseExpression(string $expr, int &$pos): float
    {
        $result = $this->parseTerm($expr, $pos);

        while ($pos < strlen($expr) && in_array($expr[$pos], ['+', '-'], true)) {
            $op = $expr[$pos++];
            $term = $this->parseTerm($expr, $pos);
            $result = $op === '+' ? $result + $term : $result - $term;
        }

        return $result;
    }

    private function parseTerm(string $expr, int &$pos): float
    {
        $result = $this->parseFactor($expr, $pos);

        while ($pos < strlen($expr) && in_array($expr[$pos], ['*', '/'], true)) {
            $op = $expr[$pos++];
            $factor = $this->parseFactor($expr, $pos);
            if ($op === '/' && $factor == 0) {
                throw new \DivisionByZeroError('Division by zero in formula');
            }
            $result = $op === '*' ? $result * $factor : $result / $factor;
        }

        return $result;
    }

    private function parseFactor(string $expr, int &$pos): float
    {
        // معالجة الأقواس
        if ($pos < strlen($expr) && $expr[$pos] === '(') {
            $pos++; // تخطي (
            $result = $this->parseExpression($expr, $pos);
            if ($pos < strlen($expr) && $expr[$pos] === ')') {
                $pos++; // تخطي )
            }
            return $result;
        }

        // معالجة الإشارة السالبة
        $sign = 1;
        if ($pos < strlen($expr) && $expr[$pos] === '-') {
            $sign = -1;
            $pos++;
        }

        // قراءة الرقم
        $numStr = '';
        while ($pos < strlen($expr) && (is_numeric($expr[$pos]) || $expr[$pos] === '.')) {
            $numStr .= $expr[$pos++];
        }

        if ($numStr === '') {
            throw new \InvalidArgumentException("Unexpected character at position {$pos} in formula");
        }

        return $sign * (float) $numStr;
    }
}
