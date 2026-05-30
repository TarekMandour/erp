<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\Supplier;
use App\Models\Finance\SupplierWallet;
use App\Http\Requests\Finance\SupplierWalletRequest;
use Illuminate\Support\Facades\DB;

class SupplierWalletController extends Controller
{
    protected $viewPath  = 'finance.suppliers.wallet';
    private $route       = 'finance.suppliers.wallet';
    private $objectModel = SupplierWallet::class;

    public function index(Request $request, $supplierId)
    {
        $supplier = Supplier::findOrFail($supplierId);

        if ($request->ajax()) {
            $query = $this->objectModel::where('supplier_id', $supplierId);

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where('description', 'LIKE', "%{$search}%");
            }

            $query->orderBy('date', 'desc');
            $query->orderByDesc('created_at');

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
                ->addColumn('previous_balance', fn($row) =>
                    number_format($row->previous_balance, 2))
                ->addColumn('balance', function ($row) {
                    $color = $row->balance >= 0 ? 'success' : 'danger';
                    return '<strong class="text-' . $color . '">' . number_format($row->balance, 2) . '</strong>';
                })
                ->addColumn('action', function ($row) use ($supplierId) {
                    return '<a href="' . route($this->route . '.edit', [$supplierId, $row->id]) . '" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                })
                ->rawColumns(['checkbox', 'date', 'debit', 'credit', 'previous_balance', 'balance', 'action'])
                ->make(true);
        }

        return view($this->viewPath . '.index', compact('supplier'));
    }

    public function create($supplierId)
    {
        $supplier = Supplier::findOrFail($supplierId);
        return view($this->viewPath . '.create', compact('supplier'));
    }

    public function store(SupplierWalletRequest $request, $supplierId)
    {
        $lastWallet = $this->objectModel::where('supplier_id', $supplierId)
            ->orderBy('date', 'desc')->orderBy('id', 'desc')->first();
        $previousBalance = $lastWallet ? $lastWallet->balance : 0;
        $balance = $previousBalance + $request->credit - $request->debit;

        DB::beginTransaction();
        $this->objectModel::create(array_merge($request->validated(), [
            'previous_balance' => $previousBalance,
            'balance'          => $balance,
        ]));
        DB::commit();

        Supplier::where('id', $supplierId)->update(['wallet_balance' => $balance]);

        return redirect(route($this->route . '.index', $supplierId))
            ->with('message', 'تم الاضافة بنجاح')->with('status', 'success');
    }

    public function edit($supplierId, $id)
    {
        $supplier = Supplier::findOrFail($supplierId);
        $data = $this->objectModel::findOrFail($id);
        return view($this->viewPath . '.edit', compact('data', 'supplier'));
    }

    public function update(SupplierWalletRequest $request, $supplierId)
    {
        $previousWallet = $this->objectModel::where('supplier_id', $supplierId)
            ->where('id', '<', $request->id)
            ->orderBy('date', 'desc')->orderBy('id', 'desc')->first();
        $previousBalance = $previousWallet ? $previousWallet->balance : 0;
        $balance = $previousBalance + $request->credit - $request->debit;

        DB::beginTransaction();
        $result = $this->objectModel::findOrFail($request->id);
        $result->update(array_merge($request->validated(), [
            'previous_balance' => $previousBalance,
            'balance'          => $balance,
        ]));
        DB::commit();

        Supplier::where('id', $supplierId)->update(['wallet_balance' => $balance]);
        
        return redirect(route($this->route . '.index', $supplierId))
            ->with('message', 'تم التعديل بنجاح')->with('status', 'success');
    }

    public function destroy(Request $request, $supplierId)
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
