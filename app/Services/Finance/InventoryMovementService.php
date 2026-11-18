<?php

namespace App\Services\Finance;

use App\Models\Finance\AccountTree;
use App\Models\Finance\InventoryItem;
use App\Models\Finance\InventoryTransaction;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\UnitConversion;
use App\Models\Finance\WarehouseTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * مسار واحد لكل حركة مخزون يدوية (شاشة المخزون + شاشة حركات المخزون):
 *  - المتغير إلزامي للمنتج ذي المتغيرات، والكمية تُحوَّل لوحدة الأساس
 *  - الصادر لا يتجاوز المتاح، و«التعديل» = تحديد الكمية النهائية (الفرق وارد أو صادر)
 *  - الوارد يحدّث متوسط التكلفة المرجّح، والصادر بمتوسط التكلفة
 *  - تُكتب الحركة في inventory_transactions و warehouse_transactions
 *  - قيد مرحّل: وارد = مدين 1103 / دائن المقابل، صادر = العكس
 */
class InventoryMovementService
{
    public const INVENTORY_ACCOUNT = '1103';

    public function __construct(private ManualJournalService $journal)
    {
    }

    /**
     * @param array $d warehouse_id, product_id, variant_id?, unit_conversion_id?, type (in|out|adjustment),
     *                 quantity, unit_cost?, counter_account_id, notes?
     */
    public function record(array $d): WarehouseTransaction
    {
        $counterId = (int) ($d['counter_account_id'] ?? 0);
        $this->assertCounterAccount($counterId);

        return DB::transaction(function () use ($d, $counterId) {
            $product   = Product::findOrFail($d['product_id']);
            $variantId = $this->resolveVariant($product, $d['variant_id'] ?? null);
            [$rate, $conversionId] = $this->resolveConversion($product, $variantId, $d['unit_conversion_id'] ?? null);

            $entered = (float) $d['quantity'];
            $baseQty = round($entered * $rate, 3);
            $item    = $this->lockItem((int) $d['warehouse_id'], $product->id, $variantId);
            $current = (float) $item->quantity;

            $type  = $d['type'];
            $notes = trim((string) ($d['notes'] ?? ''));

            if ($type === 'adjustment') {
                $delta = round($baseQty - $current, 3);
                if ($delta == 0.0) {
                    throw ValidationException::withMessages(['quantity' => 'الكمية الحالية تساوي الكمية المطلوبة (' . $this->fmt($current) . ')، لا يوجد فرق لتسجيله']);
                }
                $direction = $delta > 0 ? 'in' : 'out';
                $moveQty   = abs($delta);
                $notes     = trim('تعديل الكمية من ' . $this->fmt($current) . ' إلى ' . $this->fmt($baseQty) . ' — ' . $notes, ' —');
            } else {
                $direction = $type;
                $moveQty   = $baseQty;
            }

            if ($direction === 'out' && $moveQty > $current + 0.0005) {
                throw ValidationException::withMessages(['quantity' => 'الكمية الصادرة (' . $this->fmt($moveQty) . ') أكبر من المتاح في المستودع (' . $this->fmt($current) . ')']);
            }

            $target = $this->costTarget($product, $variantId);
            $avg    = (float) ($target->average_cost ?? 0);

            if ($direction === 'in') {
                $enteredCost = (float) ($d['unit_cost'] ?? 0);
                $cost        = $enteredCost > 0 ? $enteredCost / $rate : $avg;
                if ($cost <= 0) {
                    throw ValidationException::withMessages(['unit_cost' => 'أدخل تكلفة الوحدة: لا يوجد متوسط تكلفة لهذا المنتج بعد']);
                }
                $this->costIn($target, $product->id, $variantId, $moveQty, $cost);
                $item->quantity = $current + $moveQty;
            } else {
                $cost           = $avg;
                $item->quantity = $current - $moveQty;
            }
            $item->save();

            $wt = $this->writeRows((int) $d['warehouse_id'], $product, $variantId, $conversionId, $direction, $moveQty, $cost, $notes ?: null, null, null, (float) $item->quantity);

            $this->post($wt, $direction, $moveQty * $cost, $counterId, $notes);

            return $wt;
        });
    }

    /**
     * عكس حركة يدوية (بدل الحذف): حركة معاكسة بنفس الكمية والتكلفة + إلغاء القيد بقيد عكسي
     */
    public function reverse(WarehouseTransaction $wt): WarehouseTransaction
    {
        if ($wt->reference_type) {
            throw ValidationException::withMessages(['id' => 'هذه الحركة ناتجة عن مستند (' . class_basename($wt->reference_type) . ' #' . $wt->reference_id . ')، ألغِها من مستندها']);
        }
        if (WarehouseTransaction::where('reference_type', WarehouseTransaction::class)->where('reference_id', $wt->id)->exists()) {
            throw ValidationException::withMessages(['id' => 'هذه الحركة معكوسة بالفعل']);
        }

        return DB::transaction(function () use ($wt) {
            $product   = Product::findOrFail($wt->product_id);
            $variantId = $wt->variant_id;
            $isIn      = in_array($wt->type, ['in', 'transfer_in'], true);
            $direction = $isIn ? 'out' : 'in';
            $qty       = (float) $wt->quantity;
            $cost      = (float) $wt->unit_cost;
            $item      = $this->lockItem($wt->warehouse_id, $product->id, $variantId);
            $current   = (float) $item->quantity;
            $target    = $this->costTarget($product, $variantId);

            if ($direction === 'out') {
                if ($qty > $current + 0.0005) {
                    throw ValidationException::withMessages(['id' => 'لا يمكن عكس الحركة: الكمية المتاحة (' . $this->fmt($current) . ') أقل من كمية الحركة (' . $this->fmt($qty) . ')']);
                }
                $this->costOut($target, $product->id, $variantId, $qty, $cost);
                $item->quantity = $current - $qty;
            } else {
                if ($cost > 0) {
                    $this->costIn($target, $product->id, $variantId, $qty, $cost);
                }
                $item->quantity = $current + $qty;
            }
            $item->save();

            $reverse = $this->writeRows($wt->warehouse_id, $product, $variantId, $wt->unit_conversion_id, $direction, $qty, $cost,
                'عكس الحركة #' . $wt->id, WarehouseTransaction::class, $wt->id, (float) $item->quantity);

            $entry = JournalEntry::where('reference_type', WarehouseTransaction::class)->where('reference_id', $wt->id)
                ->where('status', 'posted')->where('entry_type', '!=', 'reversal')->first();
            if ($entry) {
                $this->journal->cancel($entry, 'عكس حركة المخزون #' . $wt->id);
            }

            return $reverse;
        });
    }

    // -------------------------------------------------------------------------

    public function resolveVariant(Product $product, $variantId): ?int
    {
        if (! $product->has_variants) {
            return null;
        }
        if (! $variantId) {
            throw ValidationException::withMessages(['variant_id' => 'هذا المنتج له متغيرات: اختر المتغير']);
        }
        if (! ProductVariant::where('id', $variantId)->where('product_id', $product->id)->exists()) {
            throw ValidationException::withMessages(['variant_id' => 'المتغير المختار لا يتبع هذا المنتج']);
        }

        return (int) $variantId;
    }

    /**
     * @return array{0: float, 1: ?int} [معامل التحويل، معرف وحدة التحويل]
     */
    public function resolveConversion(Product $product, ?int $variantId, $conversionId): array
    {
        if (! $conversionId) {
            return [1.0, null];
        }

        $uc = UnitConversion::find($conversionId);
        if (! $uc || ($uc->product_id && (int) $uc->product_id !== $product->id)
            || ($uc->variant_id && (int) $uc->variant_id !== (int) $variantId)) {
            throw ValidationException::withMessages(['unit_conversion_id' => 'وحدة التحويل لا تخص هذا المنتج']);
        }

        $rate = (float) $uc->conversion_rate;

        return [$rate > 0 ? $rate : 1.0, $uc->id];
    }

    public function costTarget(Product $product, ?int $variantId): Product|ProductVariant
    {
        return $variantId ? ProductVariant::findOrFail($variantId) : $product;
    }

    /**
     * avg = (Q×avg + q×c) / (Q + q) — Q مخزون كل المستودعات قبل الإضافة
     */
    public function costIn(Product|ProductVariant $target, int $productId, ?int $variantId, float $qty, float $cost): void
    {
        $q   = max(0.0, $this->totalStock($productId, $variantId));
        $avg = (float) ($target->average_cost ?? 0);
        if ($avg <= 0) {
            $avg = $cost;
        }
        $new = ($q + $qty) > 0 ? (($q * $avg) + ($qty * $cost)) / ($q + $qty) : $cost;
        $target->update(['average_cost' => round($new, 4)]);
    }

    /**
     * عكس وارد: avg = (Q×avg − q×c) / (Q − q)، ويبقى كما هو إن نفد المخزون أو خرجت نتيجة غير منطقية
     */
    public function costOut(Product|ProductVariant $target, int $productId, ?int $variantId, float $qty, float $cost): void
    {
        $q         = max(0.0, $this->totalStock($productId, $variantId));
        $remaining = $q - $qty;
        if ($remaining <= 0) {
            return;
        }
        $new = (($q * (float) $target->average_cost) - ($qty * $cost)) / $remaining;
        if ($new > 0) {
            $target->update(['average_cost' => round($new, 4)]);
        }
    }

    private function totalStock(int $productId, ?int $variantId): float
    {
        return (float) InventoryItem::where('product_id', $productId)
            ->when($variantId, fn ($q) => $q->where('variant_id', $variantId), fn ($q) => $q->whereNull('variant_id'))
            ->sum('quantity');
    }

    private function lockItem(int $warehouseId, int $productId, ?int $variantId): InventoryItem
    {
        $item = InventoryItem::where('warehouse_id', $warehouseId)->where('product_id', $productId)
            ->when($variantId, fn ($q) => $q->where('variant_id', $variantId), fn ($q) => $q->whereNull('variant_id'))
            ->lockForUpdate()->first();

        return $item ?? InventoryItem::create(['warehouse_id' => $warehouseId, 'product_id' => $productId, 'variant_id' => $variantId, 'quantity' => 0]);
    }

    private function writeRows(int $warehouseId, Product $product, ?int $variantId, ?int $conversionId, string $direction, float $qty, float $cost,
        ?string $notes, ?string $refType, ?int $refId, float $balanceAfter): WarehouseTransaction
    {
        $adminId = auth()->guard('admin')->id();

        InventoryTransaction::create([
            'warehouse_id' => $warehouseId,
            'product_id'   => $product->id,
            'variant_id'   => $variantId,
            'type'         => $direction,
            'quantity'     => $qty,
            'unit_cost'    => round($cost, 2),
            'notes'        => $notes,
            'created_by'   => $adminId,
        ]);

        return WarehouseTransaction::create([
            'warehouse_id'       => $warehouseId,
            'product_id'         => $product->id,
            'variant_id'         => $variantId,
            'unit_id'            => $product->unit_id,
            'unit_conversion_id' => $conversionId,
            'type'               => $direction,
            'quantity'           => $qty,
            'unit_cost'          => round($cost, 4),
            'balance_after'      => $balanceAfter,
            'reference_type'     => $refType,
            'reference_id'       => $refId,
            'notes'              => $notes,
            'created_by'         => $adminId,
        ]);
    }

    private function post(WarehouseTransaction $wt, string $direction, float $value, int $counterId, ?string $notes): void
    {
        $value = round($value, 2);
        if ($value <= 0) {
            return; // صادر بلا متوسط تكلفة: لا قيمة لترحيلها
        }

        $inventoryId = AccountTree::where('code', self::INVENTORY_ACCOUNT)->value('id');
        $isIn        = $direction === 'in';
        $description = ($isIn ? 'وارد' : 'صادر') . ' مخزون يدوي #' . $wt->id . ($notes ? ' — ' . $notes : '');

        $lines = [
            ['account_tree_id' => $inventoryId, 'debit' => $isIn ? $value : 0, 'credit' => $isIn ? 0 : $value, 'description' => $description],
            ['account_tree_id' => $counterId,   'debit' => $isIn ? 0 : $value, 'credit' => $isIn ? $value : 0, 'description' => $description],
        ];
        $this->journal->validateLines($lines, true, 'counter_account_id');

        $entry = JournalEntry::create([
            'entry_number'   => $this->journal->nextEntryNumber(),
            'entry_type'     => $isIn ? 'inventory_in' : 'inventory_out',
            'date'           => now()->toDateString(),
            'description'    => $description,
            'reference_type' => WarehouseTransaction::class,
            'reference_id'   => $wt->id,
            'created_by'     => auth()->guard('admin')->id(),
            'status'         => 'posted',
        ]);
        $this->journal->replaceItems($entry, $lines);
    }

    private function assertCounterAccount(int $counterId): void
    {
        $allowed = $this->journal->counterAccounts()->pluck('id')->all();
        $inventoryId = AccountTree::where('code', self::INVENTORY_ACCOUNT)->value('id');

        if (! $counterId || ! in_array($counterId, $allowed, true) || $counterId === (int) $inventoryId) {
            throw ValidationException::withMessages(['counter_account_id' => 'اختر الحساب المقابل (حساب فرعي نشط غير حساب المخزون)، مثل 5012 فروقات جرد المخزون أو 3001 رأس المال للرصيد الافتتاحي']);
        }
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
    }
}
