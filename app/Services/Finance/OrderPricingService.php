<?php

namespace App\Services\Finance;

use App\Helpers\Helper;
use App\Models\Finance\Coupon;
use App\Models\Finance\CouponUsage;
use App\Models\Finance\Offer;
use App\Models\Finance\Order;
use App\Models\Finance\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * QA الدفعة 6 (المبيعات): تسعير الطلب على الخادم.
 *
 * لا يُعتمد على أرقام المتصفح (خصم الكوبون/العرض، تكلفة الوحدة): تُحسب هنا
 * من بنود الطلب وتُتحقق صلاحية الكوبون والعرض بتاريخ الفاتورة.
 *
 *  - صافي البند           = الكمية × السعر − خصم البند (لا يتجاوز قيمة البند)
 *  - أساس الكوبون/العرض   = مجموع صافي البنود (نفس ما تعرضه الشاشة)
 *  - الإجمالي             = حسب وضع التسعير (شامل/غير شامل الضريبة) + الشحن − الكوبون − العرض ≥ 0
 */
class OrderPricingService
{
    public function __construct(private InventoryMovementService $inventory)
    {
    }

    /**
     * @param  array  $header  customer_id, date, shipping_cost, coupon_id, offer_id
     * @param  array  $items   بنود الطلب كما في الطلب
     * @return array{items: array<int, array>, header: array<string, mixed>}
     *
     * @throws ValidationException
     */
    public function price(array $header, array $items, ?Order $existing = null): array
    {
        $lines = $this->normalizeItems($items);

        $itemsNet  = round(array_sum(array_column($lines, 'net')), 2);
        $shipping  = round(max(0, (float) ($header['shipping_cost'] ?? 0)), 2);
        $date      = (string) ($header['date'] ?? now()->toDateString());
        $customer  = ! empty($header['customer_id']) ? (int) $header['customer_id'] : null;
        $excludeId = $existing?->id;

        // ── الكوبون ──────────────────────────────────────────────
        $couponId = ! empty($header['coupon_id']) ? (int) $header['coupon_id'] : null;
        $couponDiscount = 0.0;
        if ($couponId) {
            $coupon = Coupon::find($couponId);
            $error  = $coupon ? $this->couponError($coupon, $itemsNet, $customer, $date, $excludeId) : 'الكوبون غير موجود.';
            if ($error) {
                throw ValidationException::withMessages(['coupon_id' => $error]);
            }
            $couponDiscount = $this->couponDiscount($coupon, $itemsNet);
        }

        // ── العرض ────────────────────────────────────────────────
        $offerId = ! empty($header['offer_id']) ? (int) $header['offer_id'] : null;
        $offerDiscount = 0.0;
        if ($offerId) {
            $offer = Offer::find($offerId);
            $error = $offer ? $this->offerError($offer, $date, $excludeId) : 'العرض غير موجود.';
            if (! $error && $couponId && ! $offer->is_stackable) {
                $error = 'العرض «' . $offer->name . '» غير قابل للجمع مع كوبون خصم.';
            }
            if ($error) {
                throw ValidationException::withMessages(['offer_id' => $error]);
            }
            $result = $this->offerDiscount($offer, $lines, $shipping, $customer, $excludeId);
            if (! $result['valid']) {
                throw ValidationException::withMessages(['offer_id' => $result['message']]);
            }
            // لا يتجاوز مجموع الخصومات قيمة الطلب (الشحن يدخل فقط في عرض الشحن المجاني)
            $cap = max(0, $itemsNet - $couponDiscount) + ($offer->type === 'free_shipping' ? $shipping : 0);
            $offerDiscount = round(min($result['discount'], $cap), 2);
        }

        // ── الإجماليات ───────────────────────────────────────────
        $discount = round(array_sum(array_column($lines, 'discount')), 2);
        $tax      = round(array_sum(array_column($lines, 'tax')), 2);

        if (Helper::pricingMode() === 'inclusive') {
            $subtotal = round($itemsNet - $tax, 2);
            $total    = round($itemsNet + $shipping - $couponDiscount - $offerDiscount, 2);
        } else {
            $subtotal = $itemsNet;
            $total    = round($itemsNet + $tax + $shipping - $couponDiscount - $offerDiscount, 2);
        }

        return [
            'items'  => $lines,
            'header' => [
                'subtotal'        => $subtotal,
                'discount'        => $discount,
                'tax'             => $tax,
                'shipping_cost'   => $shipping,
                'coupon_id'       => $couponId,
                'coupon_discount' => round($couponDiscount, 2),
                'offer_id'        => $offerId,
                'offer_discount'  => $offerDiscount,
                'total'           => max(0, $total),
            ],
        ];
    }

    // =========================================================================
    //  البنود
    // =========================================================================

    /**
     * @throws ValidationException
     */
    public function normalizeItems(array $items): array
    {
        $inclusive = Helper::pricingMode() === 'inclusive';
        $lines     = [];

        foreach (array_values($items) as $i => $item) {
            $product = Product::find((int) ($item['product_id'] ?? 0));
            if (! $product) {
                throw ValidationException::withMessages(["items.$i.product_id" => 'المنتج غير موجود.']);
            }

            try {
                $variantId = $this->inventory->resolveVariant($product, $item['variant_id'] ?? null);
                [$rate, $conversionId] = $this->inventory->resolveConversion($product, $variantId, $item['unit_conversion_id'] ?? null);
            } catch (ValidationException $e) {
                throw ValidationException::withMessages([
                    "items.$i" => 'البند ' . ($i + 1) . ' (' . $product->name . '): ' . collect($e->errors())->flatten()->first(),
                ]);
            }

            $qty   = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $gross = round($qty * $price, 2);
            $disc  = round((float) ($item['discount'] ?? 0), 2);
            if ($disc > $gross) {
                throw ValidationException::withMessages([
                    "items.$i.discount" => 'خصم البند ' . ($i + 1) . ' (' . $product->name . ') أكبر من قيمته (' . number_format($gross, 2) . ').',
                ]);
            }

            $rateTax = (float) ($item['tax_rate'] ?? 0);
            $net     = round($gross - $disc, 2);
            $tax     = $inclusive
                ? round($net * $rateTax / (100 + $rateTax), 2)
                : round($net * $rateTax / 100, 2);

            $lines[] = [
                'product_id'         => $product->id,
                'category_id'        => $product->category_id,
                'variant_id'         => $variantId,
                'unit_conversion_id' => $conversionId,
                'rate'               => $rate,
                'quantity'           => $qty,
                'unit_price'         => $price,
                'discount'           => $disc,
                'tax_rate'           => $rateTax,
                'net'                => $net,
                'tax'                => $tax,
                'total'              => $inclusive ? $net : round($net + $tax, 2),
            ];
        }

        return $lines;
    }

    // =========================================================================
    //  الكوبون
    // =========================================================================

    public function couponError(Coupon $coupon, float $base, ?int $customerId, string $date, ?int $excludeOrderId = null): ?string
    {
        if (! $coupon->is_active) {
            return 'الكوبون غير مفعّل.';
        }
        if (! $this->inDateRange($coupon->start_date, $coupon->end_date, $date)) {
            return 'الكوبون خارج نطاق التاريخ الصالح (' . $coupon->start_date->toDateString() . ' → ' . $coupon->end_date->toDateString() . ').';
        }

        $usages = CouponUsage::where('coupon_id', $coupon->id)
            ->when($excludeOrderId, fn ($q) => $q->where(fn ($q2) => $q2->whereNull('order_id')->orWhere('order_id', '!=', $excludeOrderId)));

        if ($coupon->usage_limit !== null && (clone $usages)->count() >= (int) $coupon->usage_limit) {
            return 'تم استنفاد عدد مرات استخدام هذا الكوبون.';
        }
        if ($coupon->min_order_amount !== null && $base < (float) $coupon->min_order_amount) {
            return 'الحد الأدنى للطلب لاستخدام هذا الكوبون هو ' . number_format((float) $coupon->min_order_amount, 2) . ' ر.س';
        }
        if ($coupon->usage_per_user !== null) {
            if (! $customerId) {
                return 'اختر العميل أولاً: هذا الكوبون محدود الاستخدام لكل عميل.';
            }
            if ((clone $usages)->where('customer_id', $customerId)->count() >= (int) $coupon->usage_per_user) {
                return 'لقد وصل هذا العميل للحد الأقصى من استخدام هذا الكوبون.';
            }
        }

        return null;
    }

    public function couponDiscount(Coupon $coupon, float $base): float
    {
        if ($coupon->type === 'percentage') {
            $discount = round($base * min(100, (float) $coupon->value) / 100, 2);
            if ($coupon->max_discount !== null) {
                $discount = min($discount, (float) $coupon->max_discount);
            }
        } else {
            $discount = (float) $coupon->value;
        }

        return round(max(0, min($discount, $base)), 2);
    }

    // =========================================================================
    //  العروض
    // =========================================================================

    public function offerError(Offer $offer, string $date, ?int $excludeOrderId = null): ?string
    {
        if (! $offer->is_active) {
            return 'العرض غير مفعّل.';
        }
        if (! $this->inDateRange($offer->start_date, $offer->end_date, $date)) {
            return 'العرض خارج نطاق التاريخ الصالح (' . $offer->start_date->toDateString() . ' → ' . $offer->end_date->toDateString() . ').';
        }
        if ($offer->max_uses !== null && $this->offerUses($offer, $excludeOrderId) >= (int) $offer->max_uses) {
            return 'تم استنفاد عدد مرات استخدام هذا العرض.';
        }

        return null;
    }

    /** عدد الطلبات غير الملغاة التي تستخدم العرض */
    public function offerUses(Offer $offer, ?int $excludeOrderId = null): int
    {
        return Order::where('offer_id', $offer->id)
            ->where('status', '!=', 'cancelled')
            ->when($excludeOrderId, fn ($q) => $q->where('id', '!=', $excludeOrderId))
            ->count();
    }

    /**
     * @param  array  $lines  بنود منسّقة (normalizeItems)
     * @return array{valid: bool, discount: float, message: string}
     */
    public function offerDiscount(Offer $offer, array $lines, float $shipping = 0, ?int $customerId = null, ?int $excludeOrderId = null): array
    {
        $fail = fn (string $m) => ['valid' => false, 'discount' => 0.0, 'message' => $m];

        $orderNet = array_sum(array_column($lines, 'net'));
        if ($orderNet <= 0) {
            return $fail('لا يوجد مبلغ لتطبيق العرض عليه.');
        }

        $qualifying = $this->qualifyingLines($offer, $lines);
        $qNet       = array_sum(array_column($qualifying, 'net'));
        $qQty       = array_sum(array_map(fn ($l) => $l['quantity'] * $l['rate'], $qualifying));

        if ($offer->applies_to !== 'order' && empty($qualifying)) {
            return $fail('لا توجد منتجات مؤهلة لتطبيق هذا العرض.');
        }

        $pctOrAmount = function (float $base) use ($offer): float {
            if ((float) $offer->discount_percentage > 0) {
                return round($base * min(100, (float) $offer->discount_percentage) / 100, 2);
            }

            return min((float) $offer->discount_amount, $base);
        };

        switch ($offer->type) {
            case 'percentage':
                $discount = round($qNet * min(100, (float) $offer->value) / 100, 2);
                break;

            case 'fixed':
                $discount = min((float) $offer->value, $qNet);
                break;

            case 'first_order':
                $previous = Order::where('customer_id', $customerId)
                    ->where('status', '!=', 'cancelled')
                    ->when($excludeOrderId, fn ($q) => $q->where('id', '!=', $excludeOrderId))
                    ->exists();
                if (! $customerId || $previous) {
                    return $fail('عرض أول طلب: هذا العميل له طلبات سابقة.');
                }
                $discount = round($qNet * min(100, (float) $offer->value) / 100, 2);
                break;

            case 'buy_x_get_y':
                $discount = $this->calcBuyXGetY($offer, $qualifying);
                if ($discount <= 0) {
                    return $fail('يجب شراء ' . ((int) $offer->buy_quantity + (int) max(1, $offer->get_quantity)) . ' قطعة على الأقل من المنتجات المؤهلة.');
                }
                break;

            case 'buy_x_get_discount':
                if ($qQty < (int) $offer->buy_quantity) {
                    return $fail('يجب شراء ' . (int) $offer->buy_quantity . ' قطعة على الأقل من المنتجات المؤهلة.');
                }
                $discount = $pctOrAmount($qNet);
                break;

            case 'buy_amount_get_discount':
                if ($qNet < (float) $offer->min_amount) {
                    return $fail('يجب أن يكون إجمالي الطلب ' . number_format((float) $offer->min_amount, 2) . ' ر.س على الأقل.');
                }
                $discount = $pctOrAmount($qNet);
                break;

            case 'product_price_discount':
                // القيمة خصم على سعر الوحدة × عدد الوحدات المؤهلة
                $discount = 0;
                foreach ($qualifying as $l) {
                    $discount += min((float) $offer->value * $l['quantity'], $l['net']);
                }
                break;

            case 'tiered':
                $discount = $this->calcTiered($offer, $qNet);
                if ($discount <= 0) {
                    return $fail('إجمالي الطلب أقل من أول شريحة في العرض المتدرج.');
                }
                break;

            case 'flash':
                $limit = (float) ($offer->flash_quantity ?? 0);
                if ($limit > 0) {
                    $remaining = $limit - $this->flashUnitsSold($offer, $excludeOrderId);
                    if ($remaining <= 0) {
                        return $fail('نفدت الكمية المتاحة للعرض السريع.');
                    }
                    $qNet = $this->netForUnits($qualifying, $remaining);
                }
                $discount = $pctOrAmount($qNet);
                break;

            case 'free_shipping':
                if ($offer->min_amount !== null && $qNet < (float) $offer->min_amount) {
                    return $fail('الشحن المجاني للطلبات من ' . number_format((float) $offer->min_amount, 2) . ' ر.س.');
                }
                if ($shipping <= 0) {
                    return $fail('لا توجد تكلفة شحن لتطبيق الشحن المجاني عليها.');
                }
                $discount = $shipping;
                break;

            case 'bundle':
                $discount = $this->calcBundle($offer, $qualifying);
                if ($discount <= 0) {
                    return $fail('الحزمة تتطلب ' . (int) $offer->buy_quantity . ' قطع من المنتجات المؤهلة بسعر أعلى من سعر الحزمة.');
                }
                break;

            default:
                $discount = 0;
        }

        $discount = round(max(0, $discount), 2);

        return [
            'valid'    => true,
            'discount' => $discount,
            'message'  => 'تم تطبيق العرض «' . $offer->name . '» — خصم ' . number_format($discount, 2) . ' ر.س',
        ];
    }

    private function qualifyingLines(Offer $offer, array $lines): array
    {
        if ($offer->applies_to === 'order') {
            return $lines;
        }

        $ids = DB::table('offer_items')->where('offer_id', $offer->id)->where('item_type', $offer->applies_to)
            ->pluck('item_id')->map(fn ($v) => (int) $v)->all();

        return array_values(array_filter($lines, function ($l) use ($offer, $ids) {
            return $offer->applies_to === 'product'
                ? in_array((int) $l['product_id'], $ids, true)
                : ($l['category_id'] && in_array((int) $l['category_id'], $ids, true));
        }));
    }

    /** وحدات مفردة (سعر الوحدة بعد خصم البند) مرتبة تنازلياً */
    private function unitPrices(array $lines): array
    {
        $units = [];
        foreach ($lines as $l) {
            $n = (int) floor($l['quantity']);
            if ($n <= 0) {
                continue;
            }
            $unit = $l['net'] / $l['quantity'];
            for ($i = 0; $i < $n; $i++) {
                $units[] = $unit;
            }
        }
        rsort($units);

        return $units;
    }

    private function calcBuyXGetY(Offer $offer, array $lines): float
    {
        $buy   = max(1, (int) $offer->buy_quantity);
        $get   = max(1, (int) $offer->get_quantity);
        $units = $this->unitPrices($lines);
        $disc  = 0;
        foreach ($units as $i => $p) {
            if (($i % ($buy + $get)) >= $buy) {
                $disc += $p;
            }
        }
        // مجموعة ناقصة لا تُحتسب هديتها إلا إن اكتملت كمية الشراء
        return round($disc, 2);
    }

    /** حزمة: كل buy_quantity وحدة تُباع بسعر الحزمة (value) */
    private function calcBundle(Offer $offer, array $lines): float
    {
        $size  = max(1, (int) $offer->buy_quantity);
        $units = $this->unitPrices($lines);
        $disc  = 0;
        for ($g = 0; ($g + 1) * $size <= count($units); $g++) {
            $groupTotal = array_sum(array_slice($units, $g * $size, $size));
            $disc += max(0, $groupTotal - (float) $offer->value);
        }

        return round($disc, 2);
    }

    private function calcTiered(Offer $offer, float $base): float
    {
        $tiers = collect($offer->tier_thresholds ?? [])
            ->map(fn ($t) => [
                'min' => (float) ($t['min_amount'] ?? $t['min'] ?? 0),
                'pct' => (float) ($t['discount_percentage'] ?? $t['discount'] ?? $t['percentage'] ?? 0),
            ])
            ->sortByDesc('min');

        foreach ($tiers as $t) {
            if ($base >= $t['min'] && $t['pct'] > 0) {
                return round($base * min(100, $t['pct']) / 100, 2);
            }
        }

        return 0;
    }

    /** صافي أعلى $units وحدة من البنود المؤهلة */
    private function netForUnits(array $lines, float $units): float
    {
        $prices = $this->unitPrices($lines);

        return array_sum(array_slice($prices, 0, (int) floor($units)));
    }

    /** وحدات المنتجات المؤهلة المباعة بالعرض السريع في طلبات غير ملغاة */
    private function flashUnitsSold(Offer $offer, ?int $excludeOrderId): float
    {
        $orders = Order::with('items.product:id,category_id', 'items.unitConversion')
            ->where('offer_id', $offer->id)
            ->where('status', '!=', 'cancelled')
            ->when($excludeOrderId, fn ($q) => $q->where('id', '!=', $excludeOrderId))
            ->get();

        $sold = 0;
        foreach ($orders as $order) {
            $lines = $order->items->map(fn ($i) => [
                'product_id'  => $i->product_id,
                'category_id' => $i->product?->category_id,
                'quantity'    => (float) $i->quantity,
                'rate'        => (float) ($i->unitConversion?->conversion_rate ?: 1),
                'net'         => 0,
            ])->all();
            foreach ($this->qualifyingLines($offer, $lines) as $l) {
                $sold += $l['quantity'];
            }
        }

        return $sold;
    }

    private function inDateRange($start, $end, string $date): bool
    {
        $d = substr($date, 0, 10);

        return $start && $end
            && $start->toDateString() <= $d
            && $end->toDateString() >= $d;
    }
}
