<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Finance\PostingRuleVariable;
use App\Models\Finance\PostingRule;
use App\Models\Finance\PostingScenario;
use App\Http\Requests\Finance\PostingRuleVariableRequest;

class PostingRuleVariableController extends Controller
{
    protected $viewPath = 'finance.posting_rule_variables';
    private $route      = 'finance.posting_rule_variables';

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PostingRuleVariable::with(['rule.scenario', 'rule.debitAccount', 'rule.creditAccount']);

            if ($request->filled('rule_id')) {
                $query->where('rule_id', $request->rule_id);
            }
            if ($request->filled('scenario_id')) {
                $query->whereHas('rule', fn ($q) => $q->where('scenario_id', $request->scenario_id));
            }
            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where(function ($q) use ($search) {
                    $q->where('variable_name', 'LIKE', "%{$search}%")
                      ->orWhere('source_value', 'LIKE', "%{$search}%");
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('scenario_info', function ($row) {
                    if (!$row->rule?->scenario) return '—';
                    return '<strong>' . $row->rule->scenario->code . '</strong><br>
                            <small class="text-muted">مجموعة ' . $row->rule->rule_group . '</small>';
                })
                ->addColumn('source_type_badge', function ($row) {
                    $colors = ['field' => 'primary', 'function' => 'info', 'subquery' => 'warning'];
                    return '<span class="badge bg-light-' . ($colors[$row->source_type] ?? 'secondary') . '">'
                         . $row->source_type_label . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-1"><i class="bi bi-pencil-square fs-4"></i></a>';
                    $btn .= '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-info me-1"><i class="bi bi-eye fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'scenario_info', 'source_type_badge', 'action'])
                ->make(true);
        }

        $scenarios = PostingScenario::orderBy('code')->get();
        return view($this->viewPath . '.index', compact('scenarios'));
    }

    public function create(Request $request)
    {
        $rules     = PostingRule::with('scenario')->orderBy('scenario_id')->orderBy('sort_order')->get();
        $ruleId    = $request->rule_id;
        return view($this->viewPath . '.create', compact('rules', 'ruleId'));
    }

    public function store(PostingRuleVariableRequest $request)
    {
        PostingRuleVariable::create(array_merge($request->validated(), ['created_at' => now()]));

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم إضافة المتغير بنجاح');
    }

    public function edit($id)
    {
        $data  = PostingRuleVariable::findOrFail($id);
        $rules = PostingRule::with('scenario')->orderBy('scenario_id')->orderBy('sort_order')->get();
        return view($this->viewPath . '.edit', compact('data', 'rules'));
    }

    public function update(PostingRuleVariableRequest $request)
    {
        PostingRuleVariable::findOrFail($request->id)->update($request->validated());
        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تحديث المتغير بنجاح');
    }

    public function show($id)
    {
        $data = PostingRuleVariable::with(['rule.scenario', 'rule.debitAccount', 'rule.creditAccount'])->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function destroy(Request $request)
    {
        PostingRuleVariable::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true]);
    }
}
