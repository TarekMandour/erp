<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\OpeningBalance;
use App\Models\Finance\AccountTree;
use App\Http\Requests\Finance\OpeningBalanceRequest;
use App\Models\Finance\JournalEntry;
use App\Services\Finance\ManualJournalService;
use Illuminate\Support\Facades\DB;

class OpeningBalancesController extends Controller
{
    protected $viewPath  = 'finance.opening_balances';
    private $route       = 'finance.opening_balances';
    private $objectModel = OpeningBalance::class;

    public function __construct(private ManualJournalService $journal)
    {
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->objectModel::with('account');

            if ($request->filled('account_id')) {
                $query->where('account_id', $request->account_id);
            }

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'LIKE', "%{$search}%")
                      ->orWhereHas('account', fn($q2) => $q2->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('code', 'LIKE', "%{$search}%"));
                });
            }

            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            $query->orderBy('date', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('account_name', function ($row) {
                    if (!$row->account) return '—';
                    return '<span class="fw-semibold">' . $row->account->name . '</span>'
                        . '<br><small class="text-muted">' . $row->account->code . '</small>';
                })
                ->addColumn('debit', function ($row) {
                    return $row->debit > 0
                        ? '<strong class="text-success">' . number_format($row->debit, 2) . '</strong>'
                        : '<span class="text-muted">—</span>';
                })
                ->addColumn('credit', function ($row) {
                    return $row->credit > 0
                        ? '<strong class="text-danger">' . number_format($row->credit, 2) . '</strong>'
                        : '<span class="text-muted">—</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    $btn .= '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-info me-2"><i class="bi bi-eye fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'account_name', 'debit', 'credit', 'action'])
                ->make(true);
        }

        $accounts = AccountTree::where('is_active', true)->orderBy('code')->get();
        return view($this->viewPath . '.index', compact('accounts'));
    }

    public function create()
    {
        $data     = new OpeningBalance();
        $accounts = AccountTree::where('is_active', true)->whereDoesntHave('children')->orderBy('code')->get();
        return view($this->viewPath . '.create', compact('data', 'accounts'));
    }

    public function store(OpeningBalanceRequest $request)
    {
        $warning = DB::transaction(function () use ($request) {
            $this->objectModel::create($request->validated());
            return $this->syncOpeningEntry();
        });

        return redirect()->route($this->route . '.index')
            ->with('message', 'تم إضافة الرصيد الافتتاحي بنجاح')
            ->with('warning', $warning);
    }

    public function edit($id)
    {
        $data     = $this->objectModel::findOrFail($id);
        $accounts = AccountTree::where('is_active', true)->whereDoesntHave('children')->orderBy('code')->get();
        return view($this->viewPath . '.edit', compact('data', 'accounts'));
    }

    public function update(OpeningBalanceRequest $request)
    {
        $warning = DB::transaction(function () use ($request) {
            $this->objectModel::findOrFail($request->id)->update($request->validated());
            return $this->syncOpeningEntry();
        });

        return redirect()->route($this->route . '.index')
            ->with('message', 'تم تحديث الرصيد الافتتاحي بنجاح')
            ->with('warning', $warning);
    }

    public function show($id)
    {
        $data = $this->objectModel::with('account')->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function destroy(Request $request)
    {
        $ids = (array) ($request->ids ?? $request->id ?? []);

        $warning = DB::transaction(function () use ($ids) {
            $this->objectModel::whereIn('id', $ids)->delete();
            return $this->syncOpeningEntry();
        });

        return response()->json(['success' => true, 'warning' => $warning]);
    }

    public function export(Request $request)
    {
        $query = $this->objectModel::with('account');

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        return (new FastExcel($query->orderBy('date', 'desc')->get()))->download('opening_balances.csv');
    }

    /**
     * قيد افتتاحي واحد يُعاد توليده من كل سطور الأرصدة الافتتاحية.
     * مرحّل إذا توازنت السطور، وإلا مسودة بلا أثر على الأرصدة مع تنبيه بالفارق.
     *
     * @return string|null رسالة التنبيه إن لم تتوازن السطور
     */
    private function syncOpeningEntry(): ?string
    {
        $rows  = $this->objectModel::orderBy('id')->get();
        $entry = JournalEntry::where('reference_type', OpeningBalance::class)->lockForUpdate()->first();

        if ($rows->isEmpty()) {
            if ($entry) {
                $this->journal->deleteDraft($entry);
            }
            return null;
        }

        $debit    = round((float) $rows->sum('debit'), 2);
        $credit   = round((float) $rows->sum('credit'), 2);
        $balanced = $debit === $credit;

        $header = [
            'entry_type'     => 'opening_balance',
            'date'           => $rows->min('date')->toDateString(),
            'description'    => 'قيد الأرصدة الافتتاحية (يُولَّد تلقائياً من شاشة الأرصدة الافتتاحية)',
            'reference_type' => OpeningBalance::class,
            'reference_id'   => null,
            'status'         => $balanced ? 'posted' : 'draft',
        ];

        if ($entry) {
            $entry->update($header);
        } else {
            $entry = JournalEntry::create($header + [
                'entry_number' => $this->journal->nextEntryNumber(),
                'created_by'   => auth()->guard('admin')->id(),
            ]);
        }

        $this->journal->replaceItems($entry, $rows->map(fn ($row) => [
            'account_tree_id' => $row->account_id,
            'debit'           => $row->debit,
            'credit'          => $row->credit,
            'description'     => $row->description,
        ])->all());

        return $balanced ? null : 'الأرصدة الافتتاحية غير متوازنة: المدين ' . number_format($debit, 2)
            . ' والدائن ' . number_format($credit, 2) . ' (الفرق ' . number_format(abs($debit - $credit), 2)
            . '). القيد الافتتاحي ' . $entry->entry_number . ' محفوظ كمسودة ولن يؤثر في الأرصدة حتى تتوازن.';
    }
}
