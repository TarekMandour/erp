<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Blog;
use App\Http\Requests\BlogsRequest;
use Validator;

class BlogsController extends Controller
{
    protected $viewPath = 'admin.blogs';
    private $route = 'admin.blogs';

    public function __construct(Blog $model)
    {
        $this->objectModel = $model;
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $data = $this->objectModel::query();
            $data = $data->orderBy('id', 'DESC');
            

            // Apply filters
            if (!empty($request->search)) {
                $data->where('name', 'LIKE', "%$request->search%");
                $data->orWhere('type', $request->search);
            }
            
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    $checkbox = '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                                </div>';
                    return $checkbox;
                })
                ->addColumn('date', function($row) {
                    return $row->created_at->format('F j, Y');
                })
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route($this->route.'.edit', $row->id).'" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['date','action','checkbox'])
                ->make(true);

        }
        return view($this->viewPath . '.index');
    }

    // For export with filters
    public function export(Request $request)
    {

        $data = $this->objectModel::query();

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

    public function store(BlogsRequest $request)
    {
        $data = $request->validated();
        $result = $this->objectModel::create($data);

        if($request->hasFile('photo')){
            $result->addMultipleMediaFromRequest(['photo'])->each(function ($fileAdder) {
                $fileAdder->toMediaCollection('photo');
            });
        }

        if($request->hasFile('video') && $request->file('video')->isValid()){
            $result->addMediaFromRequest('video')->toMediaCollection('video');
        }

        return redirect(route($this->route . '.index'))->with('message', 'تم الاضافة بنجاح')->with('status', 'success');
    }

    public function edit($id)
    {
        $data = $this->objectModel::find($id);
        return view($this->viewPath . '.edit', compact('data'));
    }

    public function update(BlogsRequest $request)
    {

        $data = $request->validated();

        $result = $this->objectModel::whereId($request->id)->first();

        $result->update($data);

        if($request->hasFile('photo')){
            $result->addMultipleMediaFromRequest(['photo'])->each(function ($fileAdder) {
                $fileAdder->toMediaCollection('photo');
            });
        }

        if($request->hasFile('video') && $request->file('video')->isValid()){
            $result->addMediaFromRequest('video')->toMediaCollection('video');
        }

        return redirect(route($this->route . '.index'))->with('message', 'تم التعديل بنجاح')->with('status', 'success');
    }

    public function destroy(Request $request)
    {

        try {
            $this->objectModel::whereIn('id', $request->id)->delete();
        } catch (\Exception $e) {
            return response()->json(['message' => 'error']);
        }
        return response()->json(['message' => 'success']);
    }
}
