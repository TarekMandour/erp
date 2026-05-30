<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\Coupon;

class CouponsController extends Controller
{
    private $route = 'finance.coupons';

    // -------------------------------------------------------
    // Index
    // -------------------------------------------------------
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Coupon::query();

            if ($request->filled('ftype')) {
                $query->where('type', $request->ftype);
            }
            if ($request->filled('fstatus')) {
                $query->where('is_active', $request->fstatus === '1');
            }
            if ($request->filled('search')) {
                $query->where('code', 'like', '%' . $request->search . '%');
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('type_badge', function ($row) {
                    return $row->type === 'percentage'
                        ? '<span class="badge badge-light-info">نسبة مئوية</span>'
                        : '<span class="badge badge-light-warning">مبلغ ثابت</span>';
                })
                ->addColumn('value_display', function ($row) {
                    return $row->type === 'percentage'
                        ? number_format((float)$row->value, 2) . '%'
                        : number_format((float)$row->value, 2);
                })
                ->addColumn('usage_display', function ($row) {
                    $limit = $row->usage_limit ?? '∞';
                    return $row->used_count . ' / ' . $limit;
                })
                ->addColumn('dates_display', function ($row) {
                    return $row->start_date->format('Y-m-d') . ' → ' . $row->end_date->format('Y-m-d');
                })
                ->addColumn('status_badge', function ($row) {
                    $expired  = $row->end_date->isPast();
                    $maxed    = $row->usage_limit !== null && $row->used_count >= $row->usage_limit;
                    if (!$row->is_active) {
                        return '<span class="badge badge-light-danger">معطّل</span>';
                    }
                    if ($expired) {
                        return '<span class="badge badge-light-secondary">منتهي الصلاحية</span>';
                    }
                    if ($maxed) {
                        return '<span class="badge badge-light-warning">استُنفد الحد</span>';
                    }
                    return '<span class="badge badge-light-success">مفعّل</span>';
                })
                ->addColumn('action', function ($row) {
                    $edit   = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-light-primary me-1" title="تعديل"><i class="bi bi-pencil fs-5"></i></a>';
                    $delete = '<form method="POST" action="' . route($this->route . '.delete') . '" class="d-inline">'
                        . csrf_field()
                        . '<input type="hidden" name="id" value="' . $row->id . '">'
                        . '<button type="submit" class="btn btn-xs btn-icon btn-light-danger" title="حذف" onclick="return confirm(\'حذف هذا الكوبون؟\')"><i class="bi bi-trash fs-5"></i></button></form>';
                    return $edit . $delete;
                })
                ->rawColumns(['type_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('finance.coupons.index');
    }

    // -------------------------------------------------------
    // Create
    // -------------------------------------------------------
    public function create()
    {
        return view('finance.coupons.create');
    }

    // -------------------------------------------------------
    // Store
    // -------------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'code'             => 'required|string|max:100|unique:coupons,code',
            'type'             => 'required|in:percentage,fixed',
            'value'            => 'required|numeric|min:0',
            'max_discount'     => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit'      => 'nullable|integer|min:1',
            'usage_per_user'   => 'nullable|integer|min:1',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
        ]);

        Coupon::create([
            'code'             => strtoupper(trim($request->code)),
            'type'             => $request->type,
            'value'            => $request->value,
            'max_discount'     => $request->max_discount ?: null,
            'min_order_amount' => $request->min_order_amount ?: null,
            'usage_limit'      => $request->usage_limit ?: null,
            'usage_per_user'   => $request->usage_per_user ?: null,
            'used_count'       => 0,
            'start_date'       => $request->start_date,
            'end_date'         => $request->end_date,
            'is_active'        => $request->has('is_active') ? 1 : 0,
        ]);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم إضافة الكوبون بنجاح.');
    }

    // -------------------------------------------------------
    // Edit
    // -------------------------------------------------------
    public function edit($id)
    {
        $data = Coupon::findOrFail($id);
        return view('finance.coupons.edit', compact('data'));
    }

    // -------------------------------------------------------
    // Update
    // -------------------------------------------------------
    public function update(Request $request)
    {
        $request->validate([
            'id'               => 'required|exists:coupons,id',
            'code'             => 'required|string|max:100|unique:coupons,code,' . $request->id,
            'type'             => 'required|in:percentage,fixed',
            'value'            => 'required|numeric|min:0',
            'max_discount'     => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit'      => 'nullable|integer|min:1',
            'usage_per_user'   => 'nullable|integer|min:1',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
        ]);

        $record = Coupon::findOrFail($request->id);
        $record->update([
            'code'             => strtoupper(trim($request->code)),
            'type'             => $request->type,
            'value'            => $request->value,
            'max_discount'     => $request->max_discount ?: null,
            'min_order_amount' => $request->min_order_amount ?: null,
            'usage_limit'      => $request->usage_limit ?: null,
            'usage_per_user'   => $request->usage_per_user ?: null,
            'start_date'       => $request->start_date,
            'end_date'         => $request->end_date,
            'is_active'        => $request->has('is_active') ? 1 : 0,
        ]);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تحديث الكوبون بنجاح.');
    }

    // -------------------------------------------------------
    // Delete
    // -------------------------------------------------------
    public function destroy(Request $request)
    {
        Coupon::findOrFail($request->id)->delete();
        return back()->with('success', 'تم حذف الكوبون بنجاح.');
    }

    // -------------------------------------------------------
    // Generate random code (AJAX)
    // -------------------------------------------------------
    public function generateCode()
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Coupon::where('code', $code)->exists());

        return response()->json(['code' => $code]);
    }
}
