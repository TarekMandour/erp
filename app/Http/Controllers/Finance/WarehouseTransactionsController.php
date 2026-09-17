<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\WarehouseTransaction;
use App\Models\Finance\Warehouse;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\Unit;

class WarehouseTransactionsController extends Controller
{
    private string $route = 'finance.warehouse-transactions';

    // -------------------------------------------------------
    // Index
    // -------------------------------------------------------
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = WarehouseTransaction::with(['warehouse', 'product', 'variant', 'unit']);

            if ($request->filled('fwarehouse')) {
                $query->where('warehouse_id', $request->fwarehouse);
            }
            if ($request->filled('fproduct')) {
                $query->where('product_id', $request->fproduct);
            }
            if ($request->filled('ftype')) {
                $query->where('type', $request->ftype);
            }
            if ($request->filled('fdate_from')) {
                $query->whereDate('created_at', '>=', $request->fdate_from);
            }
            if ($request->filled('fdate_to')) {
                $query->whereDate('created_at', '<=', $request->fdate_to);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('warehouse_name', fn($row) => e($row->warehouse->name ?? '—'))
                ->addColumn('product_name', fn($row) => e($row->product->name ?? '—'))
                ->addColumn('variant_info', function ($row) {
                    if (!$row->variant) return '—';
                    $attrs = $row->variant->attributes ?? [];
                    return is_array($attrs) && count($attrs)
                        ? implode(' / ', array_values($attrs))
                        : e($row->variant->sku);
                })
                ->addColumn('type_badge', fn($row) =>
                    '<span class="badge ' . $row->type_badge . '">' . $row->type_name . '</span>')
                ->addColumn('qty_display', fn($row) => number_format((float) $row->quantity, 3))
                ->addColumn('unit_name', fn($row) => e($row->unit->name ?? '—'))
                ->addColumn('balance_display', fn($row) => number_format((float) $row->balance_after, 3))
                ->addColumn('reference_display', function ($row) {
                    if (!$row->reference_type) return '—';
                    $label = class_basename($row->reference_type);
                    return $label . ' #' . $row->reference_id;
                })
                ->addColumn('date_display', fn($row) => $row->created_at->format('Y-m-d H:i'))
                ->addColumn('action', function ($row) {
                    $edit   = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-light-primary me-1" title="تعديل"><i class="bi bi-pencil fs-5"></i></a>';
                    $delete = '<form method="POST" action="' . route($this->route . '.delete') . '" class="d-inline">'
                        . csrf_field()
                        . '<input type="hidden" name="id" value="' . $row->id . '">'
                        . '<button type="submit" class="btn btn-xs btn-icon btn-light-danger" title="حذف" onclick="return confirm(\'حذف هذه الحركة؟ سيتم عكس أثرها على رصيد المخزون\')"><i class="bi bi-trash fs-5"></i></button></form>';
                    return $edit . $delete;
                })
                ->rawColumns(['type_badge', 'action'])
                ->make(true);
        }

        $warehouses = Warehouse::orderBy('name')->get();

        return view('finance.warehouse-transactions.index', compact('warehouses'));
    }

    // -------------------------------------------------------
    // Create
    // -------------------------------------------------------
    public function create()
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $products   = Product::orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();
        $transaction = null;

        return view('finance.warehouse-transactions.create', compact('warehouses', 'products', 'units', 'transaction'));
    }

    // -------------------------------------------------------
    // Store (manual adjustment entry)
    // -------------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id'   => 'required|exists:products,id',
            'variant_id'   => 'nullable|exists:product_variants,id',
            'unit_id'      => 'nullable|exists:units,id',
            'type'         => 'required|in:in,out,adjustment',
            'quantity'     => 'required|numeric|min:0.001',
            'unit_cost'    => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request) {
            WarehouseTransaction::record([
                'warehouse_id' => $request->warehouse_id,
                'product_id'   => $request->product_id,
                'variant_id'   => $request->variant_id,
                'unit_id'      => $request->unit_id,
                'type'         => $request->type,
                'quantity'     => $request->quantity,
                'unit_cost'    => $request->unit_cost ?? 0,
                'notes'        => $request->notes,
                'created_by'   => Auth::guard('admin')->id(),
            ]);
        });

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تسجيل الحركة المخزنية بنجاح.');
    }

    // -------------------------------------------------------
    // Edit (notes only — quantity/type changes require a reversal entry)
    // -------------------------------------------------------
    public function edit($id)
    {
        $transaction = WarehouseTransaction::with(['warehouse', 'product', 'variant', 'unit'])->findOrFail($id);
        $warehouses  = Warehouse::orderBy('name')->get();
        $products    = Product::orderBy('name')->get();
        $units       = Unit::orderBy('name')->get();

        return view('finance.warehouse-transactions.edit', compact('transaction', 'warehouses', 'products', 'units'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id'    => 'required|exists:warehouse_transactions,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $transaction = WarehouseTransaction::findOrFail($request->id);
        $transaction->update(['notes' => $request->notes]);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تحديث الحركة المخزنية بنجاح.');
    }

    // -------------------------------------------------------
    // Delete (reverses the stock effect before removing)
    // -------------------------------------------------------
    public function destroy(Request $request)
    {
        $transaction = WarehouseTransaction::findOrFail($request->id);

        DB::transaction(function () use ($transaction) {
            $isIn  = in_array($transaction->type, ['in', 'transfer_in'], true);
            $delta = $isIn ? -$transaction->quantity : $transaction->quantity;

            $inventory = \App\Models\Finance\InventoryItem::where([
                'warehouse_id' => $transaction->warehouse_id,
                'product_id'   => $transaction->product_id,
                'variant_id'   => $transaction->variant_id,
            ])->first();

            if ($inventory) {
                $inventory->quantity = max(0, (float) $inventory->quantity + $delta);
                $inventory->save();
            }

            $transaction->delete();
        });

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم حذف الحركة وعكس أثرها على المخزون بنجاح.');
    }

    // -------------------------------------------------------
    // Ajax: variants for a product
    // -------------------------------------------------------
    public function getVariants(Request $request)
    {
        $variants = ProductVariant::where('product_id', $request->product_id)->get(['id', 'sku', 'attributes']);

        return response()->json($variants);
    }
}
