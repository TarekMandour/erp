<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\Bank;
use App\Http\Requests\Finance\BankRequest;

class BanksController extends Controller
{
    protected $viewPath  = 'finance.banks';
    private $route       = 'finance.banks';
    private $objectModel = Bank::class;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->objectModel::withCount('accounts');

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where('name', 'LIKE', "%{$search}%");
            }

            $query->orderBy('name');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('accounts_count', function ($row) {
                    return '<span class="badge bg-light-primary">' . $row->accounts_count . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    $btn .= '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-info me-2"><i class="bi bi-eye fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'accounts_count', 'action'])
                ->make(true);
        }

        return view($this->viewPath . '.index');
    }

    public function create()
    {
        return view($this->viewPath . '.create');
    }

    public function store(BankRequest $request)
    {
        $this->objectModel::create($request->validated());
        return redirect()->route($this->route . '.index')->with('success', 'تم إضافة البنك بنجاح');
    }

    public function edit($id)
    {
        $data = $this->objectModel::findOrFail($id);
        return view($this->viewPath . '.edit', compact('data'));
    }

    public function update(BankRequest $request)
    {
        $this->objectModel::findOrFail($request->id)->update($request->validated());
        return redirect()->route($this->route . '.index')->with('success', 'تم تحديث البنك بنجاح');
    }

    public function show($id)
    {
        $data = $this->objectModel::with('accounts')->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function destroy(Request $request)
    {
        $this->objectModel::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $data = $this->objectModel::withCount('accounts');
        if ($request->filled('search') || $request->filled('fsearch')) {
            $search = $request->search ?? $request->fsearch;
            $data->where('name', 'LIKE', "%{$search}%");
        }
        return (new FastExcel($data->orderBy('name')->get()))->download('banks.csv');
    }
}
