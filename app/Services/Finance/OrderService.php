<?php

namespace App\Services\Finance;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerWallet;
use App\Models\Finance\InventoryItem;
use App\Models\Finance\InventoryTransaction;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\Order;
use App\Models\Finance\OrderItem;
use App\Models\Finance\Product;
use App\Models\Finance\WarehouseTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * آثار طلب البيع (QA الدفعة 6).
 *
 * تُطبَّق للحالات الفعّالة (مؤكد/قيد التجهيز/تم الشحن/تم التسليم) وتُعكس كاملة عند
 * الإلغاء أو الرجوع لمسودة أو الحذف. يجب الاستدعاء داخل DB::transaction واحدة
 * مع حفظ الطلب: فشل أي جزء (مخزون/قيد) يلغي كل شيء.
 *
 * apply()   → خصم المخزون بمتوسط التكلفة + محفظة العميل + قيد المبيعات وقيد التكلفة
 * reverse() → إرجاع المخزون بنفس تكلفته + حذف حركات المحفظة + عكس كل قيود الطلب
 */
class OrderService
{
    public function __construct(
        private TransactionsService $transactions,
        private InventoryMovementService $inventory,
    ) {
    }

    public function apply(Order $order): void
    {
        $order->load('items.unitConversion');

        foreach ($order->items as $item) {
            $this->decreaseInventory($order, $item);
        }

        $this->addCustomerWalletEntry($order);

        // القيد بعد تثبيت تكلفة البنود (quantity_cost = الكمية × تكلفة وحدة البيع)
        $this->transactions->process($order->fresh(['items']));
    }

    public function reverse(Order $order): void
    {
        $order->load('items.unitConversion');

        $this->reverseJournalEntries($order);
        $this->removeCustomerWalletEntry($order);
        $this->returnInventory($order);
    }

    /**
     * عكس كل القيود الفعّالة للطلب (المبيعات + التكلفة) — reverse() الأصلية تعكس آخر قيد فقط
     */
    public function reverseJournalEntries(Order $order): void
    {
        $ids = JournalEntry::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('is_reversed', false)
            ->where('status', 'posted')
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $id) {
            $this->transactions->reverseEntry(JournalEntry::with('items')->find($id), $order);
        }
    }

    // =========================================================================
    //  محفظة العميل (مدين = مستحق على العميل، دائن = المحصّل) — تطابق حساب 1104
    // =========================================================================

    private function addCustomerWalletEntry(Order $order): void
    {
        if (! $order->customer_id) {
            return;
        }

        CustomerWallet::where('order_id', $order->id)->delete();

        CustomerWallet::create([
            'customer_id'      => $order->customer_id,
            'order_id'         => $order->id,
            'date'             => $order->date,
            'description'      => 'طلب بيع رقم: ' . $order->order_number,
            'debit'            => $order->total,
            'credit'           => 0,
            'balance'          => 0,
        ]);

        if ((float) $order->paid > 0) {
            CustomerWallet::create([
                'customer_id'      => $order->customer_id,
                'order_id'         => $order->id,
                'date'             => $order->date,
                'description'      => 'المحصّل عن طلب بيع رقم: ' . $order->order_number,
                'debit'            => 0,
                'credit'           => $order->paid,
                'balance'          => 0,
            ]);
        }

        $this->recalculateCustomerBalance($order->customer_id);
    }

    private function removeCustomerWalletEntry(Order $order): void
    {
        $customerIds = CustomerWallet::where('order_id', $order->id)->pluck('customer_id')->push($order->customer_id)->filter()->unique();

        CustomerWallet::where('order_id', $order->id)->delete();

        foreach ($customerIds as $customerId) {
            $this->recalculateCustomerBalance((int) $customerId);
        }
    }

    public function recalculateCustomerBalance(int $customerId): void
    {
        $balance = 0;

        foreach (CustomerWallet::where('customer_id', $customerId)->orderBy('id')->get() as $entry) {
            $balance += $entry->debit - $entry->credit;
            $entry->balance = $balance;
            $entry->saveQuietly();
        }

        Customer::where('id', $customerId)->update(['wallet_balance' => $balance]);
    }

    // =========================================================================
    //  المخزون
    // =========================================================================

    private function decreaseInventory(Order $order, OrderItem $item): void
    {
        $product = Product::findOrFail($item->product_id);
        $rate    = (float) ($item->unitConversion?->conversion_rate ?: 1);
        $qty     = round((float) $item->quantity * $rate, 6);

        $inv = InventoryItem::where('warehouse_id', $order->warehouse_id)
            ->where('product_id', $item->product_id)
            ->when($item->variant_id, fn ($q) => $q->where('variant_id', $item->variant_id), fn ($q) => $q->whereNull('variant_id'))
            ->lockForUpdate()
            ->first();

        $available = (float) ($inv?->quantity ?? 0);
        if ($qty > $available + 0.000001) {
            throw ValidationException::withMessages([
                'items' => "المنتج «{$product->name}» — الكمية المطلوبة ({$this->fmt($qty)}) تتجاوز المخزون المتاح ({$this->fmt($available)}).",
            ]);
        }

        // التكلفة بمتوسط التكلفة المرجّح الحالي (لكل وحدة أساس)، والمتوسط لا يتغير بالصرف
        $target   = $this->inventory->costTarget($product, $item->variant_id);
        $baseCost = (float) ($target->average_cost ?: ($target->cost_price ?? 0) ?: ($product->average_cost ?: $product->cost_price));

        $inv->update(['quantity' => round($available - $qty, 3)]);
        $item->forceFill(['unit_cost' => round($baseCost * $rate, 4)])->saveQuietly();

        $this->logMovement($order, $item, 'out', $qty, $baseCost, 'طلب بيع ' . $order->order_number);
    }

    /**
     * إرجاع ما خرج فعلاً للطلب (صافي حركات المستودع المسجلة) بنفس تكلفته
     */
    private function returnInventory(Order $order): void
    {
        $moves = WarehouseTransaction::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->get()
            ->groupBy(fn ($m) => $m->product_id . '_' . ($m->variant_id ?? ''));

        foreach ($moves as $group) {
            $first = $group->first();
            $out   = $group->where('type', 'out')->sum('quantity');
            $in    = $group->where('type', 'in')->sum('quantity');
            $net   = round($out - $in, 6);
            if ($net <= 0) {
                continue;
            }

            $outRows = $group->where('type', 'out');
            $cost    = $out > 0 ? $outRows->sum(fn ($m) => $m->quantity * $m->unit_cost) / $out : 0;
            $product = Product::find($first->product_id);

            if ($product && $cost > 0) {
                $this->inventory->costIn($this->inventory->costTarget($product, $first->variant_id), $first->product_id, $first->variant_id, $net, $cost);
            }

            $inv = InventoryItem::firstOrCreate(
                ['warehouse_id' => $first->warehouse_id, 'product_id' => $first->product_id, 'variant_id' => $first->variant_id],
                ['quantity' => 0]
            );
            $inv->increment('quantity', $net);

            $item = $order->items->first(fn ($i) => (int) $i->product_id === (int) $first->product_id
                && (int) $i->variant_id === (int) $first->variant_id) ?? new OrderItem([
                    'order_id' => $order->id, 'product_id' => $first->product_id, 'variant_id' => $first->variant_id,
                ]);

            $this->logMovement($order, $item, 'in', $net, round($cost, 4), 'إلغاء/تعديل طلب بيع ' . $order->order_number, (int) $first->warehouse_id);
        }
    }

    private function logMovement(Order $order, OrderItem $item, string $type, float $qty, float $baseCost, string $notes, ?int $warehouseId = null): void
    {
        $warehouseId ??= (int) $order->warehouse_id;

        InventoryTransaction::create([
            'warehouse_id' => $warehouseId,
            'product_id'   => $item->product_id,
            'variant_id'   => $item->variant_id,
            'type'         => $type,
            'quantity'     => $qty,
            'unit_cost'    => $baseCost,
            'notes'        => $notes,
            'created_by'   => auth()->id(),
        ]);

        WarehouseTransaction::log([
            'warehouse_id'       => $warehouseId,
            'product_id'         => $item->product_id,
            'variant_id'         => $item->variant_id,
            'unit_conversion_id' => null, // الكمية مسجلة بوحدة الأساس
            'type'               => $type,
            'quantity'           => $qty,
            'unit_cost'          => $baseCost,
            'reference_type'     => Order::class,
            'reference_id'       => $order->id,
            'notes'              => $notes,
        ]);
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
    }
}
