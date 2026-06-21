<?php

namespace App\Services\Finance;

use App\Models\Finance\InventoryItem;
use App\Models\Finance\InventoryTransaction;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\Purchase;
use App\Models\Finance\PurchaseItem;
use App\Models\Finance\Supplier;
use App\Models\Finance\SupplierWallet;
use Illuminate\Support\Facades\Log;

class PurchaseService
{
    // =========================================================================
    //  Public API
    // =========================================================================

    /**
     * تطبيق آثار فاتورة الشراء:
     *  1. إضافة قيد دائن في محفظة المورد
     *  2. زيادة المخزون لكل صنف
     *  3. تحديث متوسط التكلفة وسعر الشراء للمنتج / المتغير
     */
    public function apply(Purchase $purchase): void
    {
        $purchase->loadMissing('items');

        $this->addSupplierWalletEntry($purchase);

        foreach ($purchase->items as $item) {
            $this->increaseInventory($purchase->warehouse_id, $item);
            $this->updateProductCost($purchase->warehouse_id, $item);
        }

        Log::info('PurchaseService@apply: done', [
            'purchase_id' => $purchase->id,
            'items'       => $purchase->items->count(),
        ]);
    }

    /**
     * عكس آثار فاتورة الشراء (عند الحذف أو إعادة التطبيق):
     *  1. حذف قيد محفظة المورد وإعادة الحساب
     *  2. تقليل المخزون
     *  3. إعادة حساب متوسط التكلفة من بقية المشتريات
     */
    public function reverse(Purchase $purchase): void
    {
        $purchase->loadMissing('items');

        $this->removeSupplierWalletEntry($purchase);

        foreach ($purchase->items as $item) {
            $this->decreaseInventory($purchase->warehouse_id, $item);
            $this->recalculateProductCost($item->product_id, $item->variant_id);
        }

        Log::info('PurchaseService@reverse: done', [
            'purchase_id' => $purchase->id,
        ]);
    }

    // =========================================================================
    //  Supplier Wallet
    // =========================================================================

    private function addSupplierWalletEntry(Purchase $purchase): void
    {
        if (! $purchase->supplier_id) {
            return;
        }

        // تأكد أنه لا يوجد قيد مسبق لهذه الفاتورة
        SupplierWallet::where('purchase_id', $purchase->id)->delete();

        SupplierWallet::create([
            'supplier_id' => $purchase->supplier_id,
            'purchase_id' => $purchase->id,
            'date'        => $purchase->date,
            'description' => 'فاتورة شراء رقم: ' . $purchase->purchase_number,
            'debit'       => 0,
            'credit'      => $purchase->total,   // مبلغ مستحق للمورد
            'previous_balance' => 0,
            'balance'     => 0,
        ]);

        $this->recalculateSupplierBalance($purchase->supplier_id);
    }

    private function removeSupplierWalletEntry(Purchase $purchase): void
    {
        if (! $purchase->supplier_id) {
            return;
        }

        SupplierWallet::where('purchase_id', $purchase->id)->delete();
        $this->recalculateSupplierBalance($purchase->supplier_id);
    }

    private function recalculateSupplierBalance(int $supplierId): void
    {
        $prev = 0;

        foreach (SupplierWallet::where('supplier_id', $supplierId)->orderBy('id')->get() as $entry) {
            $balance = $prev + $entry->credit - $entry->debit;
            $entry->previous_balance = $prev;
            $entry->balance          = $balance;
            $entry->save();
            $prev = $balance;
        }

        Supplier::where('id', $supplierId)->update(['wallet_balance' => $prev]);
    }

    // =========================================================================
    //  Inventory
    // =========================================================================

    private function increaseInventory(int $warehouseId, PurchaseItem $item): void
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
            'unit_cost'    => $item->unit_cost,
            'notes'        => 'فاتورة شراء #' . $item->purchase_id,
            'created_by'   => auth()->id(),
        ]);
    }

    private function decreaseInventory(int $warehouseId, PurchaseItem $item): void
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
            'unit_cost'    => $item->unit_cost,
            'notes'        => 'إلغاء فاتورة شراء #' . $item->purchase_id,
            'created_by'   => auth()->id(),
        ]);
    }

    /**
     * حساب الكمية الفعلية بعد تطبيق معامل الوحدة إن وُجد
     */
    private function resolvedQuantity(PurchaseItem $item): float
    {
        $qty = (float) $item->quantity;

        if ($item->unit_conversion_id) {
            $item->loadMissing('unitConversion');
            $factor = (float) ($item->unitConversion?->factor ?? 1);
            $qty    = $qty * $factor;
        }

        return $qty;
    }

    // =========================================================================
    //  Product Cost (Average Cost & Cost Price)
    // =========================================================================

    /**
     * تحديث متوسط التكلفة وسعر الشراء عند إضافة مشتريات جديدة.
     * المعادلة: avg_new = (qty_old * avg_old + qty_new * cost_new) / (qty_old + qty_new)
     */
    private function updateProductCost(int $warehouseId, PurchaseItem $item): void
    {
        $newQty  = $this->resolvedQuantity($item);
        $newCost = (float) $item->unit_cost;

        if ($item->variant_id) {
            $variant = ProductVariant::find($item->variant_id);
            if (! $variant) {
                return;
            }

            // الكمية الحالية قبل الزيادة كانت: الكمية الإجمالية - الكمية المضافة للتو
            $currentQty = max(0, (float) InventoryItem::where([
                'warehouse_id' => $warehouseId,
                'product_id'   => $item->product_id,
                'variant_id'   => $item->variant_id,
            ])->value('quantity') - $newQty);

            $oldAvg  = (float) ($variant->average_cost ?? $newCost);
            $totalQty = $currentQty + $newQty;
            $newAvg  = $totalQty > 0
                ? (($currentQty * $oldAvg) + ($newQty * $newCost)) / $totalQty
                : $newCost;

            $variant->update([
                'average_cost'  => round($newAvg, 4),
                'cost_price'    => $newCost,
                'purchase_price' => $newCost,
            ]);

        } else {
            $product = Product::find($item->product_id);
            if (! $product) {
                return;
            }

            $currentQty = max(0, (float) InventoryItem::where([
                'warehouse_id' => $warehouseId,
                'product_id'   => $item->product_id,
                'variant_id'   => null,
            ])->value('quantity') - $newQty);

            $oldAvg   = (float) ($product->average_cost ?? $newCost);
            $totalQty = $currentQty + $newQty;
            $newAvg   = $totalQty > 0
                ? (($currentQty * $oldAvg) + ($newQty * $newCost)) / $totalQty
                : $newCost;

            $product->update([
                'average_cost'  => round($newAvg, 4),
                'cost_price'    => $newCost,
                'purchase_price' => $newCost,
            ]);
        }
    }

    /**
     * إعادة حساب متوسط التكلفة من الصفر بناءً على جميع بنود المشتريات المتبقية
     */
    private function recalculateProductCost(int $productId, ?int $variantId): void
    {
        $items = PurchaseItem::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->get();

        if ($items->isEmpty()) {
            // لا توجد مشتريات — أعد التكلفة إلى صفر
            if ($variantId) {
                ProductVariant::where('id', $variantId)->update([
                    'average_cost'   => 0,
                    'cost_price'     => 0,
                    'purchase_price' => 0,
                ]);
            } else {
                Product::where('id', $productId)->update([
                    'average_cost'   => 0,
                    'cost_price'     => 0,
                    'purchase_price' => 0,
                ]);
            }
            return;
        }

        // متوسط مرجح من جميع بنود الشراء
        $totalQty  = $items->sum(fn($i) => (float) $i->quantity);
        $totalCost = $items->sum(fn($i) => (float) $i->quantity * (float) $i->unit_cost);
        $avgCost   = $totalQty > 0 ? round($totalCost / $totalQty, 4) : 0;
        $lastCost  = (float) $items->last()->unit_cost;

        if ($variantId) {
            ProductVariant::where('id', $variantId)->update([
                'average_cost'   => $avgCost,
                'cost_price'     => $lastCost,
                'purchase_price' => $lastCost,
            ]);
        } else {
            Product::where('id', $productId)->update([
                'average_cost'   => $avgCost,
                'cost_price'     => $lastCost,
                'purchase_price' => $lastCost,
            ]);
        }
    }
}
