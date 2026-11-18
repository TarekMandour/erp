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
                    // الرصيد المخزّن = مدين − دائن؛ يُعرض حسب طبيعة الحساب (الدائن يظهر موجباً)
                    $natural = $row->account_type === 'credit' ? -(float) $row->balance : (float) $row->balance;
                    $class   = $natural < 0 ? 'text-danger' : 'text-success';
                    $side    = $row->account_type === 'credit' ? 'دائن' : 'مدين';
                    return '<strong class="' . $class . '">' . number_format($natural, 2) . '</strong> <small class="text-muted">' . $side . '</small>';
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
        $data = $this->prepare($request);

        $this->objectModel::create($data);

        return redirect()->route($this->route . '.index')
            ->with('message', 'تم إضافة الحساب بنجاح');
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
        $row  = $this->objectModel::findOrFail($request->id);
        $data = $this->prepare($request);

        DB::transaction(function () use ($row, $data) {
            $row->update($data);
            $this->refreshDescendantLevels($row);
        });

        return redirect()->route($this->route . '.index')
            ->with('message', 'تم تحديث الحساب بنجاح');
    }

    /**
     * البيانات المسموح بها فقط (لا أرصدة)، المستوى من الأب، والطبيعة الافتراضية من النوع
     */
    private function prepare(AccountTreeRequest $request): array
    {
        $data = $request->safe()->only(['name', 'code', 'parent_id', 'account_type', 'type']);

        $parent             = ! empty($data['parent_id']) ? $this->objectModel::find($data['parent_id']) : null;
        $data['parent_id']  = $parent?->id;
        $data['level']      = $parent ? $parent->level + 1 : 0;
        $data['is_active']  = $request->has('is_active') ? 1 : 0;
        // QA 7.2 (قرار 2): حساب نقدية يصلح للربط بخزنة — للأصول فقط
        $data['is_cash']    = $request->has('is_cash') && ($data['type'] ?? null) === 'asset' ? 1 : 0;

        if (empty($data['account_type'])) {
            $data['account_type'] = in_array($data['type'], ['asset', 'expense'], true) ? 'debit' : 'credit';
        }

        return $data;
    }

    private function refreshDescendantLevels(AccountTree $account): void
    {
        foreach ($account->children()->get() as $child) {
            $child->update(['level' => $account->level + 1]);
            $this->refreshDescendantLevels($child);
        }
    }

    public function show($id)
    {
        $data = $this->objectModel::with(['parent', 'children', 'transactions.account'])->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    /**
     * لا يُحذف حساب له فروع أو قيود أو مرتبط بخزنة/حساب بنكي/رصيد افتتاحي/قاعدة ترحيل
     */
    public function destroy(Request $request)
    {
        $ids      = (array) ($request->ids ?? $request->id ?? []);
        $accounts = $this->objectModel::whereIn('id', $ids)->get();

        foreach ($accounts as $account) {
            $reason = match (true) {
                $account->children()->exists()                                                 => 'له حسابات فرعية (احذف الفروع أولاً)',
                $account->transactions()->exists()                                             => 'عليه قيود محاسبية',
                \App\Models\Finance\Treasury::where('account_tree_id', $account->id)->exists()    => 'مربوط بخزنة',
                \App\Models\Finance\BankAccount::where('account_tree_id', $account->id)->exists() => 'مربوط بحساب بنكي',
                \App\Models\Finance\OpeningBalance::where('account_id', $account->id)->exists()   => 'له رصيد افتتاحي',
                \App\Models\Finance\PostingRule::where('debit_account_id', $account->id)->orWhere('credit_account_id', $account->id)->exists() => 'مستخدم في قواعد الترحيل',
                default => null,
            };

            if ($reason) {
                return response()->json([
                    'success' => false,
                    'message' => "لا يمكن حذف الحساب {$account->code} - {$account->name} لأنه {$reason}",
                ], 422);
            }
        }

        $this->objectModel::whereIn('id', $accounts->pluck('id'))->delete();

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
        
        foreach ($this->objectModel::whereNull('parent_id')->get() as $account) {
            DB::transaction(function () use ($account) {

                if (!$account instanceof AccountTree) {
                    $account = AccountTree::findOrFail($account);
                }

                $this->recalculateAccount($account);
            });
        }

        return redirect()->route($this->route . '.index')
            ->with('message', 'تم تحديث الحسابات بنجاح');
        
    }


    /**
     * الحساب الفرعي من بنود القيود غير المسودة، والأب = مجموع فروعه.
     * الرصيد المخزّن دائماً مدين − دائن (كما في كل خدمات الترحيل)؛ الطبيعة للعرض فقط.
     */
    protected function recalculateAccount(AccountTree $account): float
    {
        $children = $account->children()->get();

        if ($children->isNotEmpty()) {
            $totalDebit  = 0;
            $totalCredit = 0;

            foreach ($children as $child) {
                $this->recalculateAccount($child);
                $child->refresh();
                $totalDebit  += (float) $child->total_debit;
                $totalCredit += (float) $child->total_credit;
            }

            $account->total_debit  = $totalDebit;
            $account->total_credit = $totalCredit;
            $account->balance      = $totalDebit - $totalCredit;
            $account->save();
        } else {
            app(\App\Services\Finance\ManualJournalService::class)->recalculateAccounts([$account->id]);
        }

        return (float) $account->fresh()->balance;
    }

}
