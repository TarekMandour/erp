<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\AccountTree;
use App\Models\Finance\BankAccount;
use App\Models\Finance\Bank;
use App\Http\Requests\Finance\BankAccountRequest;

class BankAccountsController extends Controller
{
    protected $viewPath  = 'finance.bank_accounts';
    private $route       = 'finance.bank_accounts';
    private $objectModel = BankAccount::class;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->objectModel::with('bank');

            if ($request->filled('bank_id')) {
                $query->where('bank_id', $request->bank_id);
            }

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where(function ($q) use ($search) {
                    $q->where('account_number', 'LIKE', "%{$search}%")
                      ->orWhereHas('bank', fn($q2) => $q2->where('name', 'LIKE', "%{$search}%"));
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
                ->addColumn('bank_name', fn($row) => $row->bank ? $row->bank->name : '—')
                ->addColumn('balance', function ($row) {
                    $class = $row->balance >= 0 ? 'text-success' : 'text-danger';
                    return '<strong class="' . $class . '">' . number_format($row->balance, 2) . ' ' . $row->currency . '</strong>';
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    $btn .= '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-info me-2"><i class="bi bi-eye fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'balance', 'action'])
                ->make(true);
        }

        $banks = Bank::orderBy('name')->get();
        return view($this->viewPath . '.index', compact('banks'));
    }

    public function create()
    {
        $banks = Bank::orderBy('name')->get();
        $accounts    = AccountTree::where('is_active', true)->orderBy('code')->get();
        return view($this->viewPath . '.create', compact('banks', 'accounts'));
    }

    public function store(BankAccountRequest $request)
    {
        $this->objectModel::create($request->validated());
        return redirect()->route($this->route . '.index')->with('success', 'تم إضافة الحساب البنكي بنجاح');
    }

    public function edit($id)
    {
        $data  = $this->objectModel::findOrFail($id);
        $banks = Bank::orderBy('name')->get();
        $accounts    = AccountTree::where('is_active', true)->orderBy('code')->get();
        return view($this->viewPath . '.edit', compact('data', 'banks', 'accounts'));
    }

    public function update(BankAccountRequest $request)
    {
        $this->objectModel::findOrFail($request->id)->update($request->validated());
        return redirect()->route($this->route . '.index')->with('success', 'تم تحديث الحساب البنكي بنجاح');
    }

    public function show($id)
    {
        $data = $this->objectModel::with(['bank', 'transactions'])->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function destroy(Request $request)
    {
        $this->objectModel::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $data = $this->objectModel::with('bank');
        if ($request->filled('bank_id')) {
            $data->where('bank_id', $request->bank_id);
        }
        return (new FastExcel($data->orderBy('created_at', 'desc')->get()))->download('bank_accounts.csv');
    }
}
