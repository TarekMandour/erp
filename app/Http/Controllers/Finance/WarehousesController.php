<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\Warehouse;
use App\Http\Requests\Finance\WarehouseRequest;

class WarehousesController extends Controller
{
    protected $viewPath  = 'finance.warehouses';
    private $route       = 'finance.warehouses';
    private $objectModel = Warehouse::class;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->objectModel::query();

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where('name', 'LIKE', "%{$search}%");
            }

            if ($request->filled('fstatus')) {
                $query->where('is_active', $request->fstatus);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="badge badge-light-success fw-bold">مفعل</span>'
                        : '<span class="badge badge-light-danger fw-bold">غير مفعل</span>';
                })
                ->addColumn('has_location', function ($row) {
                    if ($row->location && is_array($row->location) && count($row->location) >= 3) {
                        return '<span class="badge badge-light-primary fw-bold"><i class="bi bi-bounding-box me-1"></i>' . count($row->location) . ' نقطة</span>';
                    }
                    return '<span class="text-muted">—</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    $btn .= '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-info me-2"><i class="bi bi-eye fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'is_active', 'has_location', 'action'])
                ->make(true);
        }

        return view($this->viewPath . '.index');
    }

    public function create()
    {
        $data = new Warehouse();
        return view($this->viewPath . '.create', compact('data'));
    }

    public function store(WarehouseRequest $request)
    {
        $location = null;
        if ($request->filled('polygon_coords')) {
            $decoded = json_decode($request->polygon_coords, true);
            if (is_array($decoded) && count($decoded) >= 3) {
                $location = $decoded;
            }
        }

        $this->objectModel::create([
            'name'      => $request->name,
            'is_active' => $request->has('is_active') ? 1 : 0,
            'location'  => $location,
        ]);

        return redirect()->route($this->route . '.index')->with('success', 'تم إضافة المستودع بنجاح');
    }

    public function edit($id)
    {
        $data = $this->objectModel::findOrFail($id);
        return view($this->viewPath . '.edit', compact('data'));
    }

    public function update(WarehouseRequest $request)
    {
        $location = null;
        if ($request->filled('polygon_coords')) {
            $decoded = json_decode($request->polygon_coords, true);
            if (is_array($decoded) && count($decoded) >= 3) {
                $location = $decoded;
            }
        }

        $this->objectModel::findOrFail($request->id)->update([
            'name'      => $request->name,
            'is_active' => $request->has('is_active') ? 1 : 0,
            'location'  => $location,
        ]);

        return redirect()->route($this->route . '.index')->with('success', 'تم تحديث المستودع بنجاح');
    }

    public function show($id)
    {
        $data = $this->objectModel::findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function destroy(Request $request)
    {
        $this->objectModel::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $query = $this->objectModel::query();

        if ($request->filled('fsearch')) {
            $query->where('name', 'LIKE', '%' . $request->fsearch . '%');
        }
        if ($request->filled('fstatus')) {
            $query->where('is_active', $request->fstatus);
        }

        return (new FastExcel($query->orderBy('name')->get()))->download('warehouses.csv');
    }
}
