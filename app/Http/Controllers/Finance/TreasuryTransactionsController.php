<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\TreasuryTransaction;
use App\Models\Finance\Treasury;
use App\Http\Requests\Finance\TreasuryTransactionRequest;

class TreasuryTransactionsController extends Controller
{
    protected $viewPath  = 'finance.treasury_transactions';
    private $route       = 'finance.treasury_transactions';
    private $objectModel = TreasuryTransaction::class;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->objectModel::with('treasury');

            if ($request->filled('treasury_id')) {
                $query->where('treasury_id', $request->treasury_id);
            }

            if ($request->filled('ftype')) {
                $query->where('type', $request->ftype);
            }

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'LIKE', "%{$search}%")
                      ->orWhere('reference_type', 'LIKE', "%{$search}%")
                      ->orWhereHas('treasury', fn($q2) => $q2->where('name', 'LIKE', "%{$search}%"));
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
                ->addColumn('treasury_name', fn($row) => $row->treasury ? $row->treasury->name : '—')
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

        $treasuries = Treasury::orderBy('name')->get();
        return view($this->viewPath . '.index', compact('treasuries'));
    }

    public function create()
    {
        $data       = new TreasuryTransaction();
        $treasuries = Treasury::where('is_active', true)->orderBy('name')->get();
        return view($this->viewPath . '.create', compact('data', 'treasuries'));
    }

    public function store(TreasuryTransactionRequest $request)
    {
        $row = $this->objectModel::create($request->validated());
        $this->updateTreasuryBalance($row->treasury_id);
        return redirect()->route($this->route . '.index')->with('success', 'تم إضافة الحركة بنجاح');
    }

    public function edit($id)
    {
        $data       = $this->objectModel::findOrFail($id);
        $treasuries = Treasury::where('is_active', true)->orderBy('name')->get();
        return view($this->viewPath . '.edit', compact('data', 'treasuries'));
    }

    public function update(TreasuryTransactionRequest $request)
    {
        $row           = $this->objectModel::findOrFail($request->id);
        $oldTreasuryId = $row->treasury_id;
        $row->update($request->validated());
        $this->updateTreasuryBalance($oldTreasuryId);
        if ($row->treasury_id !== $oldTreasuryId) {
            $this->updateTreasuryBalance($row->treasury_id);
        }
        return redirect()->route($this->route . '.index')->with('success', 'تم تحديث الحركة بنجاح');
    }

    public function show($id)
    {
        $data = $this->objectModel::with('treasury')->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function destroy(Request $request)
    {
        $rows        = $this->objectModel::whereIn('id', $request->ids)->get();
        $treasuryIds = $rows->pluck('treasury_id')->unique();
        $this->objectModel::whereIn('id', $request->ids)->delete();
        foreach ($treasuryIds as $treasuryId) {
            $this->updateTreasuryBalance($treasuryId);
        }
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $data = $this->objectModel::with('treasury');
        if ($request->filled('treasury_id')) {
            $data->where('treasury_id', $request->treasury_id);
        }
        if ($request->filled('ftype')) {
            $data->where('type', $request->ftype);
        }
        return (new FastExcel($data->orderBy('created_at', 'desc')->get()))->download('treasury_transactions.csv');
    }

    private function updateTreasuryBalance(int $treasuryId): void
    {
        $treasury = Treasury::find($treasuryId);
        if (!$treasury) return;

        $deposits  = TreasuryTransaction::where('treasury_id', $treasuryId)->where('type', 'deposit')->sum('amount');
        $withdraws = TreasuryTransaction::where('treasury_id', $treasuryId)->where('type', 'withdraw')->sum('amount');

        $treasury->update(['balance' => $deposits - $withdraws]);
    }
}
