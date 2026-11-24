<?php

namespace App\Http\Controllers\Finance;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Finance\Customer;
use App\Models\Finance\Order;
use App\Models\Finance\SalesReturn;
use App\Services\Finance\SalesReturnCalculator;
use App\Services\Finance\SalesReturnService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

/**
 * مرتجعات المبيعات: مستند مستقل يُنشأ من طلب بيع ولا يُعدَّل (يُلغى فقط).
 */
class SalesReturnsController extends Controller
{
    private string $route = 'finance.sales_returns';

    public function __construct(
        private SalesReturnService $service,
        private SalesReturnCalculator $calculator,
    ) {
    }

    // -------------------------------------------------------
    // Index — DataTables
    // -------------------------------------------------------
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalesReturn::with(['customer:id,name,customer_code', 'order:id,order_number'])
                ->select('sales_returns.*');

            if ($request->filled('fcustomer')) {
                $query->where('customer_id', $request->fcustomer);
            }
            if ($request->filled('fstatus')) {
                $query->where('status', $request->fstatus);
            }
            if ($request->filled('fdate_from')) {
                $query->whereDate('date', '>=', $request->fdate_from);
            }
            if ($request->filled('fdate_to')) {
                $query->whereDate('date', '<=', $request->fdate_to);
            }
            if ($request->filled('search')) {
                $s = '%' . $request->search . '%';
                $query->where(fn ($q) => $q->where('return_number', 'like', $s)
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', $s)));
            }

            return DataTables::of($query->orderByDesc('id'))
                ->addIndexColumn()
                ->addColumn('order_number', fn ($row) => $row->order
                    ? '<a href="' . route('finance.orders.show', $row->order_id) . '">' . e($row->order->order_number) . '</a>'
                    : '—')
                ->addColumn('customer_name', fn ($row) =>
                    ($row->customer?->name ?? '—') . ($row->customer?->customer_code ? ' (' . $row->customer->customer_code . ')' : ''))
                ->addColumn('date_fmt', fn ($row) => $row->date?->format('Y-m-d'))
                ->addColumn('total_fmt', fn ($row) => number_format((float) $row->total, 2) . ' ر.س')
                ->addColumn('paid_fmt', fn ($row) => number_format((float) $row->paid, 2) . ' ر.س')
                ->addColumn('status_badge', fn ($row) => '<span class="badge ' . $row->status_badge . '">' . $row->status_label . '</span>')
                ->addColumn('action', fn ($row) =>
                    '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-light-success" title="عرض وطباعة"><i class="ki-duotone ki-printer fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></a>')
                ->rawColumns(['order_number', 'status_badge', 'action'])
                ->make(true);
        }

        $customers = Customer::select('id', 'name', 'customer_code')->orderBy('name')->get();

        return view('finance.sales_returns.index', compact('customers'));
    }

    // -------------------------------------------------------
    // Create — اختيار الطلب ثم الكميات
    // -------------------------------------------------------
    public function create(Request $request)
    {
        $order = null;

        if ($request->filled('order_id') || $request->filled('order_number')) {
            $order = Order::with('customer:id,name,customer_code', 'warehouse:id,name', 'offer')
                ->when($request->filled('order_id'), fn ($q) => $q->where('id', $request->order_id))
                ->when(! $request->filled('order_id'), fn ($q) => $q->where('order_number', trim($request->order_number)))
                ->first();

            if (! $order) {
                return redirect()->route($this->route . '.create')->with('error', 'لم يُعثر على طلب بهذا الرقم.');
            }
            if (! $order->is_effective) {
                return redirect()->route($this->route . '.create')
                    ->with('error', 'لا يمكن إنشاء مرتجع للطلب ' . $order->order_number . ' — حالته: ' . $order->status_label . '.');
            }
        }

        $ctx = $order ? $this->calculator->context($order) : null;
        $hasOffer = $order && ($order->offer_id || (float) $order->offer_discount > 0);
        $blockedByPrior = $hasOffer && $ctx && $ctx['has_prior'];

        return view('finance.sales_returns.create', [
            'order'          => $order,
            'ctx'            => $ctx,
            'hasOffer'       => $hasOffer,
            'blockedByPrior' => $blockedByPrior,
            'pricingMode'    => $ctx ? ($ctx['inclusive'] ? 'inclusive' : 'exclusive') : Helper::pricingMode(),
        ]);
    }

    // -------------------------------------------------------
    // Preview — حساب الخادم لعرضه قبل الحفظ
    // -------------------------------------------------------
    public function preview(Request $request)
    {
        $order = Order::with('offer')->findOrFail($request->order_id);

        try {
            $calc = $this->calculator->calculate($order, (array) $request->input('quantities', []), $request->boolean('include_shipping'));
        } catch (ValidationException $e) {
            return response()->json(['ok' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        $ctx = $this->calculator->context($order);

        return response()->json([
            'ok'             => true,
            'is_full'        => $calc['is_full'],
            'header'         => $calc['header'],
            'items'          => $calc['items'],
            'default_refund' => $this->calculator->defaultRefund($order, $calc['header']['total'], $ctx['prior_paid']),
        ]);
    }

    // -------------------------------------------------------
    // Store
    // -------------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'order_id'         => 'required|exists:orders,id',
            'date'             => 'required|date',
            'quantities'       => 'required|array',
            'quantities.*'     => 'nullable|numeric|min:0',
            'paid'             => 'nullable|numeric|min:0',
            'include_shipping' => 'nullable|boolean',
            'notes'            => 'nullable|string|max:2000',
        ], [
            'quantities.required' => 'حدّد كمية مرتجعة لبند واحد على الأقل.',
        ]);

        $order = Order::findOrFail($request->order_id);

        try {
            $return = $this->service->create($order, [
                'quantities'       => $request->quantities,
                'date'             => $request->date,
                'paid'             => $request->input('paid'),
                'include_shipping' => $request->boolean('include_shipping'),
                'notes'            => $request->notes,
            ]);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'تعذّر حفظ المرتجع ولم يُطبَّق أي أثر: ' . $e->getMessage());
        }

        return redirect()->route($this->route . '.show', $return->id)->with('success', 'تم تسجيل المرتجع ' . $return->return_number . ' بنجاح.');
    }

    // -------------------------------------------------------
    // Show
    // -------------------------------------------------------
    public function show($id)
    {
        $data = SalesReturn::with([
            'customer', 'warehouse:id,name', 'order.offer', 'order.coupon',
            'items.product:id,name,sku', 'items.variant:id,product_id,sku',
        ])->findOrFail($id);

        $settings    = Helper::settings();
        $pricingMode = Helper::pricingMode();

        return view('finance.sales_returns.show', compact('data', 'settings', 'pricingMode'));
    }

    // -------------------------------------------------------
    // Cancel
    // -------------------------------------------------------
    public function cancel(Request $request)
    {
        $return = SalesReturn::findOrFail($request->id);

        try {
            $this->service->cancel($return, $request->input('notes'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'تعذّر إلغاء المرتجع ولم يُطبَّق أي أثر: ' . $e->getMessage());
        }

        return redirect()->route($this->route . '.show', $return->id)->with('success', 'تم إلغاء المرتجع وعكس آثاره.');
    }
}
