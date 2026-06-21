<?php

namespace App\Observers;

use App\Events\Finance\Orders\OrderEvent;
use App\Models\Finance\Order;
use Illuminate\Support\Facades\Log;

/**
 * OrderObserver
 *
 * المسؤولية الوحيدة: إطلاق الأحداث فقط.
 * TransactionsService لا يُستدعى هنا مباشرة.
 */
class OrderObserver
{
    /**
     * الحقول المالية التي تستوجب إعادة ترحيل القيد عند تغييرها
     */
    private array $financialFields = [
        'subtotal',
        'discount',
        'tax',
        'shipping_cost',
        'total',
        'payment_type',
        'customer_id',
        'coupon_discount',
        'offer_discount',
    ];

    public function created(Order $order): void
    {
        // التطبيق يتم مباشرةً من OrdersController::store بناءً على الحالة
    }

    public function updated(Order $order): void
    {
        // التطبيق يتم مباشرةً من OrdersController::update بناءً على الحالة
    }

    public function deleted(Order $order): void
    {
        // العكس يتم مباشرةً من OrdersController::destroy
    }

    public function restored(Order $order): void
    {
        if (! in_array($order->status, Order::$lockedStatuses, true)) {
            Log::info('OrderObserver@restored: dispatching OrderEvent create', [
                'order_id' => $order->id,
            ]);

            OrderEvent::dispatch($order, 'create');
        }
    }

    private function financialFieldsChanged(Order $order): bool
    {
        return (bool) array_intersect($this->financialFields, array_keys($order->getChanges()));
    }
}
