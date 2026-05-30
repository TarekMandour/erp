<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\Offer;
use App\Models\Finance\Product;
use App\Models\Finance\Category;
use App\Http\Requests\Finance\OfferRequest;

class OffersController extends Controller
{
    private string $route = 'finance.offers';

    // -------------------------------------------------------
    // Index
    // -------------------------------------------------------
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Offer::query();

            if ($request->filled('ftype')) {
                $query->where('type', $request->ftype);
            }
            if ($request->filled('fstatus')) {
                $query->where('is_active', $request->fstatus === '1');
            }
            if ($request->filled('fapplies')) {
                $query->where('applies_to', $request->fapplies);
            }
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('type_badge', fn($row) =>
                    '<span class="badge ' . $row->type_badge . '">' . $row->type_label . '</span>')
                ->addColumn('value_display', function ($row) {
                    return match ($row->type) {
                        'percentage', 'first_order'       => number_format((float) $row->value, 2) . '%',
                        'fixed', 'product_price_discount' => number_format((float) $row->value, 2) . ' ر.س',
                        'bundle'                          => number_format((float) $row->value, 2) . ' ر.س / حزمة',
                        'buy_x_get_y'                     => 'اشتري ' . $row->buy_quantity . ' → ' . $row->get_quantity . ' مجاناً',
                        'buy_x_get_discount'              => 'اشتري ' . $row->buy_quantity . ' → خصم',
                        'buy_amount_get_discount'         => 'من ' . number_format((float) $row->min_amount, 2) . ' ر.س',
                        'tiered'                          => 'متدرج (' . count((array) $row->tier_thresholds) . ' شرائح)',
                        'flash'                           => $row->flash_quantity ? $row->flash_quantity . ' وحدة' : '—',
                        'free_shipping'                   => $row->min_amount ? 'من ' . number_format((float) $row->min_amount, 2) . ' ر.س' : 'بلا حد أدنى',
                        default                           => '—',
                    };
                })
                ->addColumn('applies_to_badge', fn($row) =>
                    '<span class="badge ' . $row->applies_to_badge . '">' . $row->applies_to_label . '</span>')
                ->addColumn('usage_display', function ($row) {
                    $max = $row->max_uses ?? '∞';
                    return $row->used_count . ' / ' . $max;
                })
                ->addColumn('stackable_icon', fn($row) =>
                    $row->is_stackable
                        ? '<i class="bi bi-layers-fill text-success fs-5" title="قابل للتكديس"></i>'
                        : '<i class="bi bi-layers text-muted fs-5" title="غير قابل"></i>')
                ->addColumn('dates_display', fn($row) =>
                    $row->start_date->format('Y-m-d') . ' → ' . $row->end_date->format('Y-m-d'))
                ->addColumn('status_badge', function ($row) {
                    if (!$row->is_active) {
                        return '<span class="badge badge-light-danger">معطّل</span>';
                    }
                    if ($row->isExpired()) {
                        return '<span class="badge badge-light-secondary">منتهي</span>';
                    }
                    if ($row->isMaxedOut()) {
                        return '<span class="badge badge-light-warning">استُنفد</span>';
                    }
                    return '<span class="badge badge-light-success">نشط</span>';
                })
                ->addColumn('action', function ($row) {
                    $edit   = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-light-primary me-1" title="تعديل"><i class="bi bi-pencil fs-5"></i></a>';
                    $delete = '<form method="POST" action="' . route($this->route . '.delete') . '" class="d-inline">'
                        . csrf_field()
                        . '<input type="hidden" name="id" value="' . $row->id . '">'
                        . '<button type="submit" class="btn btn-xs btn-icon btn-light-danger" title="حذف" onclick="return confirm(\'حذف هذا العرض؟\')"><i class="bi bi-trash fs-5"></i></button></form>';
                    return $edit . $delete;
                })
                ->rawColumns(['type_badge', 'applies_to_badge', 'stackable_icon', 'status_badge', 'action'])
                ->make(true);
        }

        return view('finance.offers.index');
    }

    // -------------------------------------------------------
    // Create
    // -------------------------------------------------------
    public function create()
    {
        return view('finance.offers.create');
    }

    // -------------------------------------------------------
    // Store
    // -------------------------------------------------------
    public function store(OfferRequest $request)
    {
        $offer = Offer::create($this->mapData($request));
        $this->syncItems($offer, $request);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم إضافة العرض بنجاح.');
    }

    // -------------------------------------------------------
    // Edit
    // -------------------------------------------------------
    public function edit($id)
    {
        $data = Offer::with(['products:id,name', 'categories:id,name'])->findOrFail($id);
        return view('finance.offers.edit', compact('data'));
    }

    // -------------------------------------------------------
    // Update
    // -------------------------------------------------------
    public function update(OfferRequest $request)
    {
        $offer = Offer::findOrFail($request->id);
        $offer->update($this->mapData($request));
        $this->syncItems($offer, $request);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تحديث العرض بنجاح.');
    }

    // -------------------------------------------------------
    // Delete
    // -------------------------------------------------------
    public function destroy(Request $request)
    {
        Offer::findOrFail($request->id)->delete();
        return back()->with('success', 'تم حذف العرض بنجاح.');
    }

    // -------------------------------------------------------
    // AJAX: search products or categories for Select2
    // -------------------------------------------------------
    public function ajaxItems(Request $request)
    {
        $type   = $request->type; // 'product' or 'category'
        $search = $request->search;

        if ($type === 'category') {
            $rows = Category::select('id', 'name')
                ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
                ->paginate(20);
        } else {
            $rows = Product::select('id', 'name', 'sku')
                ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"))
                ->orderBy('name')
                ->paginate(20);
        }

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id'   => $r->id,
                'text' => $type === 'category' ? $r->name : "{$r->name} ({$r->sku})",
            ]),
            'pagination' => ['more' => $rows->hasMorePages()],
        ]);
    }

    // -------------------------------------------------------
    // Private: sync offer_items pivot rows
    // -------------------------------------------------------
    private function syncItems(Offer $offer, Request $request): void
    {
        DB::table('offer_items')->where('offer_id', $offer->id)->delete();

        $type = $request->applies_to;
        $ids  = array_filter((array) $request->input('item_ids', []));

        if (in_array($type, ['product', 'category']) && !empty($ids)) {
            $rows = array_map(fn($id) => [
                'offer_id'  => $offer->id,
                'item_type' => $type,
                'item_id'   => (int) $id,
            ], $ids);
            DB::table('offer_items')->insert($rows);
        }
    }

    // -------------------------------------------------------
    // Private: map request → data array
    // -------------------------------------------------------
    private function mapData(OfferRequest $request): array
    {
        $type  = $request->type;
        $tiers = null;

        if ($type === 'tiered' && $request->filled('tier_thresholds')) {
            $decoded = json_decode($request->tier_thresholds, true);
            $tiers   = is_array($decoded) ? $decoded : null;
        }

        return [
            'name'                => $request->name,
            'description'         => $request->description ?: null,
            'type'                => $type,
            'applies_to'          => $request->applies_to,
            'start_date'          => $request->start_date,
            'end_date'            => $request->end_date,
            'is_active'           => $request->is_active,
            'is_stackable'        => $request->is_stackable,
            'priority'            => $request->priority ?? 0,
            'max_uses'            => $request->max_uses ?: null,
            'value'               => $request->value ?: null,
            'buy_quantity'        => $request->buy_quantity ?: null,
            'get_quantity'        => $request->get_quantity ?: null,
            'min_amount'          => $request->min_amount ?: null,
            'discount_percentage' => $request->discount_percentage ?: null,
            'discount_amount'     => $request->discount_amount ?: null,
            'flash_quantity'      => $request->flash_quantity ?: null,
            'tier_thresholds'     => $tiers,
        ];
    }
}
