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
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\UnitConversion;
use App\Models\Finance\Warehouse;

class InventoryController extends Controller
{
    private $route = 'finance.inventory';

    // -------------------------------------------------------
    // Current Stock Index
    // -------------------------------------------------------
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = InventoryItem::with(['warehouse', 'product.unit', 'variant'])
                ->select('inventory_items.*');

            if ($request->filled('fwarehouse')) {
                $query->where('inventory_items.warehouse_id', $request->fwarehouse);
            }

            if ($request->filled('fproduct')) {
                $query->where('inventory_items.product_id', $request->fproduct);
            }

            if ($request->filled('fstock')) {
                if ($request->fstock === 'low') {
                    // low stock: quantity > 0 but below alert_quantity
                    $query->join('products', 'products.id', '=', 'inventory_items.product_id')
                        ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory_items.variant_id')
                        ->whereRaw('inventory_items.quantity > 0')
                        ->whereRaw('inventory_items.quantity <= COALESCE(product_variants.alert_quantity, products.alert_quantity, 0)')
                        ->whereRaw('COALESCE(product_variants.alert_quantity, products.alert_quantity, 0) > 0');
                } elseif ($request->fstock === 'out') {
                    $query->where('inventory_items.quantity', '<=', 0);
                } elseif ($request->fstock === 'ok') {
                    $query->join('products', 'products.id', '=', 'inventory_items.product_id')
                        ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory_items.variant_id')
                        ->where('inventory_items.quantity', '>', 0)
                        ->where(function ($q) {
                            $q->whereRaw('inventory_items.quantity > COALESCE(product_variants.alert_quantity, products.alert_quantity, 0)')
                              ->orWhereRaw('COALESCE(product_variants.alert_quantity, products.alert_quantity, 0) = 0');
                        });
                }
            }

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('sku', 'LIKE', "%{$search}%");
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn($row) =>
                    '<div class="form-check form-check-sm form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                    </div>'
                )
                ->addColumn('product_name', function ($row) {
                    $name = $row->product->name ?? '—';
                    $sku  = $row->variant ? $row->variant->sku : ($row->product->sku ?? '');
                    return '<div><span class="fw-bold">' . e($name) . '</span><br><small class="text-muted">' . e($sku) . '</small></div>';
                })
                ->addColumn('variant_info', function ($row) {
                    if (!$row->variant) {
                        return '<span class="badge badge-light-secondary">بدون نوع</span>';
                    }
                    $attrs = $row->variant->attributes ?? [];
                    if (is_array($attrs) && count($attrs)) {
                        $parts = array_map(fn($v) => e($v), array_values($attrs));
                        return '<span class="badge badge-light-info">' . implode(' / ', $parts) . '</span>';
                    }
                    return '<span class="badge badge-light-info">' . e($row->variant->sku) . '</span>';
                })
                ->addColumn('warehouse_name', fn($row) => e($row->warehouse->name ?? '—'))
                ->addColumn('quantity_display', function ($row) {
                    $qty   = (float) $row->quantity;
                    $unit  = $row->product->unit->name ?? '';
                    $alert = $row->variant
                        ? (int) ($row->variant->alert_quantity ?? 0)
                        : (int) ($row->product->alert_quantity ?? 0);

                    if ($qty <= 0) {
                        $badge = '<span class="badge badge-light-danger">نفذ</span>';
                    } elseif ($alert > 0 && $qty <= $alert) {
                        $badge = '<span class="badge badge-light-warning">منخفض</span>';
                    } else {
                        $badge = '<span class="badge badge-light-success">متاح</span>';
                    }

                    return '<span class="fw-bold fs-6">' . number_format($qty, 2) . '</span>'
                         . ($unit ? ' <small class="text-muted">' . e($unit) . '</small>' : '')
                         . ' ' . $badge;
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route($this->route . '.product-report', $row->product_id) . '" class="btn btn-xs btn-icon btn-warning me-1" title="تقرير المنتج"><i class="bi bi-bar-chart fs-5"></i></a>'
                         . '<a href="' . route($this->route . '.adjust-item', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-1" title="تعديل المخزون"><i class="bi bi-plus-slash-minus fs-5"></i></a>'
                         . '<a href="' . route($this->route . '.history', $row->id) . '" class="btn btn-xs btn-icon btn-info" title="سجل الحركات"><i class="bi bi-clock-history fs-5"></i></a>';
                })
                ->rawColumns(['checkbox', 'product_name', 'variant_info', 'quantity_display', 'action'])
                ->make(true);
        }

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();

        return view('finance.inventory.index', compact('warehouses', 'products'));
    }

    // -------------------------------------------------------
    // Stock Adjustment Form
    // -------------------------------------------------------
    public function adjust($id = null)
    {
        $item       = $id ? InventoryItem::with(['product', 'variant', 'warehouse'])->findOrFail($id) : null;
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('finance.inventory.adjust', compact('item', 'warehouses'));
    }

    public function storeAdjust(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id'   => 'required|exists:products,id',
            'variant_id'   => 'nullable|exists:product_variants,id',
            'type'         => 'required|in:in,out,adjustment',
            'quantity'     => 'required|numeric|min:0.001',
            'unit_cost'    => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:500',
        ]);

        $product   = Product::findOrFail($request->product_id);
        $variantId = ($product->has_variants && $request->filled('variant_id')) ? $request->variant_id : null;

        DB::transaction(function () use ($request, $variantId) {
            // Upsert inventory_items
            $item = InventoryItem::firstOrCreate([
                'warehouse_id' => $request->warehouse_id,
                'product_id'   => $request->product_id,
                'variant_id'   => $variantId,
            ]);

            $qty = (float) $request->quantity;

            if ($request->type === 'in') {
                $item->quantity = $item->quantity + $qty;
            } elseif ($request->type === 'out') {
                $item->quantity = max(0, $item->quantity - $qty);
            } else {
                // adjustment: set absolute quantity
                $item->quantity = $qty;
            }
            $item->save();

            // Record transaction
            InventoryTransaction::create([
                'warehouse_id' => $request->warehouse_id,
                'product_id'   => $request->product_id,
                'variant_id'   => $variantId,
                'type'         => $request->type,
                'quantity'     => $qty,
                'unit_cost'    => $request->unit_cost ?? 0,
                'notes'        => $request->notes,
                'created_by'   => Auth::guard('admin')->id(),
            ]);
        });

        return redirect()->route($this->route . '.index')->with('success', 'تم تحديث المخزون بنجاح');
    }

    // -------------------------------------------------------
    // Transaction History (for a single inventory item)
    // -------------------------------------------------------
    public function history(Request $request, $id)
    {
        $item = InventoryItem::with(['product', 'variant', 'warehouse'])->findOrFail($id);

        if ($request->ajax()) {
            $query = InventoryTransaction::where('warehouse_id', $item->warehouse_id)
                ->where('product_id', $item->product_id)
                ->where('variant_id', $item->variant_id)
                ->orderByDesc('created_at');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('type_badge', function ($row) {
                    return match ($row->type) {
                        'in'         => '<span class="badge badge-light-success">وارد</span>',
                        'out'        => '<span class="badge badge-light-danger">صادر</span>',
                        'adjustment' => '<span class="badge badge-light-warning">تعديل</span>',
                        default      => e($row->type),
                    };
                })
                ->addColumn('qty_display', fn($row) => number_format((float)$row->quantity, 3))
                ->addColumn('cost_display', fn($row) => number_format((float)$row->unit_cost, 2))
                ->addColumn('date_display', fn($row) => $row->created_at->format('Y-m-d H:i'))
                ->rawColumns(['type_badge'])
                ->make(true);
        }

        return view('finance.inventory.history', compact('item'));
    }

    // -------------------------------------------------------
    // All Transactions Log
    // -------------------------------------------------------
    public function transactions(Request $request)
    {
        if ($request->ajax()) {
            $query = InventoryTransaction::with(['warehouse', 'product', 'variant'])
                ->orderByDesc('created_at');

            if ($request->filled('fwarehouse')) {
                $query->where('warehouse_id', $request->fwarehouse);
            }
            if ($request->filled('fproduct')) {
                $query->where('product_id', $request->fproduct);
            }
            if ($request->filled('ftype')) {
                $query->where('type', $request->ftype);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('product_name', fn($row) => e($row->product->name ?? '—'))
                ->addColumn('variant_info', function ($row) {
                    if (!$row->variant) return '—';
                    $attrs = $row->variant->attributes ?? [];
                    return is_array($attrs) ? implode(' / ', array_values($attrs)) : $row->variant->sku;
                })
                ->addColumn('warehouse_name', fn($row) => e($row->warehouse->name ?? '—'))
                ->addColumn('type_badge', function ($row) {
                    return match ($row->type) {
                        'in'         => '<span class="badge badge-light-success">وارد</span>',
                        'out'        => '<span class="badge badge-light-danger">صادر</span>',
                        'adjustment' => '<span class="badge badge-light-warning">تعديل</span>',
                        default      => e($row->type),
                    };
                })
                ->addColumn('qty_display', fn($row) => number_format((float)$row->quantity, 3))
                ->addColumn('cost_display', fn($row) => number_format((float)$row->unit_cost, 2))
                ->addColumn('date_display', fn($row) => $row->created_at->format('Y-m-d H:i'))
                ->rawColumns(['type_badge'])
                ->make(true);
        }

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();

        return view('finance.inventory.transactions', compact('warehouses', 'products'));
    }

    // -------------------------------------------------------
    // AJAX: Search products (Select2)
    // -------------------------------------------------------
    public function ajaxProducts(Request $request)
    {
        $search = $request->search;

        $rows = Product::select('id', 'name', 'sku', 'has_variants')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"))
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(20);

        $results = $rows->map(fn($p) => [
            'id'           => $p->id,
            'text'         => $p->name . ' (' . $p->sku . ')',
            'has_variants' => $p->has_variants ? 1 : 0,
        ]);

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => $rows->hasMorePages()],
        ]);
    }

    // -------------------------------------------------------
    // AJAX: Get variants for a product
    // -------------------------------------------------------
    public function getVariants(Request $request)
    {
        $product = Product::with('variants')->findOrFail($request->product_id);

        if (!$product->has_variants) {
            return response()->json(['has_variants' => false, 'variants' => []]);
        }

        $variants = $product->variants->where('is_active', true)->map(function ($v) {
            $attrs = $v->attributes ?? [];
            $label = is_array($attrs) && count($attrs)
                ? implode(' / ', array_values($attrs))
                : $v->sku;
            return ['id' => $v->id, 'label' => $label, 'sku' => $v->sku];
        })->values();

        return response()->json(['has_variants' => true, 'variants' => $variants]);
    }

    // -------------------------------------------------------
    // Export
    // -------------------------------------------------------
    public function export(Request $request)
    {
        $query = InventoryItem::with(['warehouse', 'product', 'variant'])->orderBy('product_id');

        if ($request->filled('fwarehouse')) {
            $query->where('warehouse_id', $request->fwarehouse);
        }

        $rows = $query->get()->map(function ($item) {
            $variantLabel = '—';
            if ($item->variant) {
                $attrs = $item->variant->attributes ?? [];
                $variantLabel = is_array($attrs) && count($attrs)
                    ? implode(' / ', array_values($attrs))
                    : $item->variant->sku;
            }
            return [
                'المنتج'     => $item->product->name ?? '',
                'SKU'        => $item->variant ? $item->variant->sku : ($item->product->sku ?? ''),
                'النوع'      => $variantLabel,
                'المستودع'   => $item->warehouse->name ?? '',
                'الكمية'     => (float) $item->quantity,
            ];
        });

        return (new FastExcel($rows))->download('inventory.csv');
    }

    // -------------------------------------------------------
    // AJAX: Get available stock for transfer form
    // -------------------------------------------------------
    public function getStock(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id'   => 'required|exists:products,id',
            'variant_id'   => 'nullable|exists:product_variants,id',
        ]);

        $item = InventoryItem::where('warehouse_id', $request->warehouse_id)
            ->where('product_id', $request->product_id)
            ->where('variant_id', $request->filled('variant_id') ? $request->variant_id : null)
            ->first();

        return response()->json([
            'quantity' => $item ? number_format((float) $item->quantity, 3) : '0.000',
        ]);
    }

    // -------------------------------------------------------
    // Product Stock Report
    // -------------------------------------------------------
    public function productReport(Request $request, $id)
    {
        $product = Product::with(['category', 'brand', 'unit', 'variants', 'unitConversions.variant'])
            ->findOrFail($id);

        if ($request->ajax()) {
            $query = InventoryTransaction::with(['warehouse', 'variant'])
                ->where('product_id', $id)
                ->orderByDesc('created_at');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('type_badge', function ($row) {
                    return match ($row->type) {
                        'in'         => '<span class="badge badge-light-success">وارد</span>',
                        'out'        => '<span class="badge badge-light-danger">صادر</span>',
                        'adjustment' => '<span class="badge badge-light-warning">تعديل</span>',
                        'transfer_in'  => '<span class="badge badge-light-primary">تحويل وارد</span>',
                        'transfer_out' => '<span class="badge badge-light-secondary">تحويل صادر</span>',
                        default      => e($row->type),
                    };
                })
                ->addColumn('warehouse_name', fn($row) => e($row->warehouse->name ?? '—'))
                ->addColumn('variant_info', function ($row) {
                    if (!$row->variant) return '—';
                    $attrs = $row->variant->attributes ?? [];
                    return is_array($attrs) && count($attrs)
                        ? implode(' / ', array_values($attrs))
                        : e($row->variant->sku);
                })
                ->addColumn('qty_display', fn($row) => number_format((float)$row->quantity, 3))
                ->addColumn('cost_display', fn($row) => number_format((float)$row->unit_cost, 2))
                ->addColumn('date_display', fn($row) => $row->created_at->format('Y-m-d H:i'))
                ->rawColumns(['type_badge'])
                ->make(true);
        }

        $stockRows  = InventoryItem::with(['warehouse', 'variant'])
            ->where('product_id', $id)
            ->get();
        $totalStock = $stockRows->sum(fn($r) => (float)$r->quantity);

        return view('finance.inventory.product-report', compact('product', 'stockRows', 'totalStock'));
    }
}
