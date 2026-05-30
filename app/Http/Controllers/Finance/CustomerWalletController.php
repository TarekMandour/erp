<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerWallet;
use App\Http\Requests\Finance\CustomerWalletRequest;
use Illuminate\Support\Facades\DB;

class CustomerWalletController extends Controller
{
    protected $viewPath  = 'finance.customers.wallet';
    private $route       = 'finance.customers.wallet';
    private $objectModel = CustomerWallet::class;

    public function index(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        if ($request->ajax()) {
            $query = $this->objectModel::where('customer_id', $customerId);

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where('description', 'LIKE', "%{$search}%");
            }

            $query->orderBy('date', 'desc');

            return Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('date', fn($row) => $row->date->format('Y-m-d'))
                ->addColumn('debit', fn($row) =>
                    '<span class="text-danger">' . number_format($row->debit, 2) . '</span>')
                ->addColumn('credit', fn($row) =>
                    '<span class="text-success">' . number_format($row->credit, 2) . '</span>')
                ->addColumn('balance', function ($row) {
                    $color = $row->balance >= 0 ? 'success' : 'danger';
                    return '<strong class="text-' . $color . '">' . number_format($row->balance, 2) . '</strong>';
                })
                ->addColumn('action', function ($row) use ($customerId) {
                    return '<a href="' . route($this->route . '.edit', [$customerId, $row->id]) . '" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                })
                ->rawColumns(['checkbox', 'date', 'debit', 'credit', 'balance', 'action'])
                ->make(true);
        }

        return view($this->viewPath . '.index', compact('customer'));
    }

    public function create($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        return view($this->viewPath . '.create', compact('customer'));
    }

    public function store(CustomerWalletRequest $request, $customerId)
    {
        DB::beginTransaction();
        $this->objectModel::create($request->validated());
        DB::commit();

        return redirect(route($this->route . '.index', $customerId))
            ->with('message', 'تم الاضافة بنجاح')->with('status', 'success');
    }

    public function edit($customerId, $id)
    {
        $customer = Customer::findOrFail($customerId);
        $data = $this->objectModel::findOrFail($id);
        return view($this->viewPath . '.edit', compact('data', 'customer'));
    }

    public function update(CustomerWalletRequest $request, $customerId)
    {
        DB::beginTransaction();
        $result = $this->objectModel::findOrFail($request->id);
        $result->update($request->validated());
        DB::commit();

        return redirect(route($this->route . '.index', $customerId))
            ->with('message', 'تم التعديل بنجاح')->with('status', 'success');
    }

    public function destroy(Request $request, $customerId)
    {
        DB::beginTransaction();
        try {
            foreach ($this->objectModel::whereIn('id', $request->id)->get() as $item) {
                $item->delete();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'عفوا لم يتم الحذف']);
        }

        return response()->json(['status' => 'success', 'message' => 'تم الحذف بنجاح']);
    }
}
