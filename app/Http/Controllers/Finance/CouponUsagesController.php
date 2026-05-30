<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\CouponUsage;
use App\Models\Finance\Coupon;
use App\Models\Finance\Customer;

class CouponUsagesController extends Controller
{
    private $route = 'finance.coupon_usages';

    // -------------------------------------------------------
    // Index
    // -------------------------------------------------------
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = CouponUsage::with(['coupon', 'customer']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('coupon', fn($q) => $q->where('code', 'like', '%' . $search . '%'))
                      ->orWhereHas('customer', fn($q) => $q->where('name', 'like', '%' . $search . '%'))
                      ->orWhere('order_id', $search);
            }

            if ($request->filled('fcoupon')) {
                $query->where('coupon_id', $request->fcoupon);
            }
            if ($request->filled('fcustomer')) {
                $query->where('customer_id', $request->fcustomer);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('coupon_code', fn($row) => $row->coupon->code ?? '—')
                ->addColumn('customer_name', fn($row) => $row->customer->name ?? '—')
                ->addColumn('discount_display', fn($row) => number_format((float) $row->discount_amount, 2))
                ->addColumn('used_at_display', fn($row) => $row->used_at ? $row->used_at->format('Y-m-d H:i') : '—')
                ->addColumn('action', function ($row) {
                    return '<form method="POST" action="' . route($this->route . '.delete') . '" class="d-inline">'
                        . csrf_field()
                        . '<input type="hidden" name="id" value="' . $row->id . '">'
                        . '<button type="submit" class="btn btn-xs btn-icon btn-light-danger" title="حذف" onclick="return confirm(\'حذف هذا السجل؟\')"><i class="bi bi-trash fs-5"></i></button></form>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $coupons   = Coupon::orderBy('code')->get(['id', 'code']);
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        return view('finance.coupon_usages.index', compact('coupons', 'customers'));
    }

    // -------------------------------------------------------
    // Create
    // -------------------------------------------------------
    public function create()
    {
        $coupons   = Coupon::where('is_active', true)->orderBy('code')->get(['id', 'code']);
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        return view('finance.coupon_usages.create', compact('coupons', 'customers'));
    }

    // -------------------------------------------------------
    // Store
    // -------------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'coupon_id'       => 'required|exists:coupons,id',
            'customer_id'     => 'required|exists:customers,id',
            'order_id'        => 'required|integer|min:1',
            'discount_amount' => 'required|numeric|min:0',
            'used_at'         => 'required|date',
        ]);

        CouponUsage::create([
            'coupon_id'       => $request->coupon_id,
            'customer_id'     => $request->customer_id,
            'order_id'        => $request->order_id,
            'discount_amount' => $request->discount_amount,
            'used_at'         => $request->used_at,
        ]);

        // Increment used_count on the coupon
        Coupon::where('id', $request->coupon_id)->increment('used_count');

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تسجيل استخدام الكوبون بنجاح.');
    }

    // -------------------------------------------------------
    // Delete
    // -------------------------------------------------------
    public function destroy(Request $request)
    {
        CouponUsage::findOrFail($request->id)->delete();
        return back()->with('success', 'تم حذف السجل بنجاح.');
    }
}
