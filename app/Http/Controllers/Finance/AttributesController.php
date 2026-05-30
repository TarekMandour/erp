<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\Attribute;
use App\Http\Requests\Finance\AttributeRequest;
use Validator;

class AttributesController extends Controller
{
    protected $viewPath = 'finance.attributes';
    private $route = 'finance.attributes';
    private $objectModel = Attribute::class;

    public function __construct()
    {
        
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $data = $this->objectModel::query();
            $data = $data->orderBy('created_at', 'DESC');

            // Apply filters
            if (!empty($request->search) || !empty($request->fsearch)) {
                $search = $request->search ?? $request->fsearch;
                $data->search($search);
            }

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    $checkbox = '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                                </div>';
                    return $checkbox;
                })
                ->addColumn('action', function ($row) {
                    $btn = '<a href="javascript:;" onclick="edit_item(' . $row->id . ')" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['action', 'checkbox'])
                ->make(true);

        }
        return view($this->viewPath . '.index');
    }

    // For export with filters
    public function export(Request $request)
    {

        $data = $this->objectModel::query();

        if (!empty($request->search) || !empty($request->fsearch)) {
            $search = $request->search ?? $request->fsearch;
            $data->search($search);
        }

        $data = $data->get();

        return (new FastExcel($data))->download('file.csv');

    }

    public function show($id)
    {
        $data = $this->objectModel::find($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function create()
    {
        return view($this->viewPath . '.create');
    }

    public function store(AttributeRequest $request)
    {
        $data = $request->validated();
        $result = $this->objectModel::create($data);

        return redirect(route($this->route . '.index'))->with('message', 'تم الاضافة بنجاح')->with('status', 'success');
    }

    public function edit($id)
    {
        $data = $this->objectModel::find($id);
        return view($this->viewPath . '.form', compact('data'));
    }

    public function update(AttributeRequest $request)
    {

        $data = $request->validated();

        $result = $this->objectModel::whereId($request->id)->first();

        $result->update($data);

        return redirect(route($this->route . '.index'))->with('message', 'تم التعديل بنجاح')->with('status', 'success');
    }

    public function destroy(Request $request)
    {

        try {
            if ($this->objectModel->products()->exists()) {
                return redirect()->back()->with('error', 'Cannot delete category with products.');
            }

            $this->objectModel::whereIn('id', $request->id)->delete();

        } catch (\Exception $e) {
            return response()->json(['message' => 'error']);
        }
        return response()->json(['message' => 'success']);
    }
}
