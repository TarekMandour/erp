<?php

namespace App\Services\Finance;

use App\Models\Finance\CustomerWallet;
use App\Models\Finance\InventoryItem;
use App\Models\Finance\InventoryTransaction;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\Order;
use App\Models\Finance\PostingScenario;
use App\Models\Finance\Product;
use App\Models\Finance\SalesReturn;
use App\Models\Finance\SalesReturnItem;
use App\Models\Finance\WarehouseTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * مرتجع المبيعات: مستند مستقل يُرحَّل متزامناً داخل معاملة واحدة (فشل أي جزء يلغي الكل):
 *
 *  create() → حفظ المرتجع وبنوده + إرجاع المخزون بتكلفته الأصلية + محفظة العميل
 *             (دائن بالإجمالي، ومدين بالمردود فعلياً) + قيد sales_return + قيد التكلفة
 *  cancel() → عكس قيوده + حذف صفوف محفظته + إخراج المخزون الذي أُرجع
 *
 * الكوبون والعرض يبقيان مستهلكين بعد المرتجع (قرار المالك). الطلب الأصلي لا يتغير.
 */
class SalesReturnService
{
    public function __construct(
        private SalesReturnCalculator $calculator,
        private TransactionsService $transactions,
        private InventoryMovementService $inventory,
        private OrderService $orders,
    ) {
    }

    /**
     * @param array $d  quantities (order_item_id => qty), date, paid?, include_shipping?, notes?
     *
     * @throws ValidationException
     */
    public function create(Order $order, array $d): SalesReturn
    {
        return DB::transaction(function () use ($order, $d) {
            // قفل الطلب: مرتجعان متزامنان لا يتجاوزان معاً الكمية المتاحة
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $calc   = $this->calculator->calculate($order, $d['quantities'] ?? [], (bool) ($d['include_shipping'] ?? false));
            $header = $calc['header'];
            $ctx    = $this->calculator->context($order);

            $paid = array_key_exists('paid', $d) && $d['paid'] !== null && $d['paid'] !== ''
                ? round((float) $d['paid'], 2)
                : $this->calculator->defaultRefund($order, $header['total'], $ctx['prior_paid']);

            if ($paid < 0 || $paid > $header['total'] + 0.001) {
                throw ValidationException::withMessages([
                    'paid' => 'المبلغ المردود (' . number_format($paid, 2) . ') يجب ألا يتجاوز إجمالي المرتجع (' . number_format($header['total'], 2) . ').',
                ]);
            }

            $bankTypes = ['wallet', 'bank_online', 'bank_direct'];

            $return = SalesReturn::create([
                'return_number'    => SalesReturn::generateNumber(),
                'order_id'         => $order->id,
                'customer_id'      => $order->customer_id,
                'warehouse_id'     => $order->warehouse_id,
                'date'             => $d['date'] ?? now()->toDateString(),
                'payment_type'     => $order->payment_type,
                'bank_account_id'  => in_array($order->payment_type, $bankTypes, true) ? $order->bank_account_id : null,
                'status'           => 'confirmed',
                'is_full'          => $calc['is_full'],
                'include_shipping' => $header['include_shipping'],
                'notes'            => ($d['notes'] ?? null) ?: null,
                'subtotal'         => $header['subtotal'],
                'discount'         => $header['discount'],
                'tax'              => $header['tax'],
                'shipping_cost'    => $header['shipping_cost'],
                'coupon_discount'  => $header['coupon_discount'],
                'offer_discount'   => $header['offer_discount'],
                'total'            => $header['total'],
                'paid'             => $paid,
                'created_by'       => auth('admin')->id(),
            ]);

            foreach ($calc['items'] as $line) {
                SalesReturnItem::create($line + ['sales_return_id' => $return->id]);
            }

            $return->load('items.unitConversion', 'order');

            $this->assertScenario($return);

            foreach ($return->items as $item) {
                $this->stockIn($return, $item);
            }

            $this->addWalletEntries($return);

            // القيد بعد تثبيت البنود (quantity_cost = الكمية × تكلفة وحدة البيع الأصلية)
            $this->transactions->process($return->fresh(['items']));

            return $return->fresh(['items', 'order']);
        });
    }

    /**
     * @throws ValidationException
     */
    public function cancel(SalesReturn $return, ?string $notes = null): SalesReturn
    {
        return DB::transaction(function () use ($return, $notes) {
            $return = SalesReturn::with('items.unitConversion')->whereKey($return->id)->lockForUpdate()->firstOrFail();

            if ($return->status === 'cancelled') {
                throw ValidationException::withMessages(['id' => 'هذا المرتجع ملغي بالفعل.']);
            }

            // المخزون أولاً: فشله (نقص الكمية) يلغي كل شيء قبل أي عكس
            foreach ($return->items as $item) {
                $this->stockOut($return, $item);
            }

            $ids = JournalEntry::where('reference_type', SalesReturn::class)
                ->where('reference_id', $return->id)
                ->where('is_reversed', false)
                ->where('status', 'posted')
                ->orderBy('id')
                ->pluck('id');
            foreach ($ids as $id) {
                $this->transactions->reverseEntry(JournalEntry::with('items')->find($id), $return);
            }

            $this->removeWalletEntries($return);

            $return->update([
                'status' => 'cancelled',
                'notes'  => $notes ?: $return->notes,
            ]);

            return $return;
        });
    }

    // =========================================================================
    //  المحفظة: مدين = على العميل، دائن = للعميل (يطابق addCustomerWalletEntry للبيع)
    // =========================================================================

    private function addWalletEntries(SalesReturn $return): void
    {
        $base = [
            'customer_id'     => $return->customer_id,
            'order_id'        => null, // لا يُربط بالطلب: عكس الطلب يحذف صفوفه بهذا المعرف
            'sales_return_id' => $return->id,
            'date'            => $return->date,
            'balance'         => 0,
        ];
        $label = 'مرتجع مبيعات رقم: ' . $return->return_number . ' (طلب ' . $return->order?->order_number . ')';

        if ((float) $return->total > 0) {
            CustomerWallet::create($base + ['description' => $label, 'debit' => 0, 'credit' => $return->total]);
        }
        if ((float) $return->paid > 0) {
            CustomerWallet::create($base + ['description' => 'المردود للعميل عن مرتجع رقم: ' . $return->return_number, 'debit' => $return->paid, 'credit' => 0]);
        }

        $this->orders->recalculateCustomerBalance($return->customer_id);
    }

    private function removeWalletEntries(SalesReturn $return): void
    {
        CustomerWallet::where('sales_return_id', $return->id)->delete();
        $this->orders->recalculateCustomerBalance($return->customer_id);
    }

    // =========================================================================
    //  المخزون
    // =========================================================================

    /** إرجاع الكمية لمستودع الطلب بتكلفتها الأصلية (لكل وحدة أساس) */
    private function stockIn(SalesReturn $return, SalesReturnItem $item): void
    {
        $product  = Product::findOrFail($item->product_id);
        $rate     = (float) ($item->unitConversion?->conversion_rate ?: 1);
        $qty      = round((float) $item->quantity * $rate, 6);
        $baseCost = round((float) $item->unit_cost / $rate, 4);

        $inv = $this->lockItem($return->warehouse_id, $item->product_id, $item->variant_id);

        if ($baseCost > 0) {
            $this->inventory->costIn($this->inventory->costTarget($product, $item->variant_id), $item->product_id, $item->variant_id, $qty, $baseCost);
        }
        $inv->update(['quantity' => round((float) $inv->quantity + $qty, 3)]);

        $this->logMovement($return, $item, 'in', $qty, $baseCost, 'مرتجع مبيعات ' . $return->return_number);
    }

    /** إخراج ما أُرجع عند إلغاء المرتجع */
    private function stockOut(SalesReturn $return, SalesReturnItem $item): void
    {
        $product  = Product::findOrFail($item->product_id);
        $rate     = (float) ($item->unitConversion?->conversion_rate ?: 1);
        $qty      = round((float) $item->quantity * $rate, 6);
        $baseCost = round((float) $item->unit_cost / $rate, 4);

        $inv       = $this->lockItem($return->warehouse_id, $item->product_id, $item->variant_id);
        $available = (float) $inv->quantity;

        if ($qty > $available + 0.000001) {
            throw ValidationException::withMessages([
                'id' => "لا يمكن إلغاء المرتجع: المنتج «{$product->name}» — الكمية الحالية في المستودع ({$this->fmt($available)}) أقل من الكمية المرتجعة ({$this->fmt($qty)}).",
            ]);
        }

        if ($baseCost > 0) {
            $this->inventory->costOut($this->inventory->costTarget($product, $item->variant_id), $item->product_id, $item->variant_id, $qty, $baseCost);
        }
        $inv->update(['quantity' => round($available - $qty, 3)]);

        $this->logMovement($return, $item, 'out', $qty, $baseCost, 'إلغاء مرتجع مبيعات ' . $return->return_number);
    }

    private function lockItem(int $warehouseId, int $productId, ?int $variantId): InventoryItem
    {
        $inv = InventoryItem::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->when($variantId, fn ($q) => $q->where('variant_id', $variantId), fn ($q) => $q->whereNull('variant_id'))
            ->lockForUpdate()
            ->first();

        return $inv ?? InventoryItem::create(['warehouse_id' => $warehouseId, 'product_id' => $productId, 'variant_id' => $variantId, 'quantity' => 0]);
    }

    private function logMovement(SalesReturn $return, SalesReturnItem $item, string $type, float $qty, float $baseCost, string $notes): void
    {
        InventoryTransaction::create([
            'warehouse_id' => $return->warehouse_id,
            'product_id'   => $item->product_id,
            'variant_id'   => $item->variant_id,
            'type'         => $type,
            'quantity'     => $qty,
            'unit_cost'    => $baseCost,
            'notes'        => $notes,
            'created_by'   => auth('admin')->id(),
        ]);

        WarehouseTransaction::log([
            'warehouse_id'       => $return->warehouse_id,
            'product_id'         => $item->product_id,
            'variant_id'         => $item->variant_id,
            'unit_conversion_id' => null, // الكمية مسجلة بوحدة الأساس
            'type'               => $type,
            'quantity'           => $qty,
            'unit_cost'          => $baseCost,
            'reference_type'     => SalesReturn::class,
            'reference_id'       => $return->id,
            'notes'              => $notes,
            'created_by'         => auth('admin')->id(),
        ]);
    }

    // =========================================================================

    /** بدون سيناريو فعّال يُنشأ قيد احتياطي مسودة: نرفض بدلاً من ترك مرتجع بلا ترحيل */
    private function assertScenario(SalesReturn $return): void
    {
        $code = $return->getScenarioCode();

        if (! PostingScenario::where('code', $code)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'order_id' => "سيناريو الترحيل ({$code}) غير موجود أو غير مفعّل. فعّله من شاشة سيناريوهات الترحيل ثم أعد المحاولة.",
            ]);
        }
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
    }
}
