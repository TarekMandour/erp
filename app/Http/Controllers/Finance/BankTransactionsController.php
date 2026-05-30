<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\BankTransaction;
use App\Models\Finance\BankAccount;
use App\Http\Requests\Finance\BankTransactionRequest;

class BankTransactionsController extends Controller
{
    protected $viewPath  = 'finance.bank_transactions';
    private $route       = 'finance.bank_transactions';
    private $objectModel = BankTransaction::class;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->objectModel::with('bankAccount.bank');

            if ($request->filled('bank_account_id')) {
                $query->where('bank_account_id', $request->bank_account_id);
            }

            if ($request->filled('ftype')) {
                $query->where('type', $request->ftype);
            }

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where(function ($q) use ($search) {
                    $q->where('reference_type', 'LIKE', "%{$search}%")
                      ->orWhereHas('bankAccount', fn($q2) => $q2->where('account_number', 'LIKE', "%{$search}%"));
                });
            }

            $query->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('account_info', function ($row) {
                    if (!$row->bankAccount) return '—';
                    $bank = $row->bankAccount->bank ? $row->bankAccount->bank->name . ' - ' : '';
                    return $bank . $row->bankAccount->account_number;
                })
                ->addColumn('type_badge', function ($row) {
                    $color = $row->type === 'deposit' ? 'success' : 'danger';
                    $label = $row->type === 'deposit' ? 'إيداع' : 'سحب';
                    return '<span class="badge bg-light-' . $color . '">' . $label . '</span>';
                })
                ->addColumn('amount', function ($row) {
                    $class = $row->type === 'deposit' ? 'text-success' : 'text-danger';
                    return '<strong class="' . $class . '">' . number_format($row->amount, 2) . '</strong>';
                })
                ->addColumn('reference', function ($row) {
                    if (!$row->reference_type) return '—';
                    return $row->reference_type . ($row->reference_id ? ' #' . $row->reference_id : '');
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    $btn .= '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-info me-2"><i class="bi bi-eye fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'type_badge', 'amount', 'action'])
                ->make(true);
        }

        $bankAccounts = BankAccount::with('bank')->orderBy('created_at', 'desc')->get();
        return view($this->viewPath . '.index', compact('bankAccounts'));
    }

    public function create()
    {
        $bankAccounts = BankAccount::with('bank')->orderBy('created_at', 'desc')->get();
        return view($this->viewPath . '.create', compact('bankAccounts'));
    }

    public function store(BankTransactionRequest $request)
    {
        $row = $this->objectModel::create($request->validated());
        $this->updateAccountBalance($row->bank_account_id);
        return redirect()->route($this->route . '.index')->with('success', 'تم إضافة الحركة البنكية بنجاح');
    }

    public function edit($id)
    {
        $data         = $this->objectModel::findOrFail($id);
        $bankAccounts = BankAccount::with('bank')->orderBy('created_at', 'desc')->get();
        return view($this->viewPath . '.edit', compact('data', 'bankAccounts'));
    }

    public function update(BankTransactionRequest $request)
    {
        $row          = $this->objectModel::findOrFail($request->id);
        $oldAccountId = $row->bank_account_id;
        $row->update($request->validated());
        $this->updateAccountBalance($oldAccountId);
        if ($row->bank_account_id !== $oldAccountId) {
            $this->updateAccountBalance($row->bank_account_id);
        }
        return redirect()->route($this->route . '.index')->with('success', 'تم تحديث الحركة البنكية بنجاح');
    }

    public function show($id)
    {
        $data = $this->objectModel::with('bankAccount.bank')->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function destroy(Request $request)
    {
        $rows       = $this->objectModel::whereIn('id', $request->ids)->get();
        $accountIds = $rows->pluck('bank_account_id')->unique();
        $this->objectModel::whereIn('id', $request->ids)->delete();
        foreach ($accountIds as $accountId) {
            $this->updateAccountBalance($accountId);
        }
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $data = $this->objectModel::with('bankAccount.bank');
        if ($request->filled('bank_account_id')) {
            $data->where('bank_account_id', $request->bank_account_id);
        }
        if ($request->filled('type')) {
            $data->where('type', $request->type);
        }
        return (new FastExcel($data->orderBy('created_at', 'desc')->get()))->download('bank_transactions.csv');
    }

    private function updateAccountBalance(int $accountId): void
    {
        $account = BankAccount::find($accountId);
        if (!$account) return;

        $deposits  = $this->objectModel::where('bank_account_id', $accountId)->where('type', 'deposit')->sum('amount');
        $withdraws = $this->objectModel::where('bank_account_id', $accountId)->where('type', 'withdraw')->sum('amount');

        $account->update(['balance' => $deposits - $withdraws]);
    }
}
