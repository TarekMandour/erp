<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Company;
use App\Http\Requests\CompanysRequest;
use App\Models\CompanyUser;
use App\Http\Requests\CompanyUserRequest;
use Illuminate\Support\Facades\Hash;
use Validator;

class CompanyUsersController extends Controller
{
    protected $viewPath = 'company.companys';
    private $route = 'company.companys';

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
                if ($request->is_active == 'inactive') {
                    $data->where('is_active', '0');
                } else {
                    $data->where('is_active', $request->is_active);
                }
                
            }
            
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    $checkbox = '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                                </div>';
                    return $checkbox;
                })
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route($this->route.'.edit', $row->id).'" class="btn btn-xs btn-icon btn-light-info me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['action','checkbox'])
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
           if ($request->is_active == 'inactive') {
                $data->where('is_active', '0');
            } else {
                $data->where('is_active', $request->is_active);
            }
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
        $data['company_id'] = auth('company')->user()->company_id ;
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

    public function editcompany($id)
    {
        $data = Company::find($id);
        return view($this->viewPath . '.editcompany', compact('data'));
    }

    public function updatecompany(CompanysRequest $request)
    {

        $data = $request->validated();

        $result = Company::whereId($request->id)->first();

        $result->update($data);

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $result->clearMediaCollection('image');
            $result->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return redirect()->back()->with('message', 'تم التعديل بنجاح')->with('status', 'success');
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
