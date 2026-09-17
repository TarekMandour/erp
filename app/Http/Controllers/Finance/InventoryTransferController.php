<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\InventoryItem;
use App\Models\Finance\InventoryTransaction;
use App\Models\Finance\WarehouseTransaction;
use App\Models\Finance\InventoryTransfer;
use App\Models\Finance\Product;
use App\Models\Finance\UnitConversion;
use App\Models\Finance\Warehouse;

class InventoryTransferController extends Controller
{
    private $route = 'finance.inventory.transfers';

    // -------------------------------------------------------
    // Index
    // -------------------------------------------------------
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = InventoryTransfer::with(['fromWarehouse', 'toWarehouse', 'product', 'variant', 'unit'])
                ->select('inventory_transfers.*');

            if ($request->filled('ffrom')) {
                $query->where('from_warehouse_id', $request->ffrom);
            }
            if ($request->filled('fto')) {
                $query->where('to_warehouse_id', $request->fto);
            }
            if ($request->filled('fstatus')) {
                $query->where('status', $request->fstatus);
            }
            if ($request->filled('fproduct')) {
                $query->where('product_id', $request->fproduct);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('from_name',    fn($row) => e($row->fromWarehouse->name ?? '—'))
                ->addColumn('to_name',      fn($row) => e($row->toWarehouse->name  ?? '—'))
                ->addColumn('product_name', fn($row) => e($row->product->name      ?? '—'))
                ->addColumn('variant_info', function ($row) {
                    if (!$row->variant) return '—';
                    $attrs = $row->variant->attributes ?? [];
                    return is_array($attrs)
                        ? implode(' / ', array_values($attrs))
                        : e($row->variant->sku);
                })
                ->addColumn('qty_display',    fn($row) => number_format((float) $row->quantity, 3))
                ->addColumn('unit_name',      fn($row) => e($row->unit->name ?? '—'))
                ->addColumn('status_badge',   fn($row) => $row->status_badge)
                ->addColumn('date_display',   fn($row) => $row->created_at->format('Y-m-d H:i'))
                ->addColumn('action', function ($row) {
                    $show   = '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-light-primary me-1" title="عرض"><i class="bi bi-eye fs-5"></i></a>';
                    $cancel = '';
                    if ($row->status === 'pending') {
                        $cancel = '<form method="POST" action="' . route($this->route . '.cancel', $row->id) . '" class="d-inline">'
                            . csrf_field()
                            . '<button type="submit" class="btn btn-xs btn-icon btn-light-danger" title="إلغاء" onclick="return confirm(\'إلغاء هذا التحويل؟\')">'
                            . '<i class="bi bi-x-circle fs-5"></i></button></form>';
                    }
                    return $show . $cancel;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();

        return view('finance.inventory.transfers.index', compact('warehouses', 'products'));
    }

    // -------------------------------------------------------
    // Create Form
    // -------------------------------------------------------
    public function create()
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();

        return view('finance.inventory.transfers.create', compact('warehouses', 'products'));
    }

    // -------------------------------------------------------
    // Store (execute transfer immediately)
    // -------------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id'   => ['required', 'exists:warehouses,id', function ($attr, $val, $fail) use ($request) {
                if ($val == $request->from_warehouse_id) {
                    $fail('لا يمكن التحويل بين نفس المستودع.');
                }
            }],
            'product_id'        => 'required|exists:products,id',
            'variant_id'        => 'nullable|exists:product_variants,id',
            'unit_conversion_id' => 'nullable|exists:unit_conversions,id',
            'quantity'          => 'required|numeric|min:0.001',
            'notes'             => 'nullable|string|max:500',
        ]);

        $product   = Product::findOrFail($request->product_id);
        $variantId = ($product->has_variants && $request->filled('variant_id')) ? $request->variant_id : null;

        $enteredQty      = (float) $request->quantity;
        $unitConversion  = $request->filled('unit_conversion_id') ? UnitConversion::find($request->unit_conversion_id) : null;
        $qty             = $unitConversion ? $enteredQty * (float) $unitConversion->conversion_rate : $enteredQty;

        // Check source stock
        $sourceItem = InventoryItem::where('warehouse_id', $request->from_warehouse_id)
            ->where('product_id', $request->product_id)
            ->where('variant_id', $variantId)
            ->first();

        $available = $sourceItem ? (float) $sourceItem->quantity : 0;

        if ($qty > $available) {
            return back()->withInput()->withErrors([
                'quantity' => "الكمية المطلوبة ({$qty}) تتجاوز الرصيد المتاح في المستودع المصدر ({$available}).",
            ]);
        }

        DB::transaction(function () use ($request, $variantId, $qty, $enteredQty, $sourceItem, $product, $unitConversion) {
            $ref = InventoryTransfer::generateReference();

            // 1. Create transfer record
            $transfer = InventoryTransfer::create([
                'reference'         => $ref,
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id'   => $request->to_warehouse_id,
                'product_id'        => $request->product_id,
                'variant_id'        => $variantId,
                'unit_id'           => $product->unit_id,
                'unit_conversion_id' => $unitConversion?->id,
                'quantity'          => $enteredQty,
                'notes'             => $request->notes,
                'status'            => 'completed',
                'completed_at'      => now(),
                'created_by'        => Auth::guard('admin')->id(),
            ]);

            // 2. Deduct from source warehouse
            $sourceItem->quantity = max(0, $sourceItem->quantity - $qty);
            $sourceItem->save();

            // 3. Add to destination warehouse (upsert)
            $destItem = InventoryItem::firstOrCreate([
                'warehouse_id' => $request->to_warehouse_id,
                'product_id'   => $request->product_id,
                'variant_id'   => $variantId,
            ], ['quantity' => 0]);
            $destItem->quantity = $destItem->quantity + $qty;
            $destItem->save();

            $transNote = "تحويل مخزون #{$ref}" . ($request->notes ? " — {$request->notes}" : '');
            $adminId   = Auth::guard('admin')->id();

            // 4. Transaction: out from source
            InventoryTransaction::create([
                'warehouse_id' => $request->from_warehouse_id,
                'product_id'   => $request->product_id,
                'variant_id'   => $variantId,
                'type'         => 'out',
                'quantity'     => $qty,
                'unit_cost'    => 0,
                'notes'        => $transNote,
                'created_by'   => $adminId,
            ]);

            // 5. Transaction: in to destination
            InventoryTransaction::create([
                'warehouse_id' => $request->to_warehouse_id,
                'product_id'   => $request->product_id,
                'variant_id'   => $variantId,
                'type'         => 'in',
                'quantity'     => $qty,
                'unit_cost'    => 0,
                'notes'        => $transNote,
                'created_by'   => $adminId,
            ]);

            // 6. Warehouse ledger: transfer_out from source / transfer_in to destination
            WarehouseTransaction::log([
                'warehouse_id'   => $request->from_warehouse_id,
                'product_id'     => $request->product_id,
                'variant_id'     => $variantId,
                'unit_id'        => $product->unit_id,
                'unit_conversion_id' => $unitConversion?->id,
                'type'           => 'transfer_out',
                'quantity'       => $qty,
                'unit_cost'      => 0,
                'reference_type' => InventoryTransfer::class,
                'reference_id'   => $transfer->id,
                'notes'          => $transNote,
                'created_by'     => $adminId,
            ]);

            WarehouseTransaction::log([
                'warehouse_id'   => $request->to_warehouse_id,
                'product_id'     => $request->product_id,
                'variant_id'     => $variantId,
                'unit_id'        => $product->unit_id,
                'unit_conversion_id' => $unitConversion?->id,
                'type'           => 'transfer_in',
                'quantity'       => $qty,
                'unit_cost'      => 0,
                'reference_type' => InventoryTransfer::class,
                'reference_id'   => $transfer->id,
                'notes'          => $transNote,
                'created_by'     => $adminId,
            ]);
        });

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تنفيذ التحويل بنجاح.');
    }

    // -------------------------------------------------------
    // Show
    // -------------------------------------------------------
    public function show($id)
    {
        $transfer = InventoryTransfer::with(['fromWarehouse', 'toWarehouse', 'product', 'variant', 'unit', 'unitConversion'])
            ->findOrFail($id);

        return view('finance.inventory.transfers.show', compact('transfer'));
    }

    // -------------------------------------------------------
    // Cancel (pending transfers only)
    // -------------------------------------------------------
    public function cancel($id)
    {
        $transfer = InventoryTransfer::findOrFail($id);

        if ($transfer->status !== 'pending') {
            return back()->withErrors(['error' => 'لا يمكن إلغاء تحويل غير معلق.']);
        }

        $transfer->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'تم إلغاء التحويل.');
    }

    // -------------------------------------------------------
    // AJAX: get unit conversions for a product/variant
    // -------------------------------------------------------
    public function ajaxUnitConversions(Request $request)
    {
        $productId = (int) $request->product_id;
        $variantId = $request->filled('variant_id') ? (int) $request->variant_id : null;

        $conversions = UnitConversion::with(['baseUnit', 'targetUnit'])
            ->where('product_id', $productId)
            ->when(
                $variantId,
                fn($q) => $q->where(fn($q2) => $q2->where('variant_id', $variantId)->orWhereNull('variant_id')),
                fn($q) => $q->whereNull('variant_id')
            )
            ->get();

        return response()->json([
            'results' => $conversions->map(fn($c) => [
                'id'              => $c->id,
                'text'            => ($c->targetUnit->name ?? '—') . ' (× ' . rtrim(rtrim((string) $c->conversion_rate, '0'), '.') . ' ' . ($c->baseUnit->name ?? '—') . ')',
                'conversion_rate' => (float) $c->conversion_rate,
                'is_default'      => (bool) $c->is_default,
                'allow_fractions' => (bool) $c->allow_fractions,
                'decimal_places'  => (int) $c->decimal_places,
            ]),
        ]);
    }

    // -------------------------------------------------------
    // Export
    // -------------------------------------------------------
    public function export()
    {
        $data = InventoryTransfer::with(['fromWarehouse', 'toWarehouse', 'product', 'variant', 'unit'])
            ->orderByDesc('id')
            ->get()
            ->map(fn($r) => [
                'المرجع'           => $r->reference,
                'من المستودع'       => $r->fromWarehouse->name ?? '—',
                'إلى المستودع'      => $r->toWarehouse->name  ?? '—',
                'المنتج'           => $r->product->name       ?? '—',
                'النوع'            => $r->variant
                    ? (is_array($r->variant->attributes ?? []) ? implode('/', array_values($r->variant->attributes)) : $r->variant->sku)
                    : '—',
                'الكمية'           => number_format((float) $r->quantity, 3),
                'الوحدة'           => $r->unit->name ?? '—',
                'الحالة'           => match ($r->status) { 'completed' => 'مكتمل', 'cancelled' => 'ملغي', default => 'معلق' },
                'التاريخ'          => $r->created_at->format('Y-m-d H:i'),
                'ملاحظات'          => $r->notes ?? '',
            ]);

        return (new FastExcel($data))->download('inventory_transfers.xlsx');
    }
}
