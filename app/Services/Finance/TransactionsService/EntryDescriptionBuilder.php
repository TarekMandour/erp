<?php

namespace App\Services\Finance\TransactionsService;

use App\Models\Finance\PostingRule;
use App\Models\Finance\PostingScenario;
use Illuminate\Database\Eloquent\Model;

/**
 * توليد الأوصاف النصية لرأس القيد وبنوده
 *
 * لا يحتاج هذا الكلاس أي تعديل عند إضافة موديل جديد.
 * كل موديل يُعرَّف وصفه عبر getPostableDescription() في trait HasJournalEntry.
 */
class EntryDescriptionBuilder
{
    /**
     * توليد وصف رأس القيد
     */
    public function generateDescription(Model $sourceModel, PostingScenario $scenario): string
    {
        $typeName = $scenario->name ?? $scenario->code;

        if (method_exists($sourceModel, 'getPostableDescription')) {
            $details = $sourceModel->getPostableDescription();
            if ($details !== '') {
                return "{$typeName} - {$details}";
            }
        }

        $details = $sourceModel->description ?? '';

        return "{$typeName} - {$details}";
    }

    /**
     * وصف بند القيد
     */
    public function generateItemDescription(PostingRule $rule, Model $sourceModel): ?string
    {
        if (method_exists($sourceModel, 'getPostableDescription')) {
            $desc = $sourceModel->getPostableDescription();
            if ($desc !== '') {
                return $desc;
            }
        }

        return $sourceModel->description ?? null;
    }
}
