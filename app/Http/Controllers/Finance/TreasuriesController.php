<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\Treasury;
use App\Http\Requests\Finance\TreasuryRequest;

class TreasuriesController extends Controller
{
    protected $viewPath  = 'finance.treasuries';
    private $route       = 'finance.treasuries';
    private $objectModel = Treasury::class;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->objectModel::withCount('transactions');

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where('name', 'LIKE', "%{$search}%");
            }

            if ($request->filled('fstatus')) {
                $query->where('is_active', $request->fstatus);
            }

            $query->orderBy('name');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-light-success">مفعل</span>'
                        : '<span class="badge bg-light-danger">غير مفعل</span>';
                })
                ->addColumn('balance', function ($row) {
                    $class = $row->balance >= 0 ? 'text-success' : 'text-danger';
                    return '<strong class="' . $class . '">' . number_format($row->balance, 2) . ' ' . $row->currency . '</strong>';
                })
                ->addColumn('transactions_count', function ($row) {
                    return '<span class="badge bg-light-primary">' . $row->transactions_count . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    $btn .= '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-info me-2"><i class="bi bi-eye fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'is_active', 'balance', 'transactions_count', 'action'])
                ->make(true);
        }

        return view($this->viewPath . '.index');
    }

    public function create()
    {
        $data = new Treasury();
        return view($this->viewPath . '.create', compact('data'));
    }

    public function store(TreasuryRequest $request)
    {
        Treasury::create(array_merge($request->validated(), [
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]));
        return redirect()->route($this->route . '.index')->with('success', 'تم إضافة الخزنة بنجاح');
    }

    public function edit($id)
    {
        $data = $this->objectModel::findOrFail($id);
        return view($this->viewPath . '.edit', compact('data'));
    }

    public function update(TreasuryRequest $request)
    {
        $this->objectModel::findOrFail($request->id)->update(array_merge($request->validated(), [
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]));
        return redirect()->route($this->route . '.index')->with('success', 'تم تحديث الخزنة بنجاح');
    }

    public function show($id)
    {
        $data = $this->objectModel::with('transactions')->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function destroy(Request $request)
    {
        $this->objectModel::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $query = $this->objectModel::withCount('transactions');
        if ($request->filled('search') || $request->filled('fsearch')) {
            $search = $request->search ?? $request->fsearch;
            $query->where('name', 'LIKE', "%{$search}%");
        }
        return (new FastExcel($query->orderBy('name')->get()))->download('treasuries.csv');
    }
}
