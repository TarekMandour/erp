<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\TransAccountTree;
use App\Models\Finance\AccountTree;
use App\Http\Requests\Finance\TransAccountTreeRequest;

class TransAccountTreeController extends Controller
{
    protected $viewPath  = 'finance.trans_account_trees';
    private $route       = 'finance.trans_account_trees';
    private $objectModel = TransAccountTree::class;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->objectModel::with('account');

            if ($request->filled('account_id')) {
                $query->where('account_id', $request->account_id);
            }

            if ($request->filled('reference_type')) {
                $query->where('reference_type', $request->reference_type);
            }

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'LIKE', "%{$search}%")
                      ->orWhere('reference_type', 'LIKE', "%{$search}%")
                      ->orWhereHas('account', function ($q2) use ($search) {
                          $q2->where('name', 'LIKE', "%{$search}%")
                             ->orWhere('code', 'LIKE', "%{$search}%");
                      });
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
                    if (!$row->account) return '—';
                    return '<strong>' . $row->account->name . '</strong><br>
                            <small class="text-muted">' . $row->account->code . '</small>';
                })
                ->addColumn('debit', function ($row) {
                    return $row->debit > 0
                        ? '<strong class="text-danger">' . number_format($row->debit, 2) . '</strong>'
                        : '—';
                })
                ->addColumn('credit', function ($row) {
                    return $row->credit > 0
                        ? '<strong class="text-success">' . number_format($row->credit, 2) . '</strong>'
                        : '—';
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
                ->rawColumns(['checkbox', 'account_info', 'debit', 'credit', 'reference', 'action'])
                ->make(true);
        }

        $accounts = AccountTree::where('is_active', true)->orderBy('code')->get();
        return view($this->viewPath . '.index', compact('accounts'));
    }

    public function create()
    {
        $accounts = AccountTree::where('is_active', true)->orderBy('code')->get();
        return view($this->viewPath . '.create', compact('accounts'));
    }

    public function store(TransAccountTreeRequest $request)
    {
        $row = $this->objectModel::create($request->validated());

        // Update account balances
        $this->updateAccountBalance($row->account_id);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم إضافة القيد بنجاح');
    }

    public function edit($id)
    {
        $data     = $this->objectModel::findOrFail($id);
        $accounts = AccountTree::where('is_active', true)->orderBy('code')->get();
        return view($this->viewPath . '.edit', compact('data', 'accounts'));
    }

    public function update(TransAccountTreeRequest $request)
    {
        $row = $this->objectModel::findOrFail($request->id);
        $oldAccountId = $row->account_id;

        $row->update($request->validated());

        // Update balances for old and new account
        $this->updateAccountBalance($oldAccountId);
        if ($row->account_id !== $oldAccountId) {
            $this->updateAccountBalance($row->account_id);
        }

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تحديث القيد بنجاح');
    }

    public function show($id)
    {
        $data = $this->objectModel::with('account')->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function destroy(Request $request)
    {
        $ids = $request->ids;
        $rows = $this->objectModel::whereIn('id', $ids)->get();
        $accountIds = $rows->pluck('account_id')->unique();

        $this->objectModel::whereIn('id', $ids)->delete();

        foreach ($accountIds as $accountId) {
            $this->updateAccountBalance($accountId);
        }

        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $data = $this->objectModel::with('account');

        if ($request->filled('account_id')) {
            $data->where('account_id', $request->account_id);
        }

        return (new FastExcel($data->orderBy('created_at', 'desc')->get()))->download('trans_account_trees.csv');
    }

    private function updateAccountBalance(int $accountId): void
    {
        $account = AccountTree::find($accountId);
        if (!$account) return;

        $totals = $this->objectModel::where('account_id', $accountId)
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $totalDebit  = $totals->total_debit  ?? 0;
        $totalCredit = $totals->total_credit ?? 0;

        $account->update([
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'balance'      => $totalDebit - $totalCredit,
        ]);
    }
}
