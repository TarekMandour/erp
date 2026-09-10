<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\Purchase;
use App\Models\Finance\PurchaseItem;
use App\Models\Finance\Supplier;
use App\Models\Finance\Warehouse;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\UnitConversion;
use App\Events\Finance\Purchases\PurchaseEvent;
use App\Http\Requests\Finance\PurchaseRequest;
use App\Services\Finance\PurchaseService;
use App\Services\Finance\TransactionsService;

class PurchasesController extends Controller
{
    private string $route = 'finance.purchases';

    public function __construct(
        private PurchaseService $purchaseService,
        private TransactionsService $transactionsService,
    ) {}

    // -------------------------------------------------------
    // Index — DataTables
    // -------------------------------------------------------
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Purchase::with(['supplier:id,company_name,name', 'warehouse:id,name'])
                ->select('purchases.*');

            if ($request->filled('fsupplier')) {
                $query->where('supplier_id', $request->fsupplier);
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
                $query->where('purchase_number', 'like', '%' . $request->search . '%');
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('supplier_name', fn($row) => $row->supplier?->company_name ?: $row->supplier?->name ?? '—')
                ->addColumn('warehouse_name', fn($row) => $row->warehouse?->name ?? '—')
                ->addColumn('status_badge', fn($row) =>
                    '<span class="badge ' . $row->status_badge . '">' . $row->status_label . '</span>')
                ->addColumn('payment_label', fn($row) =>
                    Purchase::$paymentTypeLabels[$row->payment_type] ?? $row->payment_type)
                ->addColumn('total_fmt', fn($row) => number_format((float)$row->total, 2) . ' ر.س')
                ->addColumn('paid_fmt', fn($row) => number_format((float)$row->paid, 2) . ' ر.س')
                ->addColumn('remaining_fmt', fn($row) =>
                    '<span class="' . ((float)$row->remaining > 0 ? 'text-danger fw-bold' : 'text-success') . '">'
                    . number_format((float)$row->remaining, 2) . ' ر.س</span>')
                ->addColumn('action', function ($row) {
                    $show   = '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-light-success me-1" title="عرض وطباعة"><i class="ki-duotone ki-printer fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></a>';
                    $edit   = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-light-primary me-1" title="تعديل"><i class="ki-duotone ki-pencil fs-4"><span class="path1"></span><span class="path2"></span></i></a>';
                    $delete = '<form method="POST" action="' . route($this->route . '.delete') . '" class="d-inline">'
                        . csrf_field()
                        . '<input type="hidden" name="id" value="' . $row->id . '">'
                        . '<button type="submit" class="btn btn-xs btn-icon btn-light-danger" title="حذف" onclick="return confirm(\'حذف هذه الفاتورة؟\')"><i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i></button></form>';
                    return $show . $edit . $delete;
                })
                ->rawColumns(['status_badge', 'remaining_fmt', 'action'])
                ->make(true);
        }

        $suppliers = Supplier::select('id', 'company_name', 'name')->orderBy('company_name')->get();
        return view('finance.purchases.index', compact('suppliers'));
    }

    // -------------------------------------------------------
    // Create
    // -------------------------------------------------------
    public function create()
    {
        $warehouses  = Warehouse::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $pricingMode = \App\Helpers\Helper::pricingMode();
        return view('finance.purchases.create', [
            'warehouses'     => $warehouses,
            'purchaseNumber' => Purchase::generateNumber(),
            'pricingMode'    => $pricingMode,
        ]);
    }

    // -------------------------------------------------------
    // Store
    // -------------------------------------------------------
    public function store(PurchaseRequest $request)
    {
        $purchase = null;

        // تعطيل الـ Observer داخل المعاملة لمنع إنشاء قيود متعددة
        $dispatcher = Purchase::getEventDispatcher();
        Purchase::unsetEventDispatcher();

        try {
            DB::transaction(function () use ($request, &$purchase) {
                $purchase = Purchase::create($this->mapHeader($request));
                $this->syncItems($purchase, $request->items);
                $this->recalcTotals($purchase);
            });
        } finally {
            Purchase::setEventDispatcher($dispatcher);
        }

        // إطلاق حدث واحد فقط بعد اكتمال البيانات النهائية
        PurchaseEvent::dispatch($purchase->fresh(), 'create');

        $this->purchaseService->apply($purchase->fresh(['items.unitConversion']));

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم إضافة فاتورة الشراء بنجاح.');
    }

    // -------------------------------------------------------
    // Show
    // -------------------------------------------------------
    public function show($id)
    {
        $data = Purchase::with([
            'supplier',
            'warehouse:id,name',
            'items.product:id,name,sku',
            'items.variant:id,product_id,sku,attributes',
            'items.unitConversion',
        ])->findOrFail($id);

        $settings    = \App\Helpers\Helper::settings();
        $pricingMode = \App\Helpers\Helper::pricingMode();

        return view('finance.purchases.show', compact('data', 'settings', 'pricingMode'));
    }

    // -------------------------------------------------------
    // Edit
    // -------------------------------------------------------
    public function edit($id)
    {
        $data        = Purchase::with(['items.product:id,name,sku', 'items.variant:id,product_id,sku,attributes', 'items.unitConversion'])->findOrFail($id);
        $warehouses  = Warehouse::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $pricingMode = \App\Helpers\Helper::pricingMode();
        return view('finance.purchases.edit', compact('data', 'warehouses', 'pricingMode'));
    }

    // -------------------------------------------------------
    // Update
    // -------------------------------------------------------
    public function update(PurchaseRequest $request)
    {
        $purchase    = null;
        $isCancelled = $request->status === 'cancelled';

        $dispatcher = Purchase::getEventDispatcher();
        Purchase::unsetEventDispatcher();

        try {
            DB::transaction(function () use ($request, &$purchase, $isCancelled) {
                $purchase = Purchase::with('items.unitConversion')->findOrFail($request->id);

                // عكس آثار المحفظة + المخزون + التكلفة دائماً
                $this->purchaseService->reverse($purchase);

                $purchase->update($this->mapHeader($request));

                if (! $isCancelled) {
                    $purchase->items()->delete();
                    $this->syncItems($purchase, $request->items);
                    $this->recalcTotals($purchase);
                }
            });
        } finally {
            Purchase::setEventDispatcher($dispatcher);
        }

        if (! $isCancelled) {
            // إعادة إنشاء القيد مباشرة (synchronous) لتجنب تكرار القيود
            $this->transactionsService->regenerate($purchase->fresh());
            $this->purchaseService->apply($purchase->fresh(['items.unitConversion']));
        } else {
            // إلغاء: عكس القيد فقط
            $this->transactionsService->reverse($purchase->fresh());
        }

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تحديث فاتورة الشراء بنجاح.');
    }

    // -------------------------------------------------------
    // Delete
    // -------------------------------------------------------
    public function destroy(Request $request)
    {
        $purchase = Purchase::with('items.unitConversion')->findOrFail($request->id);

        // عكس المحفظة + المخزون + التكلفة
        $this->purchaseService->reverse($purchase);

        // عكس القيد المحاسبي
        $this->transactionsService->reverse($purchase);

        $purchase->delete();
        return back()->with('success', 'تم حذف الفاتورة بنجاح.');
    }

    // -------------------------------------------------------
    // Import Excel
    // -------------------------------------------------------
    public function importForm()
    {
        return view('finance.purchases.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'يرجى اختيار ملف.',
            'file.mimes'    => 'يجب أن يكون الملف بصيغة xlsx أو xls أو csv.',
            'file.max'      => 'الحد الأقصى لحجم الملف 5 ميجابايت.',
        ]);

        $rows = (new FastExcel)->import($request->file('file'));

        if ($rows->isEmpty()) {
            return back()->with('error', 'الملف فارغ أو لا يحتوي على بيانات.');
        }

        $errors  = [];
        $created = 0;

        DB::transaction(function () use ($rows, &$errors, &$created) {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // row number in Excel (1 = header)

                // Normalize keys
                $row = array_map('trim', array_change_key_case((array) $row));

                $supplierName  = $row['المورد']        ?? $row['supplier']   ?? null;
                $warehouseName = $row['المستودع']       ?? $row['warehouse']  ?? null;
                $date          = $row['التاريخ']        ?? $row['date']       ?? null;
                $paymentType   = $row['نوع الدفع']      ?? $row['payment']    ?? 'cash';
                $notes         = $row['الملاحظات']      ?? $row['notes']      ?? null;
                $productSku    = $row['رمز المنتج']     ?? $row['sku']        ?? null;
                $quantity      = $row['الكمية']         ?? $row['quantity']   ?? null;
                $unitCost      = $row['سعر الوحدة']     ?? $row['unit_cost']  ?? null;
                $discountAmt   = $row['الخصم']          ?? $row['discount']   ?? 0;
                $taxRate       = $row['نسبة الضريبة']   ?? $row['tax_rate']   ?? 0;

                // Validate required fields
                if (!$supplierName || !$warehouseName || !$date || !$productSku || !$quantity || !$unitCost) {
                    $errors[] = "السطر {$rowNum}: بيانات ناقصة (المورد / المستودع / التاريخ / المنتج / الكمية / السعر).";
                    continue;
                }

                $supplier  = Supplier::where('company_name', $supplierName)->orWhere('name', $supplierName)->first();
                $warehouse = Warehouse::where('name', $warehouseName)->first();
                $product   = Product::where('sku', $productSku)->orWhere('name', $productSku)->first();

                if (!$supplier)  { $errors[] = "السطر {$rowNum}: المورد «{$supplierName}» غير موجود.";    continue; }
                if (!$warehouse) { $errors[] = "السطر {$rowNum}: المستودع «{$warehouseName}» غير موجود."; continue; }
                if (!$product)   { $errors[] = "السطر {$rowNum}: المنتج «{$productSku}» غير موجود.";      continue; }

                $paymentMap = ['نقدي' => 'cash', 'آجل' => 'credit', 'أقساط' => 'installments', 'محفظة الكترونيه' => 'wallet', 'تحويل بنكي اونلاين' => 'bank_online', 'تحويل بنكي مباشر' => 'bank_direct', 'شيك' => 'check'];
                $paymentType = $paymentMap[$paymentType] ?? (in_array($paymentType, ['cash', 'credit', 'installments', 'wallet', 'bank_online', 'bank_direct', 'check']) ? $paymentType : 'cash');

                $qty      = (float) $quantity;
                $cost     = (float) $unitCost;
                $disc     = (float) $discountAmt;
                $tax      = (float) $taxRate;
                $rowTotal = round(($qty * $cost - $disc) * (1 + $tax / 100), 2);

                $purchase = Purchase::create([
                    'purchase_number' => Purchase::generateNumber(),
                    'supplier_id'     => $supplier->id,
                    'warehouse_id'    => $warehouse->id,
                    'date'            => \Carbon\Carbon::parse($date)->format('Y-m-d'),
                    'payment_type'    => $paymentType,
                    'status'          => 'pending',
                    'notes'           => $notes,
                    'subtotal'        => round($qty * $cost - $disc, 2),
                    'discount'        => $disc,
                    'tax'             => round(($qty * $cost - $disc) * $tax / 100, 2),
                    'total'           => $rowTotal,
                    'paid'            => 0,
                ]);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $product->id,
                    'variant_id'  => null,
                    'quantity'    => $qty,
                    'unit_cost'   => $cost,
                    'discount'    => $disc,
                    'tax_rate'    => $tax,
                    'total'       => $rowTotal,
                ]);

                $created++;
            }
        });

        $msg = "تم استيراد {$created} فاتورة بنجاح.";
        if (!empty($errors)) {
            $msg .= ' أخطاء: ' . implode(' | ', $errors);
            return back()->with('warning', $msg);
        }

        return redirect()->route($this->route . '.index')->with('success', $msg);
    }

    // -------------------------------------------------------
    // AJAX: search suppliers for Select2
    // -------------------------------------------------------
    public function ajaxSuppliers(Request $request)
    {
        $search = $request->search;
        $rows   = Supplier::select('id', 'company_name', 'name')
            ->when($search, fn($q) => $q->where('company_name', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('company_name')
            ->paginate(20);

        return response()->json([
            'results'    => $rows->map(fn($s) => ['id' => $s->id, 'text' => $s->company_name ?: $s->name]),
            'pagination' => ['more' => $rows->hasMorePages()],
        ]);
    }

    // -------------------------------------------------------
    // AJAX: search products for Select2 (purchase items row)
    // -------------------------------------------------------
    public function ajaxProducts(Request $request)
    {
        $search = $request->search;
        $rows   = Product::select('id', 'name', 'sku', 'purchase_price', 'tax_rate')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"))
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'results'    => $rows->map(fn($p) => [
                'id'             => $p->id,
                'text'           => "{$p->name} ({$p->sku})",
                'purchase_price' => \App\Helpers\Helper::resolvePurchasePrice($p->id, null),
                'tax_rate'       => (float)$p->tax_rate,
            ]),
            'pagination' => ['more' => $rows->hasMorePages()],
        ]);
    }

    // -------------------------------------------------------
    // AJAX: get variants for a product
    // -------------------------------------------------------
    public function ajaxVariants(Request $request)
    {
        $productId = (int)$request->product_id;
        $variants  = ProductVariant::where('product_id', $productId)
            ->where('is_active', true)
            ->select('id', 'product_id', 'sku', 'attributes', 'purchase_price', 'average_cost')
            ->get()
            ->map(fn($v) => [
                'id'             => $v->id,
                'text'           => $v->sku . (is_array($v->attributes) ? ' — ' . implode(', ', $v->attributes) : ''),
                'purchase_price' => \App\Helpers\Helper::resolvePurchasePrice($productId, $v->id),
            ]);

        return response()->json(['results' => $variants]);
    }

    // -------------------------------------------------------
    // AJAX: resolve purchase price (average_cost priority)
    // -------------------------------------------------------
    public function ajaxPrice(Request $request)
    {
        $productId = (int) $request->product_id;
        $variantId = $request->filled('variant_id') ? (int) $request->variant_id : null;

        $variant = $variantId
            ? ProductVariant::where('id', $variantId)->select('id', 'average_cost', 'purchase_price')->first()
            : null;

        return response()->json([
            'purchase_price'          => \App\Helpers\Helper::resolvePurchasePrice($productId, $variantId),
            'variant_average_cost'    => $variant ? (float)$variant->average_cost    : null,
            'variant_purchase_price'  => $variant ? (float)$variant->purchase_price  : null,
            'product_average_cost'    => (float)(Product::where('id', $productId)->value('average_cost')    ?? 0),
            'product_purchase_price'  => (float)(Product::where('id', $productId)->value('purchase_price') ?? 0),
        ]);
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
                'text'            => $c->targetUnit->name . ' (× ' . rtrim(rtrim((string)$c->conversion_rate, '0'), '.') . ' ' . $c->baseUnit->name . ')',
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
    // Export Excel template
    // -------------------------------------------------------
    public function exportTemplate()
    {
        $template = collect([[
            'المورد'       => 'اسم الشركة',
            'المستودع'     => 'اسم المستودع',
            'التاريخ'      => '2026-01-01',
            'نوع الدفع'    => 'نقدي',
            'رمز المنتج'   => 'SKU-001',
            'الكمية'       => 10,
            'سعر الوحدة'   => 50.00,
            'الخصم'        => 0,
            'نسبة الضريبة' => 15,
            'الملاحظات'    => '',
        ]]);

        return (new FastExcel($template))->download('purchases_template.xlsx');
    }

    // -------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------
    private function mapHeader(PurchaseRequest $request): array
    {
        return [
            'purchase_number' => $request->purchase_number ?: Purchase::generateNumber(),
            'supplier_id'     => $request->supplier_id,
            'warehouse_id'    => $request->warehouse_id,
            'date'            => $request->date,
            'due_date'        => $request->due_date ?: null,
            'payment_type'    => $request->payment_type,
            'status'          => $request->status,
            'notes'           => $request->notes ?: null,
            'paid'            => (float) ($request->paid ?? 0),
        ];
    }

    private function syncItems(Purchase $purchase, array $items): void
    {
        $pricingMode = \App\Helpers\Helper::pricingMode();
        foreach ($items as $item) {
            $qty      = (float) $item['quantity'];
            $cost     = (float) $item['unit_cost'];
            $disc     = (float) ($item['discount'] ?? 0);
            $taxRate  = (float) ($item['tax_rate'] ?? 0);

            if ($pricingMode === 'inclusive') {
                // السعر شامل الضريبة: الإجمالي = qty * cost - disc
                $rowTotal = round($qty * $cost - $disc, 2);
            } else {
                // السعر غير شامل الضريبة
                $rowTotal = round(($qty * $cost - $disc) * (1 + $taxRate / 100), 2);
            }

            $unitConversionId = !empty($item['unit_conversion_id']) ? (int)$item['unit_conversion_id'] : null;

            PurchaseItem::create([
                'purchase_id'        => $purchase->id,
                'product_id'         => $item['product_id'],
                'variant_id'         => $item['variant_id'] ?: null,
                'unit_conversion_id' => $unitConversionId,
                'quantity'           => $qty,
                'unit_cost'          => $cost,
                'discount'           => $disc,
                'tax_rate'           => $taxRate,
                'total'              => $rowTotal,
            ]);
        }
    }

    private function recalcTotals(Purchase $purchase): void
    {
        $pricingMode = \App\Helpers\Helper::pricingMode();
        $items       = $purchase->items()->get();
        $discount    = $items->sum(fn($i) => (float)$i->discount);

        if ($pricingMode === 'inclusive') {
            // استخراج الضريبة من السعر الشامل: tax = gross * rate / (100 + rate)
            $grossSum = $items->sum(fn($i) => (float)$i->quantity * (float)$i->unit_cost - (float)$i->discount);
            $tax      = $items->sum(fn($i) => round(
                ((float)$i->quantity * (float)$i->unit_cost - (float)$i->discount)
                    * (float)$i->tax_rate / (100 + (float)$i->tax_rate),
                2
            ));
            $subtotal = round($grossSum - $tax, 2);

            $purchase->update([
                'subtotal' => $subtotal,
                'discount' => round($discount, 2),
                'tax'      => round($tax, 2),
                'total'    => round($grossSum, 2),
            ]);
        } else {
            $subtotal = $items->sum(fn($i) => (float)$i->quantity * (float)$i->unit_cost - (float)$i->discount);
            $tax      = $items->sum(fn($i) => round(((float)$i->quantity * (float)$i->unit_cost - (float)$i->discount) * (float)$i->tax_rate / 100, 2));

            $purchase->update([
                'subtotal' => round($subtotal, 2),
                'discount' => round($discount, 2),
                'tax'      => round($tax, 2),
                'total'    => round($subtotal + $tax, 2),
            ]);
        }
    }

}
