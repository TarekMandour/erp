<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\AccountTree;
use App\Http\Requests\Finance\AccountTreeRequest;
use Illuminate\Support\Facades\DB;

class AccountTreeController extends Controller
{
    protected $viewPath  = 'finance.account_trees';
    private $route       = 'finance.account_trees';
    private $objectModel = AccountTree::class;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->objectModel::with('parent');

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('code', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
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
                    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $row->level);
                    return $indent . '<strong>' . $row->name . '</strong><br>
                            <small class="text-muted">' . $row->code . '</small>';
                })
                ->addColumn('parent_name', function ($row) {
                    return $row->parent ? $row->parent->name : '—';
                })
                ->addColumn('type_name', function ($row) {
                    $colors = [
                        'asset'     => 'primary',
                        'liability' => 'danger',
                        'equity'    => 'warning',
                        'revenue'   => 'success',
                        'expense'   => 'info',
                    ];
                    $labels = [
                        'asset'     => 'أصول',
                        'liability' => 'خصوم',
                        'equity'    => 'حقوق ملكية',
                        'revenue'   => 'إيرادات',
                        'expense'   => 'مصروفات',
                    ];
                    return '<span class="badge bg-light-' . ($colors[$row->type] ?? 'secondary') . '">'
                         . ($labels[$row->type] ?? $row->type) . '</span>';
                })
                ->addColumn('balance', function ($row) {
                    $class = $row->account_type === 'debit' ? 'text-success' : 'text-danger';
                    return '<strong class="' . $class . '">' . number_format($row->balance, 2) . '</strong>';
                })
                ->addColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-light-success">نشط</span>'
                        : '<span class="badge bg-light-danger">متوقف</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    $btn .= '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-info me-2"><i class="bi bi-eye fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'info', 'parent_name', 'type_name', 'balance', 'is_active', 'action'])
                ->make(true);
        }

        return view($this->viewPath . '.index');
    }

    public function create()
    {
        $parents = $this->objectModel::where('is_active', true)->orderBy('code')->get();
        return view($this->viewPath . '.create', compact('parents'));
    }

    public function store(AccountTreeRequest $request)
    {
        $data = $request->validated();

        $level = 0;
        if (!empty($data['parent_id'])) {
            $parent = $this->objectModel::find($data['parent_id']);
            $level  = $parent ? $parent->level + 1 : 0;
        }

        $data['level']     = $level;
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        $this->objectModel::create($data);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم إضافة الحساب بنجاح');
    }

    public function edit($id)
    {
        $data    = $this->objectModel::findOrFail($id);
        $parents = $this->objectModel::where('is_active', true)
            ->where('id', '!=', $id)
            ->orderBy('code')
            ->get();
        return view($this->viewPath . '.edit', compact('data', 'parents'));
    }

    public function update(AccountTreeRequest $request)
    {
        $data = $request->validated();
        $row  = $this->objectModel::findOrFail($request->id);

        $level = 0;
        if (!empty($data['parent_id'])) {
            $parent = $this->objectModel::find($data['parent_id']);
            $level  = $parent ? $parent->level + 1 : 0;
        }

        $data['level']     = $level;
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        $row->update($data);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تحديث الحساب بنجاح');
    }

    public function show($id)
    {
        $data = $this->objectModel::with(['parent', 'children', 'transactions.account'])->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function destroy(Request $request)
    {
        $ids = $request->ids;
        $this->objectModel::whereIn('id', $ids)->delete();
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $data = $this->objectModel::query();

        if ($request->filled('search') || $request->filled('fsearch')) {
            $search = $request->search ?? $request->fsearch;
            $data->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $data->where('type', $request->type);
        }

        return (new FastExcel($data->orderBy('code')->get()))->download('account_trees.csv');
    }

    public function recalculate()
    {
        
        foreach ($this->objectModel::whereHas('children')->get() as $account) {
            DB::transaction(function () use ($account) {

                if (!$account instanceof AccountTree) {
                    $account = AccountTree::findOrFail($account);
                }

                $this->recalculateAccount($account);
            });
        }

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تحديث الحسابات بنجاح');
        
    }


    protected function recalculateAccount(AccountTree $account): float
    {
        $children = $account->children()->get();

        // إذا كان الحساب أب
        if ($children->isNotEmpty()) {

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($children as $child) {

                // احسب الابن أولاً
                $this->recalculateAccount($child);

                $totalDebit += (float) $child->total_debit;
                $totalCredit += (float) $child->total_credit;
            }

            $account->total_debit = $totalDebit;
            $account->total_credit = $totalCredit;
        }

        // حساب الرصيد حسب طبيعة الحساب
        if ($account->account_type === 'debit') {
            $balance = $account->total_debit - $account->total_credit;
        } else {
            $balance = $account->total_credit - $account->total_debit;
        }

        $account->balance = $balance;

        $account->save();

        return (float) $balance;
    }

}
