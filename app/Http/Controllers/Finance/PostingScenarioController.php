<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\PostingScenario;
use App\Models\Finance\PostingRule;
use App\Models\Finance\AccountTree;
use App\Models\Finance\CostCenter;
use App\Http\Requests\Finance\PostingScenarioRequest;

class PostingScenarioController extends Controller
{
    protected $viewPath = 'finance.posting_scenarios';
    private $route      = 'finance.posting_scenarios';

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PostingScenario::withCount('rules');

            if ($request->filled('operation_type')) {
                $query->where('operation_type', $request->operation_type);
            }
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'LIKE', "%{$search}%")
                      ->orWhere('name', 'LIKE', "%{$search}%");
                });
            }

            $query->orderBy('priority')->orderBy('id');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('code_link', function ($row) {
                    return '<a href="' . route($this->route . '.show', $row->id) . '" class="fw-bold text-primary">'
                         . $row->code . '</a>';
                })
                ->addColumn('operation_type_label', function ($row) {
                    $colors = [
                        'sales'     => 'success', 'purchase'  => 'primary',
                        'inventory' => 'info',    'expense'   => 'danger',
                        'receipt'   => 'warning', 'payment'   => 'dark',
                        'transfer'  => 'secondary',
                    ];
                    return '<span class="badge bg-light-' . ($colors[$row->operation_type] ?? 'secondary') . '">'
                         . $row->operation_type_label . '</span>';
                })
                ->addColumn('is_active_badge', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-light-success">نشط</span>'
                        : '<span class="badge bg-light-danger">متوقف</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-1"><i class="bi bi-pencil-square fs-4"></i></a>';
                    $btn .= '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-info me-1"><i class="bi bi-eye fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'code_link', 'operation_type_label', 'is_active_badge', 'action'])
                ->make(true);
        }

        return view($this->viewPath . '.index');
    }

    public function create()
    {
        $accounts    = AccountTree::where('is_active', true)->orderBy('code')->get();
        $costCenters = CostCenter::where('is_active', true)->orderBy('name')->get();
        return view($this->viewPath . '.create', compact('accounts', 'costCenters'));
    }

    public function store(PostingScenarioRequest $request)
    {
        $data             = $request->validated();
        $data['created_by'] = auth()->guard('admin')->id();
        $data['is_active']  = $request->has('is_active') ? 1 : 0;
        $rules            = $data['rules'] ?? [];
        unset($data['rules']);

        $scenario = PostingScenario::create($data);

        foreach ($rules as $rule) {
            $variables = $rule['variables'] ?? [];
            unset($rule['variables']);
            $rule['created_by']   = $data['created_by'];
            $rule['is_required']  = isset($rule['is_required']) ? 1 : 0;
            $rule['conditions']   = !empty($rule['conditions']) ? $rule['conditions'] : null;
            $rule['amount_field'] = !empty($rule['amount_field']) ? $rule['amount_field'] : null;
            $newRule = $scenario->rules()->create($rule);
            foreach ($variables as $variable) {
                $newRule->variables()->create($variable);
            }
        }

        return redirect()->route($this->route . '.show', $scenario->id)
            ->with('success', 'تم إنشاء السيناريو بنجاح: ' . $scenario->code);
    }

    public function edit($id)
    {
        $data        = PostingScenario::with([
            'rules.debitAccount',
            'rules.creditAccount',
            'rules.fixedCostCenter',
            'rules.variables',
        ])->findOrFail($id);
        $accounts    = AccountTree::where('is_active', true)->orderBy('code')->get();
        $costCenters = CostCenter::where('is_active', true)->orderBy('name')->get();
        return view($this->viewPath . '.edit', compact('data', 'accounts', 'costCenters'));
    }

    public function update(PostingScenarioRequest $request)
    {
        $scenario = PostingScenario::findOrFail($request->id);

        $data             = $request->validated();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $rules            = $data['rules'] ?? [];
        unset($data['rules']);

        Cache::forget("posting_scenario:{$scenario->code}");

        $scenario->update($data);
        $scenario->rules()->delete();

        $adminId = auth()->guard('admin')->id();
        foreach ($rules as $rule) {
            $variables = $rule['variables'] ?? [];
            unset($rule['variables']);
            $rule['created_by']   = $adminId;
            $rule['is_required']  = isset($rule['is_required']) ? 1 : 0;
            $rule['conditions']   = !empty($rule['conditions']) ? $rule['conditions'] : null;
            $rule['amount_field'] = !empty($rule['amount_field']) ? $rule['amount_field'] : null;
            $newRule = $scenario->rules()->create($rule);
            foreach ($variables as $variable) {
                $newRule->variables()->create($variable);
            }
        }

        
        return redirect()->route($this->route . '.show', $scenario->id)
            ->with('success', 'تم تحديث السيناريو بنجاح');
    }

    public function show($id)
    {
        $data = PostingScenario::with([
            'rules.debitAccount',
            'rules.creditAccount',
            'rules.fixedCostCenter',
            'rules.variables',
            'createdBy',
        ])->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function destroy(Request $request)
    {
        PostingScenario::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $data = PostingScenario::query();
        if ($request->filled('operation_type')) {
            $data->where('operation_type', $request->operation_type);
        }
        return (new FastExcel($data->orderBy('priority')->get()))->download('posting_scenarios.csv');
    }
}
