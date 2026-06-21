<?php

namespace App\Observers;

use App\Events\Finance\Purchases\PurchaseEvent;
use App\Models\Finance\Purchase;
use Illuminate\Support\Facades\Log;

/**
 * PurchaseObserver
 *
 * المسؤولية الوحيدة: إطلاق الأحداث فقط.
 * TransactionsService لا يُستدعى هنا مباشرة.
 */
class PurchaseObserver
{
    /**
     * الحقول المالية التي تستوجب إعادة ترحيل القيد عند تغييرها
     */
    private array $financialFields = [];

    public function created(Purchase $purchase): void
    {
        // الحدث يُطلق يدوياً من PurchasesController::store بعد اكتمال الأرقام النهائية
        // لا نطلقه هنا لتجنب إنشاء قيد بأرقام صفرية (قبل recalcTotals)
    }

    public function updated(Purchase $purchase): void
    {
        // القيد يُعاد إنشاؤه مباشرةً من PurchasesController::update (synchronous)
        // لا نطلق حدثاً هنا لتجنب تكرار القيود عبر الـ queue
    }

    public function deleted(Purchase $purchase): void
    {
        // عكس القيد والمحفظة والمخزون يتم مباشرةً من PurchasesController::destroy
        // لا نطلق أحداثاً هنا لتجنب التكرار
    }

    public function restored(Purchase $purchase): void
    {
        Log::info('PurchaseObserver@restored: dispatching PurchaseEvent create', [
            'purchase_id' => $purchase->id,
        ]);

        PurchaseEvent::dispatch($purchase, 'create');
    }

    private function financialFieldsChanged(Purchase $purchase): bool
    {
        return false;
    }
}
