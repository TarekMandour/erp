<?php

namespace App\Services\Finance;

use App\Models\Finance\InventoryItem;
use App\Models\Finance\InventoryTransaction;
use App\Models\Finance\WarehouseTransaction;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\Purchase;
use App\Models\Finance\PurchaseItem;
use App\Models\Finance\Supplier;
use App\Models\Finance\SupplierWallet;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Log;

class PurchaseService
{
    // =========================================================================
    //  Public API
    // =========================================================================

    /**
     * تطبيق آثار فاتورة الشراء (تُستدعى فقط للفواتير بحالة received):
     *  1. إضافة قيد دائن (الإجمالي) ومدين (المدفوع) في محفظة المورد
     *  2. تحديث متوسط التكلفة على مخزون كل المستودعات (قبل زيادة الكمية)
     *  3. زيادة المخزون بوحدة الأساس وتكلفة وحدة الأساس
     */
    public function apply(Purchase $purchase): void
    {
        $purchase->loadMissing('items.unitConversion');

        $this->addSupplierWalletEntry($purchase);

        foreach ($purchase->items as $item) {
            $baseQty  = $this->resolvedQuantity($item);
            $baseCost = $this->baseUnitCost($item);

            $this->applyCostIn($item, $baseQty, $baseCost);
            $this->increaseInventory($purchase->warehouse_id, $item, $baseQty, $baseCost);
        }

        Log::info('PurchaseService@apply: done', [
            'purchase_id' => $purchase->id,
            'items'       => $purchase->items->count(),
        ]);
    }

    /**
     * عكس آثار فاتورة الشراء (تُستدعى فقط إذا كانت آثارها مطبقة، أي الحالة السابقة received):
     *  1. حذف حركات محفظة المورد لهذه الفاتورة وإعادة الحساب
     *  2. عكس أثر الفاتورة على متوسط التكلفة (قبل إنقاص الكمية)
     *  3. تقليل المخزون
     */
    public function reverse(Purchase $purchase): void
    {
        $purchase->loadMissing('items.unitConversion');

        $this->removeSupplierWalletEntry($purchase);

        foreach ($purchase->items as $item) {
            $baseQty  = $this->resolvedQuantity($item);
            $baseCost = $this->baseUnitCost($item);

            $this->applyCostOut($item, $baseQty, $baseCost);
            $this->decreaseInventory($purchase->warehouse_id, $item, $baseQty, $baseCost);
            $this->refreshLastCost($item);
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
        
        $previousBalance = SupplierWallet::where('supplier_id', $purchase->supplier_id)
            ->latest('id')
            ->value('balance') ?? 0;

        $balance = $previousBalance + $purchase->total;

        SupplierWallet::create([
            'supplier_id' => $purchase->supplier_id,
            'purchase_id' => $purchase->id,
            'date'        => $purchase->date,
            'description' => 'فاتورة شراء رقم: ' . $purchase->purchase_number,
            'debit'       => 0,
            'credit'      => $purchase->total,
            'previous_balance' => $previousBalance,
            'balance'     => $balance,
        ]);
        
        if ($purchase->paid > 0) {
            SupplierWallet::create([
                'supplier_id' => $purchase->supplier_id,
                'purchase_id' => $purchase->id,
                'date'        => $purchase->date,
                'description' => 'عن فاتورة شراء رقم: ' . $purchase->purchase_number,
                'debit'       => $purchase->paid,
                'credit'      => 0,
                'previous_balance' => $balance,
                'balance'     => $balance - $purchase->paid,
            ]);
        }

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
        app(WalletBalanceService::class)->recalculateSupplier($supplierId);
    }

    // =========================================================================
    //  Inventory
    // =========================================================================

    private function increaseInventory(int $warehouseId, PurchaseItem $item, float $qty, float $unitCost): void
    {
        $inv = InventoryItem::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'product_id'   => $item->product_id,
                'variant_id'   => $item->variant_id,
            ],
            ['quantity' => 0]
        );

        $inv->increment('quantity', $qty);

        $this->logMovement($warehouseId, $item, 'in', $qty, $unitCost, 'فاتورة شراء #' . $item->purchase_id);
    }

    private function decreaseInventory(int $warehouseId, PurchaseItem $item, float $qty, float $unitCost): void
    {
        $inv = InventoryItem::where('warehouse_id', $warehouseId)
            ->where('product_id', $item->product_id)
            ->when(
                $item->variant_id,
                fn ($q) => $q->where('variant_id', $item->variant_id),
                fn ($q) => $q->whereNull('variant_id')
            )
            ->first();

        if ($inv) {
            $newQty = max(0, (float) $inv->quantity - $qty);
            $inv->update(['quantity' => $newQty]);
        }

        $this->logMovement($warehouseId, $item, 'out', $qty, $unitCost, 'إلغاء فاتورة شراء #' . $item->purchase_id);
    }

    /**
     * تسجيل الحركة في inventory_transactions و warehouse_transactions بوحدة الأساس
     */
    private function logMovement(int $warehouseId, PurchaseItem $item, string $type, float $qty, float $unitCost, string $notes): void
    {
        InventoryTransaction::create([
            'warehouse_id' => $warehouseId,
            'product_id'   => $item->product_id,
            'variant_id'   => $item->variant_id,
            'type'         => $type,
            'quantity'     => $qty,
            'unit_cost'    => round($unitCost, 4),
            'notes'        => $notes,
            'created_by'   => auth()->id(),
        ]);

        WarehouseTransaction::log([
            'warehouse_id'       => $warehouseId,
            'product_id'         => $item->product_id,
            'variant_id'         => $item->variant_id,
            'unit_conversion_id' => $item->unit_conversion_id,
            'type'               => $type,
            'quantity'           => $qty,
            'unit_cost'          => round($unitCost, 4),
            'reference_type'     => Purchase::class,
            'reference_id'       => $item->purchase_id,
            'notes'              => $notes,
        ]);
    }

    /**
     * الكمية بوحدة الأساس = الكمية × معامل التحويل (إن وُجد)
     */
    public function resolvedQuantity(PurchaseItem $item): float
    {
        $qty = (float) $item->quantity;

        if ($item->unit_conversion_id) {
            $item->loadMissing('unitConversion');
            $factor = (float) ($item->unitConversion?->conversion_rate ?? 1);
            $qty    = $qty * ($factor > 0 ? $factor : 1);
        }

        return $qty;
    }

    /**
     * تكلفة وحدة الأساس = صافي السطر (بعد الخصم وبدون الضريبة) ÷ الكمية بوحدة الأساس
     * بهذا تتطابق قيمة المخزون مع مدين حساب المخزون (subtotal) في القيد.
     */
    public function baseUnitCost(PurchaseItem $item): float
    {
        $baseQty = $this->resolvedQuantity($item);
        if ($baseQty <= 0) {
            return 0.0;
        }

        $gross = (float) $item->quantity * (float) $item->unit_cost - (float) $item->discount;
        $rate  = (float) $item->tax_rate;

        $net = Helper::pricingMode() === 'inclusive' && $rate > 0
            ? $gross * 100 / (100 + $rate)
            : $gross;

        return max(0.0, $net / $baseQty);
    }

    // =========================================================================
    //  Product Cost (Weighted Average Cost across all warehouses)
    // =========================================================================

    /**
     * الكمية الحالية للمنتج / المتغير في كل المستودعات
     */
    private function currentStock(int $productId, ?int $variantId): float
    {
        return (float) InventoryItem::where('product_id', $productId)
            ->when(
                $variantId,
                fn ($q) => $q->where('variant_id', $variantId),
                fn ($q) => $q->whereNull('variant_id')
            )
            ->sum('quantity');
    }

    private function costTarget(PurchaseItem $item): Product|ProductVariant|null
    {
        return $item->variant_id
            ? ProductVariant::find($item->variant_id)
            : Product::find($item->product_id);
    }

    /**
     * إضافة: avg_new = (Q×avg + q×c) / (Q + q) — Q مخزون كل المستودعات قبل الإضافة
     */
    private function applyCostIn(PurchaseItem $item, float $qty, float $cost): void
    {
        $target = $this->costTarget($item);
        if (! $target || $qty <= 0) {
            return;
        }

        $currentQty = max(0.0, $this->currentStock($item->product_id, $item->variant_id));
        $oldAvg     = (float) ($target->average_cost ?? 0);

        // متوسط غير محسوب (مثل رصيد افتتاحي بلا تكلفة): يُقيَّم الرصيد الحالي بالتكلفة الجديدة
        if ($oldAvg <= 0) {
            $oldAvg = $cost;
        }

        $totalQty = $currentQty + $qty;
        $newAvg   = $totalQty > 0 ? (($currentQty * $oldAvg) + ($qty * $cost)) / $totalQty : $cost;

        $target->update([
            'average_cost'   => round($newAvg, 4),
            'cost_price'     => round($cost, 4),
            'purchase_price' => round($cost, 4),
        ]);
    }

    /**
     * عكس: avg_new = (Q×avg − q×c) / (Q − q) — Q مخزون كل المستودعات قبل الإنقاص
     * إذا نفد المخزون أو كانت النتيجة غير منطقية يبقى المتوسط كما هو.
     */
    private function applyCostOut(PurchaseItem $item, float $qty, float $cost): void
    {
        $target = $this->costTarget($item);
        if (! $target || $qty <= 0) {
            return;
        }

        $currentQty = max(0.0, $this->currentStock($item->product_id, $item->variant_id));
        $avg        = (float) ($target->average_cost ?? 0);
        $remaining  = $currentQty - $qty;

        if ($remaining <= 0) {
            return;
        }

        $newAvg = (($currentQty * $avg) - ($qty * $cost)) / $remaining;

        if ($newAvg > 0) {
            $target->update(['average_cost' => round($newAvg, 4)]);
        }
    }

    /**
     * سعر آخر شراء = تكلفة وحدة الأساس لآخر بند من فاتورة مستلمة أخرى (غير الفاتورة المعكوسة)
     */
    private function refreshLastCost(PurchaseItem $item): void
    {
        $last = PurchaseItem::with('unitConversion')
            ->where('product_id', $item->product_id)
            ->when(
                $item->variant_id,
                fn ($q) => $q->where('variant_id', $item->variant_id),
                fn ($q) => $q->whereNull('variant_id')
            )
            ->where('purchase_id', '!=', $item->purchase_id)
            ->whereHas('purchase', fn ($q) => $q->where('status', 'received'))
            ->latest('id')
            ->first();

        $target = $this->costTarget($item);
        if (! $last || ! $target) {
            return;
        }

        $cost = round($this->baseUnitCost($last), 4);
        $target->update(['cost_price' => $cost, 'purchase_price' => $cost]);
    }
}

