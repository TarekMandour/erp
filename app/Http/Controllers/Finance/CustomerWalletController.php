<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerWallet;
use App\Http\Requests\Finance\CustomerWalletRequest;
use Illuminate\Support\Facades\DB;
use App\Services\Finance\WalletBalanceService;
use App\Services\Finance\WalletJournalService;

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
        $counterAccounts = app(WalletJournalService::class)->counterAccounts('1104');
        return view($this->viewPath . '.create', compact('customer', 'counterAccounts'));
    }

    public function store(CustomerWalletRequest $request, $customerId)
    {
        // الرصيد يُحسب في الخادم من الحركات، والحركة اليدوية تُرحَّل بقيد (QA 4.4 / 4.7)
        DB::transaction(function () use ($request, $customerId) {
            $data = $request->safe()->except(['counter_account_id', 'balance']);
            $row  = $this->objectModel::create(array_merge($data, ['balance' => 0]));
            app(WalletJournalService::class)->sync($row, (int) $request->counter_account_id);
            app(WalletBalanceService::class)->recalculateCustomer((int) $customerId);
        });

        return redirect(route($this->route . '.index', $customerId))
            ->with('message', 'تم الاضافة وترحيل القيد بنجاح')->with('status', 'success');
    }

    public function edit($customerId, $id)
    {
        $customer = Customer::findOrFail($customerId);
        $data = $this->objectModel::findOrFail($id);
        $wallets = app(WalletJournalService::class);
        $wallets->assertManual($data);
        $data->counter_account_id = $wallets->counterAccountId($data);
        $counterAccounts = $wallets->counterAccounts('1104');
        return view($this->viewPath . '.edit', compact('data', 'customer', 'counterAccounts'));
    }

    public function update(CustomerWalletRequest $request, $customerId)
    {
        DB::transaction(function () use ($request, $customerId) {
            $result = $this->objectModel::findOrFail($request->id);
            app(WalletJournalService::class)->assertManual($result);
            $result->update($request->safe()->except(['counter_account_id', 'balance']));
            app(WalletJournalService::class)->sync($result->fresh(), (int) $request->counter_account_id);
            app(WalletBalanceService::class)->recalculateCustomer((int) $customerId);
        });

        return redirect(route($this->route . '.index', $customerId))
            ->with('message', 'تم التعديل وتحديث القيد بنجاح')->with('status', 'success');
    }

    /**
     * حذف الحركة اليدوية = إلغاء قيدها بقيد عكسي ثم حذف الحركة؛ حركات المستندات تُحذف من مستندها
     */
    public function destroy(Request $request, $customerId)
    {
        try {
            DB::transaction(function () use ($request, $customerId) {
                $ids = (array) ($request->id ?? $request->ids ?? []);
                foreach ($this->objectModel::whereIn('id', $ids)->get() as $item) {
                    app(WalletJournalService::class)->assertManual($item);
                    app(WalletJournalService::class)->cancel($item);
                    $item->delete();
                }
                app(WalletBalanceService::class)->recalculateCustomer((int) $customerId);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'عفوا لم يتم الحذف'], 500);
        }

        return response()->json(['status' => 'success', 'message' => 'تم الحذف وإلغاء القيد بنجاح']);
    }
}
