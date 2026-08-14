<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\Order;
use App\Models\Finance\OrderItem;
use App\Models\Finance\Customer;
use App\Models\Finance\Warehouse;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\ProductVariantPrice;
use App\Models\Finance\UnitConversion;
use App\Models\Finance\InventoryItem;
use App\Models\Finance\Coupon;
use App\Models\Finance\CouponUsage;
use App\Models\Finance\Offer;
use App\Http\Requests\Finance\OrderRequest;
use App\Services\Finance\OrderService;
use App\Services\Finance\TransactionsService;

class OrdersController extends Controller
{
    private string $route = 'finance.orders';

    public function __construct(
        private OrderService $orderService,
        private TransactionsService $transactionsService,
    ) {}

    // -------------------------------------------------------
    // Index — DataTables
    // -------------------------------------------------------
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Order::with(['customer:id,name,customer_code', 'warehouse:id,name'])
                ->select('orders.*');

            if ($request->filled('fcustomer')) {
                $query->where('customer_id', $request->fcustomer);
            }
            if ($request->filled('fstatus')) {
                $query->where('status', $request->fstatus);
            }
            if ($request->filled('fpayment')) {
                $query->where('payment_type', $request->fpayment);
            }
            if ($request->filled('fdate_from')) {
                $query->whereDate('date', '>=', $request->fdate_from);
            }
            if ($request->filled('fdate_to')) {
                $query->whereDate('date', '<=', $request->fdate_to);
            }
            if ($request->filled('search')) {
                $query->where('order_number', 'like', '%' . $request->search . '%');
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('customer_name', fn($row) =>
                    ($row->customer?->name ?? '—') . ($row->customer?->customer_code ? ' (' . $row->customer->customer_code . ')' : ''))
                ->addColumn('warehouse_name', fn($row) => $row->warehouse?->name ?? '—')
                ->addColumn('status_badge', fn($row) =>
                    '<span class="badge ' . $row->status_badge . '">' . $row->status_label . '</span>')
                ->addColumn('payment_label', fn($row) =>
                    Order::$paymentTypeLabels[$row->payment_type] ?? $row->payment_type)
                ->addColumn('total_fmt', fn($row) => number_format((float)$row->total, 2) . ' ر.س')
                ->addColumn('paid_fmt', fn($row) => number_format((float)$row->paid, 2) . ' ر.س')
                ->addColumn('remaining_fmt', fn($row) =>
                    '<span class="' . ((float)$row->remaining > 0 ? 'text-danger fw-bold' : 'text-success') . '">'
                    . number_format((float)$row->remaining, 2) . ' ر.س</span>')
                ->addColumn('action', function ($row) {
                    $show = '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-light-success me-1" title="عرض وطباعة"><i class="ki-duotone ki-printer fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></a>';
                    $edit = $row->is_locked ? '' :
                        '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-light-primary me-1" title="تعديل"><i class="ki-duotone ki-pencil fs-4"><span class="path1"></span><span class="path2"></span></i></a>';
                    $delete = $row->is_locked ? '' :
                        '<form method="POST" action="' . route($this->route . '.delete') . '" class="d-inline">'
                        . csrf_field()
                        . '<input type="hidden" name="id" value="' . $row->id . '">'
                        . '<button type="submit" class="btn btn-xs btn-icon btn-light-danger" title="حذف" onclick="return confirm(\'حذف هذا الطلب؟\')"><i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i></button></form>';
                    return $show . $edit . $delete;
                })
                ->rawColumns(['status_badge', 'remaining_fmt', 'action'])
                ->make(true);
        }

        $customers = Customer::select('id', 'name', 'customer_code')->orderBy('name')->get();
        return view('finance.orders.index', compact('customers'));
    }

    // -------------------------------------------------------
    // Create
    // -------------------------------------------------------
    public function create()
    {
        $warehouses  = Warehouse::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $orderNumber = Order::generateNumber();
        $pricingMode = \App\Helpers\Helper::pricingMode();
        return view('finance.orders.create', compact('warehouses', 'orderNumber', 'pricingMode'));
    }

    // -------------------------------------------------------
    // Store
    // -------------------------------------------------------
    public function store(OrderRequest $request)
    {
        // Validate stock availability before saving
        $stockError = $this->checkStock($request->items, $request->warehouse_id);
        if ($stockError) {
            return back()->withInput()->withErrors(['items' => $stockError]);
        }

        $order = null;

        $dispatcher = Order::getEventDispatcher();
        Order::unsetEventDispatcher();

        try {
            DB::transaction(function () use ($request, &$order) {
                $order = Order::create($this->mapHeader($request));
                $this->syncItems($order, $request->items);
                $this->recalcTotals($order);
                $this->recordCouponUsage($order, $request);
                $this->recordOfferUsage($order, null);
            });
        } finally {
            Order::setEventDispatcher($dispatcher);
        }

        if ($order->status === 'confirmed') {
            $this->transactionsService->process($order->fresh());
            $this->orderService->apply($order->fresh(['items.unitConversion']));
        }

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم إضافة الطلب بنجاح.');
    }

    // -------------------------------------------------------
    // Show
    // -------------------------------------------------------
    public function show($id)
    {
        $data = Order::with([
            'customer',
            'warehouse:id,name',
            'items.product:id,name,sku',
            'items.variant:id,product_id,sku,attributes',
        ])->findOrFail($id);

        $settings    = \App\Helpers\Helper::settings();
        $pricingMode = \App\Helpers\Helper::pricingMode();
        return view('finance.orders.show', compact('data', 'settings', 'pricingMode'));
    }

    // -------------------------------------------------------
    // Edit
    // -------------------------------------------------------
    public function edit($id)
    {
        $data = Order::with(['items.product:id,name,sku', 'items.variant:id,product_id,sku,attributes', 'items.unitConversion'])
            ->findOrFail($id);

        if ($data->is_locked) {
            return redirect()->route($this->route . '.show', $id)
                ->with('warning', 'لا يمكن تعديل هذا الطلب — الحالة: ' . $data->status_label);
        }

        $warehouses = Warehouse::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $pricingMode = \App\Helpers\Helper::pricingMode();
        return view('finance.orders.edit', compact('data', 'warehouses', 'pricingMode'));
    }

    // -------------------------------------------------------
    // Update
    // -------------------------------------------------------
    public function update(OrderRequest $request)
    {
        $order = Order::with('items.unitConversion')->findOrFail($request->id);

        if ($order->is_locked) {
            return back()->withErrors(['items' => 'لا يمكن تعديل طلب بهذه الحالة.']);
        }

        $stockError = $this->checkStock($request->items, $request->warehouse_id, $order->id);
        if ($stockError) {
            return back()->withInput()->withErrors(['items' => $stockError]);
        }

        $wasConfirmed = $order->status === 'confirmed';
        $isCancelled  = $request->status === 'cancelled';
        $willConfirm  = $request->status === 'confirmed';

        // عكس الآثار القديمة إذا كان الطلب مؤكداً
        if ($wasConfirmed) {
            $this->orderService->reverse($order);
            $this->transactionsService->reverse($order);
        }

        $dispatcher = Order::getEventDispatcher();
        Order::unsetEventDispatcher();

        try {
            DB::transaction(function () use ($request, $order, $isCancelled) {
                $oldCouponId = $order->coupon_id;
                $oldOfferId  = $order->offer_id;
                $order->update($this->mapHeader($request));

                if (! $isCancelled) {
                    $order->items()->delete();
                    $this->syncItems($order, $request->items);
                    $this->recalcTotals($order);
                    if ($oldCouponId && $oldCouponId !== $order->coupon_id) {
                        CouponUsage::where('order_id', $order->id)->delete();
                        Coupon::where('id', $oldCouponId)->decrement('used_count');
                    }
                    $this->recordCouponUsage($order, $request);
                    $this->recordOfferUsage($order, $oldOfferId);
                }
            });
        } finally {
            Order::setEventDispatcher($dispatcher);
        }

        if ($willConfirm) {
            $this->transactionsService->process($order->fresh());
            $this->orderService->apply($order->fresh(['items.unitConversion']));
        }

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تحديث الطلب بنجاح.');
    }

    // -------------------------------------------------------
    // Delete
    // -------------------------------------------------------
    public function destroy(Request $request)
    {
        $order = Order::with('items.unitConversion')->findOrFail($request->id);

        if ($order->is_locked) {
            return back()->with('error', 'لا يمكن حذف طلب بهذه الحالة.');
        }

        if ($order->status === 'confirmed') {
            $this->orderService->reverse($order);
            $this->transactionsService->reverse($order);
        }

        $order->delete();
        return back()->with('success', 'تم حذف الطلب بنجاح.');
    }

    // -------------------------------------------------------
    // Import Excel
    // -------------------------------------------------------
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'يرجى اختيار ملف.',
            'file.mimes'    => 'يجب أن يكون الملف بصيغة xlsx أو xls أو csv.',
            'file.max'      => 'الحد الأقصى لحجم الملف 5 ميجابايت.',
        ]);

        $rows    = (new FastExcel)->import($request->file('file'));
        $errors  = [];
        $created = 0;

        if ($rows->isEmpty()) {
            return back()->with('error', 'الملف فارغ أو لا يحتوي على بيانات.');
        }

        DB::transaction(function () use ($rows, &$errors, &$created) {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;
                $row    = array_map('trim', array_change_key_case((array)$row));

                $customerName  = $row['العميل']       ?? $row['customer']      ?? null;
                $warehouseName = $row['المستودع']      ?? $row['warehouse']     ?? null;
                $date          = $row['التاريخ']       ?? $row['date']          ?? null;
                $paymentType   = $row['طريقة الدفع']   ?? $row['payment']       ?? 'cash';
                $notes         = $row['الملاحظات']     ?? $row['notes']         ?? null;
                $productSku    = $row['رمز المنتج']    ?? $row['sku']           ?? null;
                $quantity      = $row['الكمية']        ?? $row['quantity']      ?? null;
                $unitPrice     = $row['سعر الوحدة']    ?? $row['unit_price']    ?? null;
                $discountAmt   = $row['الخصم']         ?? $row['discount']      ?? 0;
                $taxRate       = $row['نسبة الضريبة']  ?? $row['tax_rate']      ?? 0;

                if (!$customerName || !$warehouseName || !$date || !$productSku || !$quantity || !$unitPrice) {
                    $errors[] = "السطر {$rowNum}: بيانات ناقصة.";
                    continue;
                }

                $customer  = Customer::where('name', $customerName)->orWhere('customer_code', $customerName)->first();
                $warehouse = Warehouse::where('name', $warehouseName)->first();
                $product   = Product::where('sku', $productSku)->orWhere('name', $productSku)->first();

                if (!$customer)  { $errors[] = "السطر {$rowNum}: العميل «{$customerName}» غير موجود.";    continue; }
                if (!$warehouse) { $errors[] = "السطر {$rowNum}: المستودع «{$warehouseName}» غير موجود."; continue; }
                if (!$product)   { $errors[] = "السطر {$rowNum}: المنتج «{$productSku}» غير موجود.";      continue; }

                $paymentMap  = ['نقدي' => 'cash', 'آجل' => 'credit', 'محفظة' => 'wallet'];
                $paymentType = $paymentMap[$paymentType] ?? (in_array($paymentType, ['cash', 'credit', 'wallet']) ? $paymentType : 'cash');

                $qty      = (float)$quantity;
                $price    = (float)$unitPrice;
                $disc     = (float)$discountAmt;
                $tax      = (float)$taxRate;
                $rowTotal = round(($qty * $price - $disc) * (1 + $tax / 100), 2);

                // Stock check
                $stock = InventoryItem::where('warehouse_id', $warehouse->id)
                    ->where('product_id', $product->id)
                    ->whereNull('variant_id')
                    ->value('quantity') ?? 0;

                if ((float)$stock < $qty) {
                    $errors[] = "السطر {$rowNum}: الكمية المطلوبة ({$qty}) تتجاوز المخزون المتاح ({$stock}).";
                    continue;
                }

                $order = Order::create([
                    'order_number'  => Order::generateNumber(),
                    'customer_id'   => $customer->id,
                    'warehouse_id'  => $warehouse->id,
                    'date'          => \Carbon\Carbon::parse($date)->format('Y-m-d'),
                    'payment_type'  => $paymentType,
                    'status'        => 'confirmed',
                    'notes'         => $notes,
                    'subtotal'      => round($qty * $price - $disc, 2),
                    'discount'      => $disc,
                    'tax'           => round(($qty * $price - $disc) * $tax / 100, 2),
                    'shipping_cost' => 0,
                    'total'         => $rowTotal,
                    'paid'          => 0,
                ]);

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'quantity'   => $qty,
                    'unit_price' => $price,
                    'discount'   => $disc,
                    'tax_rate'   => $tax,
                    'total'      => $rowTotal,
                ]);

                $created++;
            }
        });

        $msg = "تم استيراد {$created} طلب بنجاح.";
        if (!empty($errors)) {
            $msg .= ' أخطاء: ' . implode(' | ', $errors);
            return back()->with('warning', $msg);
        }

        return redirect()->route($this->route . '.index')->with('success', $msg);
    }

    // -------------------------------------------------------
    // Export template
    // -------------------------------------------------------
    public function exportTemplate()
    {
        $template = collect([[
            'العميل'       => 'اسم العميل',
            'المستودع'     => 'اسم المستودع',
            'التاريخ'      => '2026-01-01',
            'طريقة الدفع'  => 'نقدي',
            'رمز المنتج'   => 'SKU-001',
            'الكمية'       => 5,
            'سعر الوحدة'   => 100.00,
            'الخصم'        => 0,
            'نسبة الضريبة' => 15,
            'الملاحظات'    => '',
        ]]);

        return (new FastExcel($template))->download('orders_template.xlsx');
    }

    // -------------------------------------------------------
    // AJAX: validate coupon code
    // -------------------------------------------------------
    public function ajaxValidateCoupon(Request $request)
    {
        $code       = trim($request->code ?? '');
        $subtotal   = (float)($request->subtotal ?? 0);
        $customerId = $request->filled('customer_id') ? (int)$request->customer_id : null;
        $orderId    = $request->filled('order_id')    ? (int)$request->order_id    : null;

        if (!$code) {
            return response()->json(['valid' => false, 'message' => 'يرجى إدخال رمز الكوبون.']);
        }

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json(['valid' => false, 'message' => 'رمز الكوبون غير صحيح.']);
        }

        if (!$coupon->is_active) {
            return response()->json(['valid' => false, 'message' => 'الكوبون غير مفعّل.']);
        }

        $today = now()->toDateString();
        $startDate = (string)$coupon->start_date;
        $endDate   = (string)$coupon->end_date;
        if ($startDate > $today || $endDate < $today) {
            return response()->json(['valid' => false, 'message' => 'الكوبون خارج نطاق التاريخ الصالح.']);
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['valid' => false, 'message' => 'تم استنفاد عدد مرات استخدام هذا الكوبون.']);
        }

        if ($coupon->min_order_amount !== null && $subtotal < (float)$coupon->min_order_amount) {
            return response()->json([
                'valid'   => false,
                'message' => 'الحد الأدنى للطلب لاستخدام هذا الكوبون هو ' . number_format((float)$coupon->min_order_amount, 2) . ' ر.س',
            ]);
        }

        if ($customerId && $coupon->usage_per_user !== null) {
            $usedByUser = CouponUsage::where('coupon_id', $coupon->id)
                ->where('customer_id', $customerId)
                ->when($orderId, fn($q) => $q->where('order_id', '!=', $orderId))
                ->count();
            if ($usedByUser >= $coupon->usage_per_user) {
                return response()->json(['valid' => false, 'message' => 'لقد وصلت للحد الأقصى من استخدام هذا الكوبون.']);
            }
        }

        if ($coupon->type === 'percentage') {
            $discount = round($subtotal * (float)$coupon->value / 100, 2);
            if ($coupon->max_discount !== null) {
                $discount = min($discount, (float)$coupon->max_discount);
            }
        } else {
            $discount = min((float)$coupon->value, $subtotal);
        }

        return response()->json([
            'valid'      => true,
            'coupon_id'  => $coupon->id,
            'discount'   => $discount,
            'type'       => $coupon->type,
            'value'      => (float)$coupon->value,
            'message'    => 'تم تطبيق الكوبون — خصم ' . number_format($discount, 2) . ' ر.س',
        ]);
    }

    // -------------------------------------------------------
    // AJAX: search active offers for Select2
    // -------------------------------------------------------
    public function ajaxSearchOffers(Request $request)
    {
        $today  = now()->toDateString();
        $search = $request->search;

        $rows = Offer::where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where(fn($q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('priority')
            ->select('id', 'name', 'type')
            ->paginate(20);

        return response()->json([
            'results'    => $rows->map(fn($o) => [
                'id'   => $o->id,
                'text' => $o->name . ' — ' . ($o->type_label ?? $o->type),
            ]),
            'pagination' => ['more' => $rows->hasMorePages()],
        ]);
    }

    // -------------------------------------------------------
    // AJAX: apply offer to current order items
    // -------------------------------------------------------
    public function ajaxApplyOffer(Request $request)
    {
        $offerId = (int)($request->offer_id ?? 0);
        $items   = $request->items ?? [];

        if (!$offerId) {
            return response()->json(['valid' => false, 'message' => 'يرجى اختيار عرض.']);
        }

        if (empty($items)) {
            return response()->json(['valid' => false, 'message' => 'أضف منتجات إلى الطلب أولاً.']);
        }

        $offer = Offer::find($offerId);

        if (!$offer) {
            return response()->json(['valid' => false, 'message' => 'العرض غير موجود.']);
        }

        if (!$offer->is_active) {
            return response()->json(['valid' => false, 'message' => 'العرض غير مفعّل.']);
        }

        $today = now()->toDateString();
        if ((string)$offer->start_date > $today || (string)$offer->end_date < $today) {
            return response()->json(['valid' => false, 'message' => 'العرض خارج نطاق التاريخ الصالح.']);
        }

        if ($offer->max_uses !== null && $offer->used_count >= $offer->max_uses) {
            return response()->json(['valid' => false, 'message' => 'تم استنفاد عدد مرات استخدام هذا العرض.']);
        }

        $result = $this->applyOfferDiscount($offer, $items);

        if (isset($result['valid']) && !$result['valid']) {
            return response()->json($result);
        }

        return response()->json([
            'valid'    => true,
            'offer_id' => $offer->id,
            'discount' => $result['discount'],
            'message'  => $result['message'],
        ]);
    }

    // -------------------------------------------------------
    // AJAX: check stock for a product/variant/warehouse
    // -------------------------------------------------------
    public function ajaxStock(Request $request)
    {
        $stock = InventoryItem::where('warehouse_id', $request->warehouse_id)
            ->where('product_id', $request->product_id)
            ->when($request->filled('variant_id'), fn($q) => $q->where('variant_id', $request->variant_id),
                fn($q) => $q->whereNull('variant_id'))
            ->value('quantity') ?? 0;

        return response()->json(['stock' => (float)$stock]);
    }

    // -------------------------------------------------------
    // AJAX: scan product by SKU / barcode
    // -------------------------------------------------------
    public function ajaxScanProduct(Request $request)
    {
        $code        = trim($request->code ?? '');
        $warehouseId = (int)($request->warehouse_id ?? 0);

        if (!$code) {
            return response()->json(['found' => false, 'message' => 'رمز فارغ.']);
        }

        // Check variant SKU first (barcode may map to a variant)
        $variant = ProductVariant::where('sku', $code)
            ->where('is_active', true)
            ->with('product:id,name,sku,selling_price,tax_rate,is_active,average_cost,cost_price')
            ->first();

        if ($variant && $variant->product?->is_active) {
            $product        = $variant->product;
            $customerId     = $request->filled('customer_id') ? (int)$request->customer_id : null;
            $effectivePrice = \App\Helpers\Helper::resolveSellingPrice($product->id, $variant->id, $customerId);

            $stock = $warehouseId
                ? (float)(InventoryItem::where('warehouse_id', $warehouseId)
                    ->where('product_id', $product->id)
                    ->where('variant_id', $variant->id)
                    ->value('quantity') ?? 0)
                : 0;

            $variantLabel = $variant->sku;
            if (is_array($variant->attributes) && count($variant->attributes)) {
                $variantLabel .= ' — ' . implode(', ', array_values($variant->attributes));
            }

            return response()->json([
                'found'         => true,
                'product_id'    => $product->id,
                'product_text'  => $product->name . ' (' . $product->sku . ')',
                'variant_id'    => $variant->id,
                'variant_text'  => $variantLabel,
                'selling_price' => $effectivePrice,
                'tax_rate'      => (float)$product->tax_rate,
                'average_cost'  => (float)$variant->average_cost ?: (float)$variant->cost_price,
                'stock'         => $stock,
            ]);
        }

        // Fall back to product SKU
        $product = Product::where('sku', $code)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return response()->json(['found' => false, 'message' => "لم يُعثر على منتج بالرمز «{$code}».  "]);
        }

        $stock = $warehouseId
            ? (float)(InventoryItem::where('warehouse_id', $warehouseId)
                ->where('product_id', $product->id)
                ->whereNull('variant_id')
                ->value('quantity') ?? 0)
            : 0;

        // Priority: product_variant_prices > products.selling_price
        $effectivePrice = \App\Helpers\Helper::resolveSellingPrice($product->id, null, $request->filled('customer_id') ? (int)$request->customer_id : null);

        return response()->json([
            'found'         => true,
            'product_id'    => $product->id,
            'product_text'  => $product->name . ' (' . $product->sku . ')',
            'variant_id'    => null,
            'variant_text'  => null,
            'selling_price' => $effectivePrice,
            'tax_rate'      => (float)$product->tax_rate,
            'average_cost'  => (float)$product->average_cost ?: (float)$product->cost_price,
            'stock'         => $stock,
        ]);
    }

    // -------------------------------------------------------
    // AJAX: search customers for Select2
    // -------------------------------------------------------
    public function ajaxCustomers(Request $request)
    {
        $search = $request->search;
        $rows   = Customer::select('id', 'name', 'customer_code')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('customer_code', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'results'    => $rows->map(fn($c) => ['id' => $c->id, 'text' => $c->name . ($c->customer_code ? ' (' . $c->customer_code . ')' : '')]),
            'pagination' => ['more' => $rows->hasMorePages()],
        ]);
    }

    // -------------------------------------------------------
    // AJAX: search products for Select2
    // -------------------------------------------------------
    public function ajaxProducts(Request $request)
    {
        $search      = $request->search;
        $warehouseId = $request->warehouse_id;

        $rows = Product::select('products.id', 'products.name', 'products.sku', 'products.selling_price', 'products.tax_rate', 'products.average_cost', 'products.cost_price')
            ->when($search, fn($q) => $q->where('products.name', 'like', "%{$search}%")
                ->orWhere('products.sku', 'like', "%{$search}%"))
            ->where('products.is_active', true)
            ->orderBy('products.name')
            ->paginate(20);

        $results = $rows->map(function ($p) use ($warehouseId, $request) {
            $stock = 0;
            if ($warehouseId) {
                $stock = (float) (InventoryItem::where('warehouse_id', $warehouseId)
                    ->where('product_id', $p->id)
                    ->whereNull('variant_id')
                    ->value('quantity') ?? 0);
            }
            // Priority: product_variant_prices > product_variants > products
            $customerId     = $request->filled('customer_id') ? (int)$request->customer_id : null;
            $effectivePrice = \App\Helpers\Helper::resolveSellingPrice($p->id, null, $customerId);
            return [
                'id'            => $p->id,
                'text'          => $p->name . ' (' . $p->sku . ')',
                'selling_price' => $effectivePrice,
                'tax_rate'      => (float)$p->tax_rate,
                'average_cost'  => (float)$p->average_cost ?: (float)$p->cost_price,
                'stock'         => $stock,
            ];
        });

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => $rows->hasMorePages()],
        ]);
    }

    // -------------------------------------------------------
    // AJAX: get variants for a product (with stock)
    // -------------------------------------------------------
    public function ajaxVariants(Request $request)
    {
        $warehouseId = $request->warehouse_id;

        $variants = ProductVariant::where('product_id', $request->product_id)
            ->where('is_active', true)
            ->select('id', 'product_id', 'sku', 'attributes', 'selling_price', 'average_cost', 'cost_price')
            ->get()
            ->map(function ($v) use ($warehouseId, $request) {
                $stock = 0;
                if ($warehouseId) {
                    $stock = (float)(InventoryItem::where('warehouse_id', $warehouseId)
                        ->where('product_id', $v->product_id)
                        ->where('variant_id', $v->id)
                        ->value('quantity') ?? 0);
                }
                // Priority: product_variant_prices > product_variants > products
                $customerId     = $request->filled('customer_id') ? (int)$request->customer_id : null;
                $effectivePrice = \App\Helpers\Helper::resolveSellingPrice($v->product_id, $v->id, $customerId);

                $label = $v->sku;
                if (is_array($v->attributes) && count($v->attributes)) {
                    $label .= ' — ' . implode(', ', array_values($v->attributes));
                }
                return [
                    'id'            => $v->id,
                    'text'          => $label,
                    'selling_price' => $effectivePrice,
                    'average_cost'  => (float)$v->average_cost ?: (float)$v->cost_price,
                    'stock'         => $stock,
                ];
            });

        return response()->json(['results' => $variants]);
    }

    // -------------------------------------------------------
    // AJAX: get unit conversions for a product/variant
    // -------------------------------------------------------
    public function ajaxUnitConversions(Request $request)
    {
        $productId = (int)$request->product_id;
        $variantId = $request->filled('variant_id') ? (int)$request->variant_id : null;

        $conversions = UnitConversion::where('product_id', $productId)
            ->when(
                $variantId,
                fn($q) => $q->where(fn($q2) => $q2->where('variant_id', $variantId)->orWhereNull('variant_id')),
                fn($q) => $q->whereNull('variant_id')
            )
            ->get(['id', 'base_unit', 'target_unit', 'conversion_rate', 'is_default', 'allow_fractions', 'decimal_places']);

        return response()->json([
            'results' => $conversions->map(fn($c) => [
                'id'              => $c->id,
                'text'            => $c->target_unit . ' (× ' . rtrim(rtrim((string)$c->conversion_rate, '0'), '.') . ' ' . $c->base_unit . ')',
                'base_unit'       => $c->base_unit,
                'target_unit'     => $c->target_unit,
                'conversion_rate' => (float)$c->conversion_rate,
                'is_default'      => (bool)$c->is_default,
                'allow_fractions' => (bool)$c->allow_fractions,
                'decimal_places'  => (int)$c->decimal_places,
            ]),
        ]);
    }

    // -------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------

    /**
     * @deprecated Use \App\Helpers\Helper::resolveSellingPrice() instead.
     */
    private function resolveProductPrice(int $productId, float $fallback, ?int $customerId = null): float
    {
        return \App\Helpers\Helper::resolveSellingPrice($productId, null, $customerId);
    }

    /**
     * @deprecated Use \App\Helpers\Helper::resolveSellingPrice() instead.
     */
    private function resolveVariantPrice(int $variantId, float $fallback, ?int $customerId = null): float
    {
        // Retrieve product_id from the variant to delegate to Helper
        $productId = \App\Models\Finance\ProductVariant::where('id', $variantId)->value('product_id');
        return \App\Helpers\Helper::resolveSellingPrice((int)$productId, $variantId, $customerId);
    }

    /**
     * @deprecated Use \App\Helpers\Helper::extractPvpPrice() instead.
     */
    private function extractPrice(ProductVariantPrice $record, string $today): float
    {
        return \App\Helpers\Helper::extractPvpPrice($record, $today);
    }

    /**
     * Record coupon usage and increment used_count (skips if no coupon applied).
     * Idempotent for updates: replaces existing usage record.
     */
    private function recordCouponUsage(Order $order, Request $request): void
    {
        $couponId = $order->coupon_id;
        if (!$couponId || !$order->customer_id) {
            return;
        }

        $discountAmount = (float)$order->coupon_discount;

        $existing = CouponUsage::where('order_id', $order->id)->where('coupon_id', $couponId)->first();
        if ($existing) {
            $existing->update(['discount_amount' => $discountAmount, 'used_at' => now()]);
            return;
        }

        CouponUsage::create([
            'coupon_id'       => $couponId,
            'customer_id'     => $order->customer_id,
            'order_id'        => $order->id,
            'discount_amount' => $discountAmount,
            'used_at'         => now(),
        ]);

        Coupon::where('id', $couponId)->increment('used_count');
    }

    /**
     * Check stock for all items. Returns error string or null.
     * $excludeOrderId: when updating, exclude current order's reserved qty (if applicable).
     */
    private function checkStock(array $items, int $warehouseId, ?int $excludeOrderId = null): ?string
    {
        // When updating a confirmed order, its quantities are already deducted from inventory.
        // Add them back per product/variant so the check is based on effective available stock.
        $reservedQtys = [];
        if ($excludeOrderId) {
            $existingOrder = Order::find($excludeOrderId);
            if ($existingOrder && $existingOrder->status === 'confirmed') {
                $existingItems = OrderItem::where('order_id', $excludeOrderId)->get();
                foreach ($existingItems as $ei) {
                    $rate = 1;
                    if ($ei->unit_conversion_id) {
                        $rate = (float)(UnitConversion::find($ei->unit_conversion_id)?->conversion_rate ?? 1);
                    }
                    $key = $ei->product_id . '_' . ($ei->variant_id ?? 'null');
                    $reservedQtys[$key] = ($reservedQtys[$key] ?? 0) + round((float)$ei->quantity * $rate, 6);
                }
            }
        }

        $errors = [];

        foreach ($items as $idx => $item) {
            $qty              = (float)($item['quantity'] ?? 0);
            $productId        = (int)($item['product_id'] ?? 0);
            $variantId        = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
            $unitConversionId = !empty($item['unit_conversion_id']) ? (int)$item['unit_conversion_id'] : null;

            // Convert entered qty to base units for stock comparison
            if ($unitConversionId) {
                $rate = (float)(UnitConversion::find($unitConversionId)?->conversion_rate ?? 1);
                $qty  = round($qty * $rate, 6);
            }

            $stock = (float)(InventoryItem::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->when($variantId,
                    fn($q) => $q->where('variant_id', $variantId),
                    fn($q) => $q->whereNull('variant_id'))
                ->value('quantity') ?? 0);

            // Add back this order's previously reserved quantity (effective available stock)
            $key   = $productId . '_' . ($variantId ?? 'null');
            $stock += ($reservedQtys[$key] ?? 0);

            if ($qty > $stock) {
                $productName = Product::find($productId)?->name ?? "#$productId";
                $errors[] = "المنتج «{$productName}» — الكمية المطلوبة ({$qty}) تتجاوز المخزون المتاح ({$stock}).";
            }
        }

        return $errors ? implode("\n", $errors) : null;
    }

    private function mapHeader(OrderRequest $request): array
    {
        return [
            'order_number'    => $request->order_number ?: Order::generateNumber(),
            'customer_id'     => $request->customer_id,
            'warehouse_id'    => $request->warehouse_id,
            'date'            => $request->date,
            'delivery_date'   => $request->delivery_date ?: null,
            'payment_type'    => $request->payment_type,
            'status'          => $request->status,
            'notes'           => $request->notes ?: null,
            'shipping_address'=> $request->shipping_address ?: null,
            'shipping_cost'   => (float)($request->shipping_cost ?? 0),
            'coupon_id'       => $request->filled('coupon_id') ? (int)$request->coupon_id : null,
            'coupon_discount' => (float)($request->coupon_discount ?? 0),
            'offer_id'        => $request->filled('offer_id') ? (int)$request->offer_id : null,
            'offer_discount'  => (float)($request->offer_discount ?? 0),
            'paid'            => (float)($request->paid ?? 0),
        ];
    }

    private function syncItems(Order $order, array $items): void
    {
        $pricingMode = \App\Helpers\Helper::pricingMode();
        foreach ($items as $item) {
            $qty              = (float)$item['quantity'];
            $price            = (float)$item['unit_price'];
            $disc             = (float)($item['discount'] ?? 0);
            $taxRate          = (float)($item['tax_rate'] ?? 0);
            $unitConversionId = !empty($item['unit_conversion_id']) ? (int)$item['unit_conversion_id'] : null;

            if ($pricingMode === 'inclusive') {
                // السعر شامل الضريبة: الإجمالي = qty * price - disc (بدون إضافة ضريبة)
                $rowTotal = round($qty * $price - $disc, 2);
            } else {
                // السعر غير شامل الضريبة: الإجمالي = (qty * price - disc) * (1 + taxRate/100)
                $rowTotal = round(($qty * $price - $disc) * (1 + $taxRate / 100), 2);
            }

            OrderItem::create([
                'order_id'           => $order->id,
                'product_id'         => $item['product_id'],
                'variant_id'         => $item['variant_id'] ?: null,
                'unit_conversion_id' => $unitConversionId,
                'quantity'           => $qty,
                'unit_price'         => $price,
                'unit_cost'          => (float)($item['unit_cost'] ?? 0),
                'discount'           => $disc,
                'tax_rate'           => $taxRate,
                'total'              => $rowTotal,
            ]);
        }
    }

    private function recalcTotals(Order $order): void
    {
        $pricingMode = \App\Helpers\Helper::pricingMode();
        $items       = $order->items()->get();
        $discount    = $items->sum(fn($i) => (float)$i->discount);

        if ($pricingMode === 'inclusive') {
            // السعر شامل الضريبة: استخراج الضريبة من السعر
            // tax = gross * rate / (100 + rate)
            $grossSum = $items->sum(fn($i) => (float)$i->quantity * (float)$i->unit_price - (float)$i->discount);
            $tax      = $items->sum(fn($i) => round(
                ((float)$i->quantity * (float)$i->unit_price - (float)$i->discount)
                    * (float)$i->tax_rate / (100 + (float)$i->tax_rate),
                2
            ));
            $subtotal = round($grossSum - $tax, 2);

            $order->update([
                'subtotal'        => $subtotal,
                'discount'        => round($discount, 2),
                'tax'             => round($tax, 2),
                'total'           => round($grossSum + (float)$order->shipping_cost - (float)$order->coupon_discount - (float)$order->offer_discount, 2),
            ]);
        } else {
            // السعر غير شامل الضريبة: إضافة الضريبة فوق السعر
            $subtotal = $items->sum(fn($i) => (float)$i->quantity * (float)$i->unit_price - (float)$i->discount);
            $tax      = $items->sum(fn($i) => round(((float)$i->quantity * (float)$i->unit_price - (float)$i->discount) * (float)$i->tax_rate / 100, 2));

            $order->update([
                'subtotal'        => round($subtotal, 2),
                'discount'        => round($discount, 2),
                'tax'             => round($tax, 2),
                'total'           => round($subtotal + $tax + (float)$order->shipping_cost - (float)$order->coupon_discount - (float)$order->offer_discount, 2),
            ]);
        }
    }

    /**
     * Record / update offer used_count when an offer is applied to an order.
     * Pass $previousOfferId when updating so the old offer's used_count is decremented.
     */
    private function recordOfferUsage(Order $order, ?int $previousOfferId): void
    {
        // Decrement old offer if it changed
        if ($previousOfferId && $previousOfferId !== $order->offer_id) {
            Offer::where('id', $previousOfferId)->decrement('used_count');
        }

        if (!$order->offer_id) {
            return;
        }

        // Only increment if it's a new offer being applied (not same offer on update)
        if ($previousOfferId === null || $previousOfferId !== $order->offer_id) {
            Offer::where('id', $order->offer_id)->increment('used_count');
        }
    }

    /**
     * Apply offer discount to submitted items.
     * Returns ['discount' => float, 'message' => string] or ['valid' => false, 'message' => string].
     */
    private function applyOfferDiscount(Offer $offer, array $items): array
    {
        // Subtotal from submitted items
        $subtotal = collect($items)->sum(
            fn($i) => (float)($i['quantity'] ?? 0) * (float)($i['unit_price'] ?? 0) - (float)($i['discount'] ?? 0)
        );

        if ($subtotal <= 0) {
            return ['valid' => false, 'message' => 'لا يوجد مبلغ لتطبيق العرض عليه.'];
        }

        // Resolve qualifying items based on applies_to
        $qualifyingItems = $this->resolveQualifyingItems($offer, $items);

        $qualifyingSubtotal = collect($qualifyingItems)->sum(
            fn($i) => (float)($i['quantity'] ?? 0) * (float)($i['unit_price'] ?? 0) - (float)($i['discount'] ?? 0)
        );
        $qualifyingQty = collect($qualifyingItems)->sum(fn($i) => (float)($i['quantity'] ?? 0));

        $discount = 0;

        switch ($offer->type) {
            case 'percentage':
                $discount = round($subtotal * (float)$offer->value / 100, 2);
                break;

            case 'fixed':
                $discount = min((float)$offer->value, $subtotal);
                break;

            case 'buy_x_get_y':
                if (empty($qualifyingItems)) {
                    return ['valid' => false, 'message' => 'لا توجد منتجات مؤهلة لتطبيق هذا العرض.'];
                }
                $discount = $this->calcBuyXGetY($offer, $qualifyingItems);
                if ($discount <= 0) {
                    return ['valid' => false, 'message' => 'يجب شراء ' . (int)$offer->buy_quantity . ' قطعة على الأقل من المنتجات المؤهلة.'];
                }
                break;

            case 'buy_x_get_discount':
                if ($qualifyingQty < (int)$offer->buy_quantity) {
                    return [
                        'valid'   => false,
                        'message' => 'يجب شراء ' . (int)$offer->buy_quantity . ' قطعة على الأقل من المنتجات المؤهلة.',
                    ];
                }
                if ((float)$offer->discount_percentage > 0) {
                    $discount = round($qualifyingSubtotal * (float)$offer->discount_percentage / 100, 2);
                } else {
                    $discount = min((float)$offer->discount_amount, $qualifyingSubtotal);
                }
                break;

            case 'buy_amount_get_discount':
                if ($subtotal < (float)$offer->min_amount) {
                    return [
                        'valid'   => false,
                        'message' => 'يجب أن يكون إجمالي الطلب ' . number_format((float)$offer->min_amount, 2) . ' ر.س على الأقل.',
                    ];
                }
                if ((float)$offer->discount_percentage > 0) {
                    $discount = round($subtotal * (float)$offer->discount_percentage / 100, 2);
                } else {
                    $discount = min((float)$offer->discount_amount, $subtotal);
                }
                break;

            case 'product_price_discount':
                if (empty($qualifyingItems)) {
                    return ['valid' => false, 'message' => 'لا توجد منتجات مؤهلة لتطبيق هذا العرض.'];
                }
                if ((float)$offer->discount_percentage > 0) {
                    $discount = round($qualifyingSubtotal * (float)$offer->discount_percentage / 100, 2);
                } else {
                    $discount = min((float)$offer->discount_amount, $qualifyingSubtotal);
                }
                break;

            case 'tiered':
                $discount = $this->calcTieredDiscount($offer, $subtotal);
                break;

            case 'flash':
            case 'bundle':
            case 'first_order':
                if ((float)$offer->discount_percentage > 0) {
                    $discount = round($subtotal * (float)$offer->discount_percentage / 100, 2);
                } elseif ((float)$offer->discount_amount > 0) {
                    $discount = min((float)$offer->discount_amount, $subtotal);
                } elseif ((float)$offer->value > 0) {
                    $discount = min((float)$offer->value, $subtotal);
                }
                break;

            case 'free_shipping':
                // Returns zero order discount (shipping handled separately)
                $discount = 0;
                break;
        }

        $discount = max(0, min(round($discount, 2), $subtotal));

        return [
            'discount' => $discount,
            'message'  => 'تم تطبيق العرض «' . $offer->name . '» — خصم ' . number_format($discount, 2) . ' ر.س',
        ];
    }

    /**
     * Filter submitted items to those qualifying for the offer.
     */
    private function resolveQualifyingItems(Offer $offer, array $items): array
    {
        if ($offer->applies_to === 'order') {
            return $items;
        }

        $offerProductIds  = DB::table('offer_items')
            ->where('offer_id', $offer->id)->where('item_type', 'product')
            ->pluck('item_id')->toArray();

        $offerCategoryIds = DB::table('offer_items')
            ->where('offer_id', $offer->id)->where('item_type', 'category')
            ->pluck('item_id')->toArray();

        $qualifying = [];
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            if (!$productId) continue;

            if ($offer->applies_to === 'product' && in_array($productId, $offerProductIds)) {
                $qualifying[] = $item;
            } elseif ($offer->applies_to === 'category' && !empty($offerCategoryIds)) {
                $catId = Product::where('id', $productId)->value('category_id');
                if ($catId && in_array($catId, $offerCategoryIds)) {
                    $qualifying[] = $item;
                }
            }
        }

        return $qualifying;
    }

    /**
     * Buy X get Y free: sort all qualifying units by price DESC.
     * In each group of (buy_qty + get_qty), the cheapest get_qty units are free.
     */
    private function calcBuyXGetY(Offer $offer, array $qualifyingItems): float
    {
        $buyQty  = max(1, (int)$offer->buy_quantity);
        $getQty  = max(1, (int)$offer->get_quantity);
        $groupSz = $buyQty + $getQty;

        // Expand into individual units (floor quantity to whole units)
        $units = [];
        foreach ($qualifyingItems as $item) {
            $qty   = (int)floor((float)($item['quantity'] ?? 0));
            $price = (float)($item['unit_price'] ?? 0);
            for ($i = 0; $i < $qty; $i++) {
                $units[] = $price;
            }
        }

        if (count($units) < $buyQty) {
            return 0;
        }

        // Sort DESC — most expensive first (cheapest in each group get freed)
        rsort($units);

        $discount = 0;
        $total    = count($units);

        for ($i = 0; $i < $total; $i++) {
            // Within each group, positions >= buyQty are free
            if (($i % $groupSz) >= $buyQty) {
                $discount += $units[$i];
            }
        }

        return round($discount, 2);
    }

    /**
     * Tiered discount: pick the tier matching the subtotal and return percentage off.
     * tier_thresholds format: [{"min": 500, "discount": 5}, {"min": 1000, "discount": 10}, ...]
     */
    private function calcTieredDiscount(Offer $offer, float $subtotal): float
    {
        $tiers = $offer->tier_thresholds ?? [];
        if (empty($tiers)) {
            return 0;
        }

        // Sort by min DESC to find the highest applicable tier
        usort($tiers, fn($a, $b) => (float)($b['min'] ?? 0) <=> (float)($a['min'] ?? 0));

        foreach ($tiers as $tier) {
            $min = (float)($tier['min'] ?? 0);
            if ($subtotal >= $min) {
                $pct = (float)($tier['discount'] ?? $tier['percentage'] ?? 0);
                return round($subtotal * $pct / 100, 2);
            }
        }

        return 0;
    }
}
