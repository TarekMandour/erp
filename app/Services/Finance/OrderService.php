<?php

namespace App\Services\Finance;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerWallet;
use App\Models\Finance\InventoryItem;
use App\Models\Finance\InventoryTransaction;
use App\Models\Finance\WarehouseTransaction;
use App\Models\Finance\Order;
use App\Models\Finance\OrderItem;
use Illuminate\Support\Facades\Log;

/**
 * OrderService
 *
 * يُطبَّق فقط عند الحالة confirmed.
 * لا يُستدعى من Observer — يُستدعى مباشرةً من OrdersController.
 *
 * apply()   → محفظة العميل (دائن) + تقليل المخزون + سجل inventory_transactions
 * reverse() → حذف قيد المحفظة + إعادة المخزون + سجل عكسي
 */
class OrderService
{
    // =========================================================================
    //  Public API
    // =========================================================================

    public function apply(Order $order): void
    {
        $order->loadMissing('items');

        $this->addCustomerWalletEntry($order);

        foreach ($order->items as $item) {
            $this->decreaseInventory($order->warehouse_id, $item);
        }

        Log::info('OrderService@apply: done', [
            'order_id' => $order->id,
            'items'    => $order->items->count(),
        ]);
    }

    public function reverse(Order $order): void
    {
        $order->loadMissing('items');

        $this->removeCustomerWalletEntry($order);

        foreach ($order->items as $item) {
            $this->increaseInventory($order->warehouse_id, $item);
        }

        Log::info('OrderService@reverse: done', [
            'order_id' => $order->id,
        ]);
    }

    // =========================================================================
    //  Customer Wallet
    // =========================================================================

    private function addCustomerWalletEntry(Order $order): void
    {
        if (! $order->customer_id) {
            return;
        }

        // احذف أي قيد مسبق لنفس الطلب (لتجنب التكرار عند إعادة التأكيد)
        CustomerWallet::where('order_id', $order->id)->delete();

        $previousBalance = CustomerWallet::where('customer_id', $order->customer_id)
            ->latest('id')
            ->value('balance') ?? 0;

        $balance = $previousBalance + $order->total;

        CustomerWallet::create([
            'customer_id' => $order->customer_id,
            'order_id'    => $order->id,
            'date'        => $order->date,
            'description' => 'طلب بيع رقم: ' . $order->order_number,
            'debit'       => $order->total,   // مبلغ مستحق علي العميل
            'credit'      => 0,
            'previous_balance' => $previousBalance,
            'balance'     => $balance,
        ]);
        if ($order->paid > 0) {
            CustomerWallet::create([
                'customer_id' => $order->customer_id,
                'order_id'    => $order->id,
                'date'        => $order->date,
                'description' => 'عن طلب بيع رقم: ' . $order->order_number,
                'debit'       => 0,
                'credit'      => $order->paid,
                'previous_balance' => $balance,
                'balance'     => $balance - $order->paid,
            ]);
        }

        $this->recalculateCustomerBalance($order->customer_id);
    }

    private function removeCustomerWalletEntry(Order $order): void
    {
        if (! $order->customer_id) {
            return;
        }

        CustomerWallet::where('order_id', $order->id)->delete();
        $this->recalculateCustomerBalance($order->customer_id);
    }

    private function recalculateCustomerBalance(int $customerId): void
    {
        $balance = 0;

        foreach (CustomerWallet::where('customer_id', $customerId)->orderBy('id')->get() as $entry) {
            $balance += $entry->debit - $entry->credit;
            $entry->balance = $balance;
            $entry->save();
        }

        Customer::where('id', $customerId)->update(['wallet_balance' => $balance]);
    }

    // =========================================================================
    //  Inventory
    // =========================================================================

    private function decreaseInventory(int $warehouseId, OrderItem $item): void
    {
        $qty = $this->resolvedQuantity($item);

        $inv = InventoryItem::where([
            'warehouse_id' => $warehouseId,
            'product_id'   => $item->product_id,
            'variant_id'   => $item->variant_id,
        ])->first();

        if ($inv) {
            $newQty = max(0, (float) $inv->quantity - $qty);
            $inv->update(['quantity' => $newQty]);
        }

        InventoryTransaction::create([
            'warehouse_id' => $warehouseId,
            'product_id'   => $item->product_id,
            'variant_id'   => $item->variant_id,
            'type'         => 'out',
            'quantity'     => $qty,
            'unit_cost'    => $item->unit_price,
            'notes'        => 'طلب بيع #' . $item->order_id,
            'created_by'   => auth()->id(),
        ]);

        WarehouseTransaction::log([
            'warehouse_id'       => $warehouseId,
            'product_id'         => $item->product_id,
            'variant_id'         => $item->variant_id,
            'unit_conversion_id' => $item->unit_conversion_id,
            'type'               => 'out',
            'quantity'           => $qty,
            'unit_cost'          => $item->unit_price,
            'reference_type'     => Order::class,
            'reference_id'       => $item->order_id,
            'notes'              => 'طلب بيع #' . $item->order_id,
        ]);
    }

    private function increaseInventory(int $warehouseId, OrderItem $item): void
    {
        $qty = $this->resolvedQuantity($item);

        $inv = InventoryItem::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'product_id'   => $item->product_id,
                'variant_id'   => $item->variant_id,
            ],
            ['quantity' => 0]
        );

        $inv->increment('quantity', $qty);

        InventoryTransaction::create([
            'warehouse_id' => $warehouseId,
            'product_id'   => $item->product_id,
            'variant_id'   => $item->variant_id,
            'type'         => 'in',
            'quantity'     => $qty,
            'unit_cost'    => $item->unit_price,
            'notes'        => 'إلغاء طلب بيع #' . $item->order_id,
            'created_by'   => auth()->id(),
        ]);

        WarehouseTransaction::log([
            'warehouse_id'       => $warehouseId,
            'product_id'         => $item->product_id,
            'variant_id'         => $item->variant_id,
            'unit_conversion_id' => $item->unit_conversion_id,
            'type'               => 'in',
            'quantity'           => $qty,
            'unit_cost'          => $item->unit_price,
            'reference_type'     => Order::class,
            'reference_id'       => $item->order_id,
            'notes'              => 'إلغاء طلب بيع #' . $item->order_id,
        ]);
    }

    private function resolvedQuantity(OrderItem $item): float
    {
        $qty = (float) $item->quantity;

        if ($item->unit_conversion_id) {
            $item->loadMissing('unitConversion');
            $factor = (float) ($item->unitConversion?->conversion_rate ?? 1);
            $qty    = $qty * $factor;
        }

        return $qty;
    }
}
