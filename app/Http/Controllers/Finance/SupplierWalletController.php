<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\Supplier;
use App\Models\Finance\SupplierWallet;
use App\Http\Requests\Finance\SupplierWalletRequest;
use Illuminate\Support\Facades\DB;
use App\Services\Finance\WalletBalanceService;
use App\Services\Finance\WalletJournalService;

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
        $counterAccounts = app(WalletJournalService::class)->counterAccounts('2101');
        return view($this->viewPath . '.create', compact('supplier', 'counterAccounts'));
    }

    public function store(SupplierWalletRequest $request, $supplierId)
    {
        // الرصيد يُحسب في الخادم من الحركات، والحركة اليدوية تُرحَّل بقيد (QA 4.4 / 4.7)
        DB::transaction(function () use ($request, $supplierId) {
            $data = $request->safe()->except(['counter_account_id', 'balance']);
            $row  = $this->objectModel::create(array_merge($data, ['previous_balance' => 0, 'balance' => 0]));
            app(WalletJournalService::class)->sync($row, (int) $request->counter_account_id);
            app(WalletBalanceService::class)->recalculateSupplier((int) $supplierId);
        });

        return redirect(route($this->route . '.index', $supplierId))
            ->with('message', 'تم الاضافة وترحيل القيد بنجاح')->with('status', 'success');
    }

    public function edit($supplierId, $id)
    {
        $supplier = Supplier::findOrFail($supplierId);
        $data = $this->objectModel::findOrFail($id);
        $wallets = app(WalletJournalService::class);
        $wallets->assertManual($data);
        $data->counter_account_id = $wallets->counterAccountId($data);
        $counterAccounts = $wallets->counterAccounts('2101');
        return view($this->viewPath . '.edit', compact('data', 'supplier', 'counterAccounts'));
    }

    public function update(SupplierWalletRequest $request, $supplierId)
    {
        DB::transaction(function () use ($request, $supplierId) {
            $result = $this->objectModel::findOrFail($request->id);
            app(WalletJournalService::class)->assertManual($result);
            $result->update($request->safe()->except(['counter_account_id', 'balance']));
            app(WalletJournalService::class)->sync($result->fresh(), (int) $request->counter_account_id);
            app(WalletBalanceService::class)->recalculateSupplier((int) $supplierId);
        });

        return redirect(route($this->route . '.index', $supplierId))
            ->with('message', 'تم التعديل وتحديث القيد بنجاح')->with('status', 'success');
    }

    /**
     * حذف الحركة اليدوية = إلغاء قيدها بقيد عكسي ثم حذف الحركة؛ حركات المستندات تُحذف من مستندها
     */
    public function destroy(Request $request, $supplierId)
    {
        try {
            DB::transaction(function () use ($request, $supplierId) {
                $ids = (array) ($request->id ?? $request->ids ?? []);
                foreach ($this->objectModel::whereIn('id', $ids)->get() as $item) {
                    app(WalletJournalService::class)->assertManual($item);
                    app(WalletJournalService::class)->cancel($item);
                    $item->delete();
                }
                app(WalletBalanceService::class)->recalculateSupplier((int) $supplierId);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'عفوا لم يتم الحذف'], 500);
        }

        return response()->json(['status' => 'success', 'message' => 'تم الحذف وإلغاء القيد بنجاح']);
    }
}
