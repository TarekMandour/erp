<?php

namespace App\Services\Finance;

use App\Helpers\Helper;
use App\Models\Finance\Order;
use App\Models\Finance\SalesReturn;
use App\Models\Finance\SalesReturnItem;
use Illuminate\Validation\ValidationException;

/**
 * حساب مرتجع المبيعات من أرقام الطلب الأصلي (لا إعادة تسعير):
 *
 *  - قيمة البند المرتجع = صافيه بعد حصته من خصم الفاتورة (كوبون/عرض) مع ضريبته،
 *    بنفس توزيع OrderPricingService::allocate() المستخدم عند البيع
 *  - آخر كمية متبقية من البند تأخذ باقي قيمته (فروق التقريب لا تتراكم)
 *  - طلب فيه عرض: مرتجع واحد كامل فقط، وأرقامه من أرقام الطلب
 *  - الشحن يُرجَع مع المرتجع الذي يكمل الطلب، وفي الجزئي بخيار المستخدم
 *  - وضع التسعير (شامل/غير شامل) يُستنتج من بنود الطلب نفسها لا من الإعداد الحالي
 */
class SalesReturnCalculator
{
    private const EPS = 0.0005;

    /**
     * حالة كل بند في الطلب: المباع/المرتجع سابقاً/المتاح + قيمه الكاملة
     *
     * @return array{inclusive: bool, rows: array<int, array>, prior_shipping: float, prior_paid: float, has_prior: bool}
     */
    public function context(Order $order): array
    {
        $order->loadMissing('items.product:id,name,sku', 'items.variant:id,product_id,sku', 'items.unitConversion', 'offer');

        $invoiceDiscount = $this->invoiceDiscount($order);
        $nets  = $order->items->map(fn ($i) => round((float) $i->quantity * (float) $i->unit_price - (float) $i->discount, 2))->all();
        $alloc = OrderPricingService::allocate($nets, $invoiceDiscount);

        $inclusive = $this->detectInclusive($order, $nets, $alloc);

        $prior = SalesReturnItem::query()
            ->join('sales_returns', 'sales_returns.id', '=', 'sales_return_items.sales_return_id')
            ->where('sales_returns.order_id', $order->id)
            ->where('sales_returns.status', 'confirmed')
            ->selectRaw('sales_return_items.order_item_id, SUM(sales_return_items.quantity) q, SUM(sales_return_items.gross) g, SUM(sales_return_items.discount) d, SUM(sales_return_items.invoice_discount) inv, SUM(sales_return_items.tax) t, SUM(sales_return_items.total) tot')
            ->groupBy('sales_return_items.order_item_id')
            ->get()->keyBy('order_item_id');

        $confirmed = SalesReturn::where('order_id', $order->id)->where('status', 'confirmed');

        $rows = [];
        foreach ($order->items as $idx => $item) {
            $qty      = (float) $item->quantity;
            $price    = (float) $item->unit_price;
            $rate     = (float) $item->tax_rate;
            $gross    = round($qty * $price, 2);
            $disc     = round((float) $item->discount, 2);
            $inv      = (float) $alloc[$idx];
            $taxable  = round($gross - $disc - $inv, 2);
            $tax      = OrderPricingService::lineTax($taxable, $rate, $inclusive);
            $total    = $inclusive ? $taxable : round($taxable + $tax, 2);
            $p        = $prior->get($item->id);

            $rows[$item->id] = [
                'item'      => $item,
                'sold'      => $qty,
                'returned'  => (float) ($p->q ?? 0),
                'remaining' => round($qty - (float) ($p->q ?? 0), 3),
                'full'      => ['gross' => $gross, 'discount' => $disc, 'invoice_discount' => $inv, 'tax' => $tax, 'total' => $total],
                'prior'     => ['gross' => (float) ($p->g ?? 0), 'discount' => (float) ($p->d ?? 0), 'invoice_discount' => (float) ($p->inv ?? 0), 'tax' => (float) ($p->t ?? 0), 'total' => (float) ($p->tot ?? 0)],
            ];
        }

        return [
            'inclusive'      => $inclusive,
            'rows'           => $rows,
            'prior_shipping' => round((float) (clone $confirmed)->sum('shipping_cost'), 2),
            'prior_paid'     => round((float) (clone $confirmed)->sum('paid'), 2),
            'has_prior'      => (clone $confirmed)->exists(),
        ];
    }

    /**
     * @param  array<int|string, float|string>  $quantities  order_item_id => الكمية المرتجعة
     * @return array{items: array<int, array>, header: array<string, mixed>, is_full: bool, inclusive: bool}
     *
     * @throws ValidationException
     */
    public function calculate(Order $order, array $quantities, bool $includeShipping = false): array
    {
        if (! $order->is_effective) {
            throw ValidationException::withMessages(['order_id' => 'لا يمكن إنشاء مرتجع لطلب بهذه الحالة (' . $order->status_label . ').']);
        }

        $ctx       = $this->context($order);
        $inclusive = $ctx['inclusive'];
        $hasOffer  = $order->offer_id || (float) $order->offer_discount > 0;

        $items = [];
        foreach ($quantities as $orderItemId => $qty) {
            $qty = round((float) $qty, 3);
            if ($qty <= 0) {
                continue;
            }

            $row = $ctx['rows'][(int) $orderItemId] ?? null;
            if (! $row) {
                throw ValidationException::withMessages(['items' => 'بند غير تابع لهذا الطلب.']);
            }

            $name = $row['item']->product?->name ?? ('#' . $row['item']->product_id);
            if ($qty > $row['remaining'] + self::EPS) {
                throw ValidationException::withMessages([
                    'items' => "المنتج «{$name}»: الكمية المرتجعة ({$this->fmt($qty)}) أكبر من المتاح للإرجاع ({$this->fmt($row['remaining'])}).",
                ]);
            }

            $items[$orderItemId] = $this->line($row, $qty, $inclusive);
        }

        if (! $items) {
            throw ValidationException::withMessages(['items' => 'حدّد كمية مرتجعة لبند واحد على الأقل.']);
        }

        // هل يكمل هذا المرتجع كل كميات الطلب؟
        $isFull = true;
        foreach ($ctx['rows'] as $id => $row) {
            $after = $row['remaining'] - (float) ($items[$id]['quantity'] ?? 0);
            if ($after > self::EPS) {
                $isFull = false;
                break;
            }
        }

        // طلب فيه عرض: مرتجع واحد كامل
        if ($hasOffer && ($ctx['has_prior'] || ! $isFull)) {
            throw ValidationException::withMessages([
                'items' => 'هذا الطلب استُخدم فيه عرض: لا يمكن إرجاعه جزئياً، يجب إرجاع كل بنوده بكامل كمياتها في مرتجع واحد.',
            ]);
        }

        $itemsNet = round(array_sum(array_map(fn ($l) => $l['gross'] - $l['discount'], $items)), 2);
        $tax      = round(array_sum(array_column($items, 'tax')), 2);
        $discount = round(array_sum(array_column($items, 'discount')), 2);

        $shippingLeft = max(0.0, round((float) $order->shipping_cost - $ctx['prior_shipping'], 2));
        $shipping     = ($isFull || $includeShipping) ? $shippingLeft : 0.0;

        if ($hasOffer) {
            $coupon = round((float) $order->coupon_discount, 2);
            $offer  = round((float) $order->offer_discount, 2);
        } else {
            $coupon = round(array_sum(array_column($items, 'invoice_discount')), 2);
            $offer  = 0.0;
        }

        $subtotal = $inclusive ? round($itemsNet - $tax, 2) : $itemsNet;
        $total    = $inclusive
            ? round($itemsNet + $shipping - $coupon - $offer, 2)
            : round($itemsNet + $tax + $shipping - $coupon - $offer, 2);

        return [
            'items'     => array_values($items),
            'is_full'   => $isFull,
            'inclusive' => $inclusive,
            'header'    => [
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'tax'              => $tax,
                'shipping_cost'    => $shipping,
                'include_shipping' => $shipping > 0,
                'coupon_discount'  => $coupon,
                'offer_discount'   => $offer,
                'total'            => max(0.0, $total),
            ],
        ];
    }

    /**
     * المبلغ الافتراضي المردود فعلياً: الدفع الفوري (نقدي/محفظة/بنك) حتى حد ما دُفع فعلاً،
     * والآجل/الأقساط/الشيك صفر (يبقى رصيداً للعميل في محفظته)
     */
    public function defaultRefund(Order $order, float $total, float $priorPaid): float
    {
        if (! in_array($order->payment_type, ['cash', 'wallet', 'bank_online', 'bank_direct'], true)) {
            return 0.0;
        }

        return round(max(0.0, min($total, (float) $order->paid - $priorPaid)), 2);
    }

    // -------------------------------------------------------------------------

    private function line(array $row, float $qty, bool $inclusive): array
    {
        $item = $row['item'];
        $full = $row['full'];
        $prior = $row['prior'];
        $rate = (float) $item->tax_rate;

        if (abs($qty - $row['remaining']) <= self::EPS) {
            // آخر ما تبقى من البند: يأخذ باقي قيمته
            $gross = round($full['gross'] - $prior['gross'], 2);
            $disc  = round($full['discount'] - $prior['discount'], 2);
            $inv   = round($full['invoice_discount'] - $prior['invoice_discount'], 2);
            $tax   = round($full['tax'] - $prior['tax'], 2);
            $total = round($full['total'] - $prior['total'], 2);
        } else {
            $share = $qty / (float) $row['sold'];
            $gross = round($qty * (float) $item->unit_price, 2);
            $disc  = min($gross, round($full['discount'] * $share, 2));
            $inv   = min(round($gross - $disc, 2), round($full['invoice_discount'] * $share, 2));
            $taxable = round($gross - $disc - $inv, 2);
            $tax   = OrderPricingService::lineTax($taxable, $rate, $inclusive);
            $total = $inclusive ? $taxable : round($taxable + $tax, 2);
        }

        return [
            'order_item_id'      => $item->id,
            'product_id'         => $item->product_id,
            'variant_id'         => $item->variant_id,
            'unit_conversion_id' => $item->unit_conversion_id,
            'quantity'           => $qty,
            'unit_price'         => (float) $item->unit_price,
            'unit_cost'          => (float) $item->unit_cost,
            'gross'              => $gross,
            'discount'           => $disc,
            'invoice_discount'   => $inv,
            'tax_rate'           => $rate,
            'tax'                => $tax,
            'total'              => $total,
        ];
    }

    /** خصم الفاتورة الموزَّع على البنود = كوبون + عرض − (خصم الشحن المجاني: يخص الشحن) */
    private function invoiceDiscount(Order $order): float
    {
        $shipOffer = $order->offer?->type === 'free_shipping' ? (float) $order->offer_discount : 0.0;

        return round((float) $order->coupon_discount + (float) $order->offer_discount - $shipOffer, 2);
    }

    /** شامل الضريبة؟ تُستنتج من أول بند ذي ضريبة: إجماليه = صافيه (شامل) أم صافيه + ضريبة (غير شامل) */
    private function detectInclusive(Order $order, array $nets, array $alloc): bool
    {
        foreach ($order->items as $idx => $item) {
            $rate = (float) $item->tax_rate;
            if ($rate <= 0) {
                continue;
            }
            $taxable = round($nets[$idx] - $alloc[$idx], 2);
            if ($taxable <= 0) {
                continue;
            }

            return abs((float) $item->total - $taxable) < abs((float) $item->total - round($taxable + OrderPricingService::lineTax($taxable, $rate, false), 2));
        }

        return Helper::pricingMode() === 'inclusive';
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
    }
}
