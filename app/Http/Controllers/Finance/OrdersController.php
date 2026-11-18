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
use App\Services\Finance\OrderPricingService;
use Illuminate\Validation\ValidationException;

class OrdersController extends Controller
{
    private string $route = 'finance.orders';

    public function __construct(
        private OrderService $orderService,
        private OrderPricingService $pricing,
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
        return $this->guard(function () use ($request) {
            DB::transaction(fn () => $this->persist($this->mapHeader($request), $request->items));

            return redirect()->route($this->route . '.index')
                ->with('success', 'تم إضافة الطلب بنجاح.');
        });
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
        $order = Order::with('items')->findOrFail($request->id);

        if ($order->is_locked) {
            return back()->withErrors(['items' => 'لا يمكن تعديل طلب بهذه الحالة.']);
        }

        return $this->guard(function () use ($request, $order) {
            DB::transaction(function () use ($request, $order) {
                if ($request->status === 'cancelled') {
                    // الإلغاء: عكس كل الآثار مع إبقاء بيانات الطلب كما هي
                    $this->cancel($order, $request->notes);
                    return;
                }

                $this->persist($this->mapHeader($request, $order), $request->items, $order);
            });

            return redirect()->route($this->route . '.index')
                ->with('success', 'تم تحديث الطلب بنجاح.');
        });
    }

    // -------------------------------------------------------
    // Delete
    // -------------------------------------------------------
    public function destroy(Request $request)
    {
        $order = Order::with('items')->findOrFail($request->id);

        if ($order->is_locked) {
            return back()->with('error', 'لا يمكن حذف طلب بهذه الحالة.');
        }

        return $this->guard(function () use ($order) {
            DB::transaction(function () use ($order) {
                if ($order->is_effective) {
                    $this->orderService->reverse($order);
                }
                CouponUsage::where('order_id', $order->id)->delete();
                $order->items()->delete();
                $order->delete();
                $this->recountUsage([$order->coupon_id], [$order->offer_id]);
            });

            return back()->with('success', 'تم حذف الطلب وعكس آثاره بنجاح.');
        });
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

            // QA 6: نفس مسار الحفظ (مخزون + محفظة + قيد) — الاستيراد كان يُنشئ طلبات مؤكدة بلا أي أثر
            try {
                DB::transaction(fn () => $this->persist([
                    'order_number'  => Order::generateNumber(),
                    'customer_id'   => $customer->id,
                    'warehouse_id'  => $warehouse->id,
                    'date'          => \Carbon\Carbon::parse($date)->format('Y-m-d'),
                    'payment_type'  => $paymentType,
                    'status'        => 'confirmed',
                    'notes'         => $notes,
                    'shipping_cost' => 0,
                    'paid'          => 0,
                ], [[
                    'product_id' => $product->id,
                    'quantity'   => (float) $quantity,
                    'unit_price' => (float) $unitPrice,
                    'discount'   => (float) $discountAmt,
                    'tax_rate'   => (float) $taxRate,
                ]]));
                $created++;
            } catch (ValidationException $e) {
                $errors[] = "السطر {$rowNum}: " . collect($e->errors())->flatten()->first();
            }
        }

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
        $date       = $request->filled('date') ? (string)$request->date : now()->toDateString();

        if (!$code) {
            return response()->json(['valid' => false, 'message' => 'يرجى إدخال رمز الكوبون.']);
        }

        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon) {
            return response()->json(['valid' => false, 'message' => 'رمز الكوبون غير صحيح.']);
        }

        // QA 6: نفس قواعد الخادم عند الحفظ (التاريخ شامل يوم البدء، الحدود بدون هذا الطلب)
        if ($error = $this->pricing->couponError($coupon, $subtotal, $customerId, $date, $orderId)) {
            return response()->json(['valid' => false, 'message' => $error]);
        }

        $discount = $this->pricing->couponDiscount($coupon, $subtotal);

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
        $orderId = $request->filled('order_id') ? (int)$request->order_id : null;
        $date    = $request->filled('date') ? (string)$request->date : now()->toDateString();

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

        if ($error = $this->pricing->offerError($offer, $date, $orderId)) {
            return response()->json(['valid' => false, 'message' => $error]);
        }

        if ($request->filled('coupon_id') && !$offer->is_stackable) {
            return response()->json(['valid' => false, 'message' => 'العرض «' . $offer->name . '» غير قابل للجمع مع كوبون خصم.']);
        }

        try {
            $lines = $this->pricing->normalizeItems(array_values(array_filter($items, fn($i) => !empty($i['product_id']))));
        } catch (ValidationException $e) {
            return response()->json(['valid' => false, 'message' => collect($e->errors())->flatten()->first()]);
        }

        $result = $this->pricing->offerDiscount(
            $offer,
            $lines,
            (float)($request->shipping_cost ?? 0),
            $request->filled('customer_id') ? (int)$request->customer_id : null,
            $orderId
        );

        if (!$result['valid']) {
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
        $variantId = $request->filled('variant_id') ? (int) $request->variant_id : null;

        return response()->json([
            'results' => UnitConversion::dropdownFor((int) $request->product_id, $variantId),
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
     * QA 6: تنفيذ طلب (حفظ أو تحديث) داخل معاملة واحدة:
     * تسعير الخادم ← تحقق المدفوع ← عكس الآثار القديمة عند الحاجة ← حفظ ← تطبيق الآثار ← الاستخدامات
     *
     * @throws ValidationException
     */
    private function persist(array $header, array $items, ?Order $order = null): Order
    {
        $priced = $this->pricing->price($header, $items, $order);
        $header = array_merge($header, $priced['header']);
        $header['paid'] = round((float)($header['paid'] ?? 0), 2);

        if ($header['paid'] > (float)$header['total'] + 0.001) {
            throw ValidationException::withMessages([
                'paid' => 'المبلغ المدفوع (' . number_format($header['paid'], 2) . ') أكبر من إجمالي الطلب (' . number_format((float)$header['total'], 2) . ').',
            ]);
        }

        $wasEffective  = (bool)$order?->is_effective;
        $willEffective = in_array($header['status'], Order::$effectiveStatuses, true);
        $oldCoupon     = $order?->coupon_id;
        $oldOffer      = $order?->offer_id;

        $changed = ! $order || $this->fingerprint($order->fresh('items')) !== $this->fingerprint(null, $header, $priced['items']);

        if ($wasEffective && (! $willEffective || $changed)) {
            $this->orderService->reverse($order);
        }

        if ($order) {
            $order->update($header);
        } else {
            $order = Order::create($header);
        }

        if ($changed) {
            $order->items()->delete();
            foreach ($priced['items'] as $line) {
                OrderItem::create([
                    'order_id'           => $order->id,
                    'product_id'         => $line['product_id'],
                    'variant_id'         => $line['variant_id'],
                    'unit_conversion_id' => $line['unit_conversion_id'],
                    'quantity'           => $line['quantity'],
                    'unit_price'         => $line['unit_price'],
                    'unit_cost'          => 0, // تُثبَّت من متوسط التكلفة عند التأكيد
                    'discount'           => $line['discount'],
                    'tax_rate'           => $line['tax_rate'],
                    'total'              => $line['total'],
                ]);
            }
        }

        if ($willEffective && (! $wasEffective || $changed)) {
            $this->orderService->apply($order->fresh());
        }

        $this->syncCouponUsage($order);
        $this->recountUsage([$oldCoupon, $order->coupon_id], [$oldOffer, $order->offer_id]);

        return $order;
    }

    /**
     * إلغاء الطلب: عكس المخزون والمحفظة والقيود، وتحرير الكوبون والعرض
     */
    private function cancel(Order $order, ?string $notes = null): void
    {
        if ($order->is_effective) {
            $this->orderService->reverse($order);
        }

        $order->update(['status' => 'cancelled', 'notes' => $notes ?: $order->notes]);
        CouponUsage::where('order_id', $order->id)->delete();
        $this->recountUsage([$order->coupon_id], [$order->offer_id]);
    }

    private function syncCouponUsage(Order $order): void
    {
        CouponUsage::where('order_id', $order->id)->delete();

        if ($order->coupon_id && $order->customer_id && $order->status !== 'cancelled') {
            CouponUsage::create([
                'coupon_id'       => $order->coupon_id,
                'customer_id'     => $order->customer_id,
                'order_id'        => $order->id,
                'discount_amount' => $order->coupon_discount,
                'used_at'         => now(),
            ]);
        }
    }

    /** used_count = الاستخدامات الفعلية (بدل زيادة/إنقاص قد ينحرف) */
    private function recountUsage(array $couponIds, array $offerIds): void
    {
        foreach (array_unique(array_filter($couponIds)) as $id) {
            Coupon::where('id', $id)->update(['used_count' => CouponUsage::where('coupon_id', $id)->count()]);
        }
        foreach (array_unique(array_filter($offerIds)) as $id) {
            $offer = Offer::find($id);
            if ($offer) {
                $offer->update(['used_count' => $this->pricing->offerUses($offer)]);
            }
        }
    }

    /** بصمة البيانات المالية للطلب: إن لم تتغير لا يُعاد الترحيل عند تغيير الحالة فقط */
    private function fingerprint(?Order $order, array $header = [], array $items = []): string
    {
        if ($order) {
            $header = $order->only(['customer_id', 'warehouse_id', 'payment_type', 'bank_account_id', 'paid', 'total', 'shipping_cost', 'coupon_discount', 'offer_discount']);
            $header['date'] = $order->date?->toDateString();
            $items = $order->items->map(fn ($i) => $i->only(['product_id', 'variant_id', 'unit_conversion_id', 'quantity', 'unit_price', 'discount', 'tax_rate']))->all();
        }

        $n = fn ($v) => is_numeric($v) ? number_format((float)$v, 3, '.', '') : (string)$v;
        $h = collect(['customer_id', 'warehouse_id', 'payment_type', 'bank_account_id', 'paid', 'total', 'shipping_cost', 'coupon_discount', 'offer_discount', 'date'])
            ->map(fn ($k) => $n($k === 'date' ? substr((string)($header[$k] ?? ''), 0, 10) : ($header[$k] ?? '')))->implode('|');
        $l = collect($items)->map(fn ($i) => collect(['product_id', 'variant_id', 'unit_conversion_id', 'quantity', 'unit_price', 'discount', 'tax_rate'])
            ->map(fn ($k) => $n($i[$k] ?? ''))->implode(','))->implode(';');

        return md5($h . '#' . $l);
    }

    /**
     * أخطاء التحقق تعود للنموذج؛ أي خطأ آخر يلغي العملية كاملة برسالة واضحة (بدل 500 وطلب نصف محفوظ)
     */
    private function guard(\Closure $action)
    {
        try {
            return $action();
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('error', 'تعذّر حفظ الطلب ولم يُطبَّق أي أثر: ' . $e->getMessage());
        }
    }

    private function mapHeader(OrderRequest $request, ?Order $existing = null): array
    {
        $bankTypes = ['wallet', 'bank_online', 'bank_direct'];

        return [
            // QA 6: رقم الطلب لا يتغير عند التعديل
            'order_number'     => $existing?->order_number ?? ($request->order_number ?: Order::generateNumber()),
            'customer_id'      => $request->customer_id,
            'warehouse_id'     => $request->warehouse_id,
            'date'             => $request->date,
            'delivery_date'    => $request->delivery_date ?: null,
            'payment_type'     => $request->payment_type,
            'bank_account_id'  => in_array($request->payment_type, $bankTypes, true) && $request->filled('bank_account_id')
                ? (int)$request->bank_account_id
                : null,
            'status'           => $request->status,
            'notes'            => $request->notes ?: null,
            'shipping_address' => $request->shipping_address ?: null,
            'shipping_cost'    => (float)($request->shipping_cost ?? 0),
            'coupon_id'        => $request->filled('coupon_id') ? (int)$request->coupon_id : null,
            'offer_id'         => $request->filled('offer_id') ? (int)$request->offer_id : null,
            'paid'             => (float)($request->paid ?? 0),
        ];
    }
}
