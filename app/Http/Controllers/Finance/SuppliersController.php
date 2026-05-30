<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\Supplier;
use App\Http\Requests\Finance\SupplierRequest;
use Illuminate\Support\Facades\DB;

class SuppliersController extends Controller
{
    protected $viewPath  = 'finance.suppliers';
    private $route       = 'finance.suppliers';
    private $objectModel = Supplier::class;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->objectModel::query();

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('company_name', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('account_status')) {
                $query->where('account_status', $request->account_status);
            }

            $query->orderBy('created_at', 'desc');

            return Datatables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('info', function ($row) {
                    return '<strong>' . $row->company_name . '</strong><br>
                            <small class="text-muted">' . $row->name . '</small>';
                })
                ->addColumn('contact', function ($row) {
                    $c = $row->phone ?? '—';
                    if ($row->address) {
                        $c .= '<br><small class="text-muted">' . $row->address . '</small>';
                    }
                    return $c;
                })
                ->addColumn('wallet_balance', function ($row) {
                    return '<strong class="text-success">' . number_format($row->wallet_balance, 2) . '</strong>';
                })
                ->addColumn('account_status', function ($row) {
                    $colors = ['active' => 'success', 'inactive' => 'warning', 'blocked' => 'danger'];
                    $labels = ['active' => 'نشط', 'inactive' => 'غير نشط', 'blocked' => 'محظور'];
                    return '<span class="badge bg-light-' . ($colors[$row->account_status] ?? 'secondary') . '">'
                         . ($labels[$row->account_status] ?? $row->account_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    $btn .= '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-info me-2"><i class="bi bi-eye fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'info', 'contact', 'wallet_balance', 'account_status', 'action'])
                ->make(true);
        }

        return view($this->viewPath . '.index');
    }

    public function export(Request $request)
    {
        $data = $this->objectModel::query();

        if ($request->filled('search') || $request->filled('fsearch')) {
            $search = $request->search ?? $request->fsearch;
            $data->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('account_status')) {
            $data->where('account_status', $request->account_status);
        }

        return (new FastExcel($data->get()))->download('suppliers.csv');
    }

    public function show($id)
    {
        $data = $this->objectModel::with('wallets')->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function create()
    {
        return view($this->viewPath . '.create');
    }

    public function store(SupplierRequest $request)
    {
        DB::beginTransaction();
        $this->objectModel::create($request->validated());
        DB::commit();

        return redirect(route($this->route . '.index'))
            ->with('message', 'تم الاضافة بنجاح')->with('status', 'success');
    }

    public function edit($id)
    {
        $data = $this->objectModel::findOrFail($id);
        return view($this->viewPath . '.edit', compact('data'));
    }

    public function update(SupplierRequest $request)
    {
        DB::beginTransaction();
        $result = $this->objectModel::findOrFail($request->id);
        $result->update($request->validated());
        DB::commit();

        return redirect(route($this->route . '.index'))
            ->with('message', 'تم التعديل بنجاح')->with('status', 'success');
    }

    public function destroy(Request $request)
    {
        DB::beginTransaction();
        try {
            foreach ($this->objectModel::whereIn('id', $request->id)->get() as $item) {
                $item->delete();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'عفوا لم يتم الحذف']);
        }

        return response()->json(['status' => 'success', 'message' => 'تم الحذف بنجاح']);
    }
}
