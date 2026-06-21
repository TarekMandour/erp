<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\CostCenter;
use App\Http\Requests\Finance\CostCenterRequest;

class CostCenterController extends Controller
{
    protected $viewPath = 'finance.cost_centers';
    private $route      = 'finance.cost_centers';

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = CostCenter::with('parent');

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('code', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            $query->orderBy('code');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('info', function ($row) {
                    return '<strong>' . $row->name . '</strong><br>
                            <small class="text-muted">' . $row->code . '</small>';
                })
                ->addColumn('parent_name', function ($row) {
                    return $row->parent ? $row->parent->name : '—';
                })
                ->addColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-light-success">نشط</span>'
                        : '<span class="badge bg-light-danger">متوقف</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-1"><i class="bi bi-pencil-square fs-4"></i></a>';
                    $btn .= '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-info me-1"><i class="bi bi-eye fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'info', 'parent_name', 'is_active', 'action'])
                ->make(true);
        }

        return view($this->viewPath . '.index');
    }

    public function create()
    {
        $parents = CostCenter::where('is_active', true)->orderBy('code')->get();
        return view($this->viewPath . '.create', compact('parents'));
    }

    public function store(CostCenterRequest $request)
    {
        $data             = $request->validated();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        CostCenter::create($data);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم إضافة مركز التكلفة بنجاح');
    }

    public function edit($id)
    {
        $data    = CostCenter::findOrFail($id);
        $parents = CostCenter::where('is_active', true)->where('id', '!=', $id)->orderBy('code')->get();
        return view($this->viewPath . '.edit', compact('data', 'parents'));
    }

    public function update(CostCenterRequest $request)
    {
        $row              = CostCenter::findOrFail($request->id);
        $data             = $request->validated();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        $row->update($data);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تحديث مركز التكلفة بنجاح');
    }

    public function show($id)
    {
        $data = CostCenter::with(['parent', 'children'])->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function destroy(Request $request)
    {
        $ids = $request->ids;
        CostCenter::whereIn('id', $ids)->delete();
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $data = CostCenter::query();

        if ($request->filled('is_active')) {
            $data->where('is_active', $request->is_active);
        }

        return (new FastExcel($data->orderBy('code')->get()))->download('cost_centers.csv');
    }
}
