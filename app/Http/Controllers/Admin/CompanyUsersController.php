<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\CompanyUser;
use App\Http\Requests\CompanyUserRequest;
use Illuminate\Support\Facades\Hash;
use Validator;

class CompanyUsersController extends Controller
{
    protected $viewPath = 'admin.companyusers';
    private $route = 'admin.companyusers';

    public function __construct(CompanyUser $model)
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
                $data->orWhere('phone', 'LIKE', "%$request->search%");
            }

            if (!empty($request->is_active)) {
                $data->where('is_active', $request->is_active);
            }

            if (!empty($request->company_id)) {
                $data->where('company_id', $request->company_id);
            }
            
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    $checkbox = '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                                </div>';
                    return $checkbox;
                })
                ->addColumn('company', function($row){
                    $company = $row->company->name;
                    return $company;
                })
                ->addColumn('type', function($row){
                    if($row->type == 'super') {
                        $type = 'مشرف';
                    } else {
                        $type = 'موظف';
                    }
                    return $type;
                })
                ->addColumn('status', function($row){
                    if($row->is_active == 0) {
                        $status = 'غير مفعل';
                    } elseif($row->is_active == 2) {
                        $status = 'محظور';
                    } else {
                        $status = 'مفعل';
                    }
                    return $status;
                })
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route($this->route.'.edit', $row->id).'" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['action','checkbox','company','type','status'])
                ->make(true);

        }
        return view($this->viewPath . '.index');
    }

    // For export with filters
    public function export(Request $request)
    {

        $data = $this->objectModel::query();
        
        // Apply the same filters as index
        if (!empty($request->is_active)) {
           $data = $data->where('is_active', $request->is_active );
        }

        if (!empty($request->company_id)) {
                $data->where('company_id', $request->company_id);
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

    public function store(CompanyUserRequest $request)
    {
        $data = $request->validated(); 
        $data['password']=Hash::make($request->password);

        $result = $this->objectModel::create($data);

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $result->clearMediaCollection('image');
            $result->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return redirect(route($this->route . '.index'))->with('message', 'تم الاضافة بنجاح')->with('status', 'success');
    }

    public function edit($id)
    {
        $data = $this->objectModel::find($id);
        return view($this->viewPath . '.edit', compact('data'));
    }

    public function update(CompanyUserRequest $request)
    {

        $data = $request->validated();

        $result = $this->objectModel::whereId($request->id)->first();

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        } else {
            $data['password'] = $result->password;
        }

        $result->update($data);

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $result->clearMediaCollection('image');
            $result->addMediaFromRequest('image')->toMediaCollection('image');
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
