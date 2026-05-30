<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\ProductVariantPrice;
use App\Models\Finance\Customer;

class ProductVariantPricesController extends Controller
{
    private $route = 'finance.variant-prices';

    // -------------------------------------------------------
    // Index
    // -------------------------------------------------------
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProductVariantPrice::with(['product', 'variant', 'customer'])
                ->select('product_variant_prices.*');

            if ($request->filled('fproduct')) {
                $query->where('product_id', $request->fproduct);
            }
            if ($request->filled('fstatus')) {
                $query->where('is_active', $request->fstatus === '1');
            }
            if ($request->filled('fcustomer')) {
                $query->where(function ($q) use ($request) {
                    $q->where('customer_id', $request->fcustomer)
                      ->orWhereJsonContains('customer_ids', (int) $request->fcustomer);
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('product_name', fn($row) => e($row->product->name ?? '—'))
                ->addColumn('variant_label', function ($row) {
                    if (!$row->variant) return '<span class="text-muted fst-italic">عام (المنتج)</span>';
                    $attrs = $row->variant->attributes ?? [];
                    return is_array($attrs) && count($attrs)
                        ? implode(' / ', array_values($attrs))
                        : e($row->variant->sku);
                })
                ->addColumn('price_display', fn($row) => number_format((float)$row->price, 2))
                ->addColumn('sale_display', function ($row) {
                    if (!$row->sale_price) return '—';
                    $dates = '';
                    if ($row->sale_start || $row->sale_end) {
                        $dates = '<br><small class="text-muted">'
                            . ($row->sale_start ? $row->sale_start->format('Y-m-d') : '∞')
                            . ' → '
                            . ($row->sale_end ? $row->sale_end->format('Y-m-d') : '∞')
                            . '</small>';
                    }
                    return '<span class="text-danger fw-bold">' . number_format((float)$row->sale_price, 2) . '</span>' . $dates;
                })
                ->addColumn('customer_name', function ($row) {
                    $ids = $row->customer_ids ?? [];
                    if (empty($ids)) {
                        return $row->customer ? e($row->customer->name) : '<span class="text-muted">عام</span>';
                    }
                    $names = Customer::whereIn('id', $ids)->pluck('name')->map(fn($n) => e($n));
                    return $names->map(fn($n) => '<span class="badge badge-light-primary me-1">' . $n . '</span>')->implode('');
                })
                ->addColumn('qty_tiers', function ($row) {
                    $tiers = $row->quantity_prices ?? [];
                    if (empty($tiers)) return '—';
                    return count($tiers) . ' شريحة';
                })
                ->addColumn('valid_display', function ($row) {
                    if (!$row->valid_from && !$row->valid_to) return '—';
                    return ($row->valid_from ? $row->valid_from->format('Y-m-d') : '∞')
                        . ' → '
                        . ($row->valid_to ? $row->valid_to->format('Y-m-d') : '∞');
                })
                ->addColumn('status_badge', function ($row) {
                    return $row->is_active
                        ? '<span class="badge badge-light-success">مفعّل</span>'
                        : '<span class="badge badge-light-danger">معطّل</span>';
                })
                ->addColumn('action', function ($row) {
                    $edit   = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-light-primary me-1" title="تعديل"><i class="bi bi-pencil fs-5"></i></a>';
                    $delete = '<form method="POST" action="' . route($this->route . '.delete') . '" class="d-inline">'
                        . csrf_field()
                        . '<input type="hidden" name="id" value="' . $row->id . '">'
                        . '<button type="submit" class="btn btn-xs btn-icon btn-light-danger" title="حذف" onclick="return confirm(\'حذف هذا السعر؟\')"><i class="bi bi-trash fs-5"></i></button></form>';
                    return $edit . $delete;
                })
                ->rawColumns(['variant_label', 'sale_display', 'customer_name', 'status_badge', 'action'])
                ->make(true);
        }

        $products  = Product::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();

        return view('finance.variant-prices.index', compact('products', 'customers'));
    }

    // -------------------------------------------------------
    // Create
    // -------------------------------------------------------
    public function create()
    {
        $products  = Product::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();

        return view('finance.variant-prices.create', compact('products', 'customers'));
    }

    // -------------------------------------------------------
    // Store
    // -------------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'product_id'  => 'required|exists:products,id',
            'variant_id'  => 'nullable|exists:product_variants,id',
            'price'       => 'required|numeric|min:0',
            'sale_price'  => 'nullable|numeric|min:0',
            'sale_start'  => 'nullable|date',
            'sale_end'    => 'nullable|date|after_or_equal:sale_start',
            'customer_ids'   => 'nullable|array',
            'customer_ids.*' => 'exists:customers,id',
            'valid_from'  => 'nullable|date',
            'valid_to'    => 'nullable|date|after_or_equal:valid_from',
            'priority'    => 'integer|min:0',
        ]);

        // Build quantity_prices from submitted tiers
        $tiers = [];
        if ($request->filled('tier_qty') && $request->filled('tier_price')) {
            foreach ($request->tier_qty as $i => $minQty) {
                if ($minQty !== null && isset($request->tier_price[$i]) && $request->tier_price[$i] !== null) {
                    $tiers[] = [
                        'min_qty' => (float) $minQty,
                        'price'   => (float) $request->tier_price[$i],
                    ];
                }
            }
            usort($tiers, fn($a, $b) => $a['min_qty'] <=> $b['min_qty']);
        }

        $customerIds = array_filter((array) $request->input('customer_ids', []));
        ProductVariantPrice::create([
            'product_id'      => $request->product_id,
            'variant_id'      => $request->variant_id ?: null,
            'price'           => $request->price,
            'sale_price'      => $request->sale_price ?: null,
            'sale_start'      => $request->sale_start ?: null,
            'sale_end'        => $request->sale_end ?: null,
            'quantity_prices' => empty($tiers) ? null : $tiers,
            'customer_id'     => count($customerIds) === 1 ? $customerIds[0] : null,
            'customer_ids'    => empty($customerIds) ? null : array_values($customerIds),
            'valid_from'      => $request->valid_from ?: null,
            'valid_to'        => $request->valid_to ?: null,
            'is_active'       => $request->has('is_active') ? 1 : 0,
            'priority'        => $request->priority ?? 0,
        ]);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم إضافة قاعدة السعر بنجاح.');
    }

    // -------------------------------------------------------
    // Edit
    // -------------------------------------------------------
    public function edit($id)
    {
        $data      = ProductVariantPrice::findOrFail($id);
        $products  = Product::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();

        // Load variants for selected product
        $variants = ProductVariant::where('product_id', $data->product_id)
            ->where('is_active', true)
            ->get();

        return view('finance.variant-prices.edit', compact('data', 'products', 'customers', 'variants'));
    }

    // -------------------------------------------------------
    // Update
    // -------------------------------------------------------
    public function update(Request $request)
    {
        $request->validate([
            'id'             => 'required|exists:product_variant_prices,id',
            'product_id'     => 'required|exists:products,id',
            'variant_id'     => 'nullable|exists:product_variants,id',
            'price'          => 'required|numeric|min:0',
            'sale_price'     => 'nullable|numeric|min:0',
            'sale_start'     => 'nullable|date',
            'sale_end'       => 'nullable|date|after_or_equal:sale_start',
            'customer_ids'   => 'nullable|array',
            'customer_ids.*' => 'exists:customers,id',
            'valid_from'     => 'nullable|date',
            'valid_to'       => 'nullable|date|after_or_equal:valid_from',
            'priority'       => 'integer|min:0',
        ]);

        $tiers = [];
        if ($request->filled('tier_qty') && $request->filled('tier_price')) {
            foreach ($request->tier_qty as $i => $minQty) {
                if ($minQty !== null && isset($request->tier_price[$i]) && $request->tier_price[$i] !== null) {
                    $tiers[] = [
                        'min_qty' => (float) $minQty,
                        'price'   => (float) $request->tier_price[$i],
                    ];
                }
            }
            usort($tiers, fn($a, $b) => $a['min_qty'] <=> $b['min_qty']);
        }

        $customerIds = array_filter((array) $request->input('customer_ids', []));
        $record = ProductVariantPrice::findOrFail($request->id);
        $record->update([
            'product_id'      => $request->product_id,
            'variant_id'      => $request->variant_id ?: null,
            'price'           => $request->price,
            'sale_price'      => $request->sale_price ?: null,
            'sale_start'      => $request->sale_start ?: null,
            'sale_end'        => $request->sale_end ?: null,
            'quantity_prices' => empty($tiers) ? null : $tiers,
            'customer_id'     => count($customerIds) === 1 ? $customerIds[0] : null,
            'customer_ids'    => empty($customerIds) ? null : array_values($customerIds),
            'valid_from'      => $request->valid_from ?: null,
            'valid_to'        => $request->valid_to ?: null,
            'is_active'       => $request->has('is_active') ? 1 : 0,
            'priority'        => $request->priority ?? 0,
        ]);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تحديث قاعدة السعر بنجاح.');
    }

    // -------------------------------------------------------
    // Delete
    // -------------------------------------------------------
    public function destroy(Request $request)
    {
        ProductVariantPrice::findOrFail($request->id)->delete();
        return back()->with('success', 'تم الحذف بنجاح.');
    }

    // -------------------------------------------------------
    // AJAX: Get variants for a product
    // -------------------------------------------------------
    public function getVariants(Request $request)
    {
        $variants = ProductVariant::where('product_id', $request->product_id)
            ->where('is_active', true)
            ->get()
            ->map(function ($v) {
                $attrs = $v->attributes ?? [];
                $label = is_array($attrs) && count($attrs)
                    ? implode(' / ', array_values($attrs))
                    : $v->sku;
                return ['id' => $v->id, 'label' => $label . ' (' . $v->sku . ')', 'selling_price' => $v->selling_price];
            });

        return response()->json($variants);
    }
}
