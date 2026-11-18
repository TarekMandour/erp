<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryItem;
use App\Models\Finance\AccountTree;
use App\Models\Finance\CostCenter;
use App\Http\Requests\Finance\JournalEntryRequest;
use App\Services\Finance\ManualJournalService;
use Illuminate\Support\Facades\DB;

class JournalEntryController extends Controller
{
    protected $viewPath  = 'finance.journal_entries';
    private $route       = 'finance.journal_entries';

    public function __construct(private ManualJournalService $journal)
    {
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = JournalEntry::withSum('items as total_debit', 'debit')
                ->withSum('items as total_credit', 'credit');

            if ($request->filled('entry_type')) {
                $query->where('entry_type', $request->entry_type);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('date', '<=', $request->date_to);
            }
            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where(function ($q) use ($search) {
                    $q->where('entry_number', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            $query->orderBy('date', 'desc')->orderBy('id', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('entry_number_link', function ($row) {
                    return '<a href="' . route($this->route . '.show', $row->id) . '" class="fw-bold text-primary">'
                         . $row->entry_number . '</a>';
                })
                ->addColumn('entry_type_label', function ($row) {
                    $colors = [
                        'sales'      => 'success',
                        'purchase'   => 'primary',
                        'receipt'    => 'info',
                        'payment'    => 'warning',
                        'expense'    => 'danger',
                        'transfer'   => 'dark',
                        'opening'    => 'secondary',
                        'adjustment' => 'light',
                    ];
                    return '<span class="badge bg-light-' . ($colors[$row->entry_type] ?? 'secondary') . '">'
                         . $row->entry_type_label . '</span>';
                })
                ->addColumn('status_label', function ($row) {
                    $colors = ['draft' => 'warning', 'posted' => 'success', 'canceled' => 'danger'];
                    return '<span class="badge bg-light-' . ($colors[$row->status] ?? 'secondary') . '">'
                         . $row->status_label . '</span>';
                })
                ->addColumn('debit_total', function ($row) {
                    return '<strong class="text-danger">' . number_format($row->total_debit ?? 0, 2) . '</strong>';
                })
                ->addColumn('credit_total', function ($row) {
                    return '<strong class="text-success">' . number_format($row->total_credit ?? 0, 2) . '</strong>';
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-1"><i class="bi bi-pencil-square fs-4"></i></a>';
                    $btn .= '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-info me-1"><i class="bi bi-eye fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'entry_number_link', 'entry_type_label', 'status_label', 'debit_total', 'credit_total', 'action'])
                ->make(true);
        }

        return view($this->viewPath . '.index');
    }

    public function create()
    {
        $accounts    = AccountTree::where('is_active', true)->whereDoesntHave('children')->orderBy('code')->get();
        $costCenters = CostCenter::where('is_active', true)->orderBy('name')->get();
        return view($this->viewPath . '.create', compact('accounts', 'costCenters'));
    }

    public function store(JournalEntryRequest $request)
    {
        $data  = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        if ($data['status'] === 'canceled') {
            return back()->withInput()->withErrors(['status' => 'لا يمكن إنشاء قيد بحالة ملغى']);
        }

        $entry = DB::transaction(function () use ($data, $items) {
            $data['entry_number'] = $this->journal->nextEntryNumber();
            $data['created_by']   = auth()->guard('admin')->id();

            $entry = JournalEntry::create($data);
            $this->journal->replaceItems($entry, $items);

            return $entry;
        });

        return redirect()->route($this->route . '.show', $entry->id)
            ->with('message', 'تم إنشاء القيد بنجاح: ' . $entry->entry_number);
    }

    public function edit($id)
    {
        $data        = JournalEntry::with('items')->findOrFail($id);
        $accounts    = AccountTree::where('is_active', true)->whereDoesntHave('children')->orderBy('code')->get();
        $costCenters = CostCenter::where('is_active', true)->orderBy('name')->get();
        return view($this->viewPath . '.edit', compact('data', 'accounts', 'costCenters'));
    }

    public function update(JournalEntryRequest $request)
    {
        $entry = JournalEntry::findOrFail($request->id);

        if ($error = $this->lockedReason($entry)) {
            return back()->withInput()->withErrors(['status' => $error]);
        }

        $data  = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        // إلغاء قيد مرحّل = قيد عكسي، وبنود الأصل لا تتغير
        if ($data['status'] === 'canceled') {
            if ($entry->status !== 'posted') {
                return back()->withInput()->withErrors(['status' => 'المسودة تُحذف ولا تُلغى']);
            }
            $reverse = $this->journal->cancel($entry);

            return redirect()->route($this->route . '.show', $entry->id)
                ->with('message', 'تم إلغاء القيد بالقيد العكسي ' . $reverse->entry_number);
        }

        if ($entry->status === 'posted' && $data['status'] === 'draft') {
            return back()->withInput()->withErrors(['status' => 'لا يمكن إرجاع قيد مرحّل إلى مسودة، ألغِه بدلاً من ذلك']);
        }

        DB::transaction(function () use ($entry, $data, $items) {
            $entry->update($data);
            $this->journal->replaceItems($entry, $items);
        });

        return redirect()->route($this->route . '.show', $entry->id)
            ->with('message', 'تم تحديث القيد بنجاح');
    }

    public function show($id)
    {
        $data = JournalEntry::with(['items.account', 'items.costCenter', 'createdBy', 'approvedBy'])->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    /**
     * الحذف للمسودات فقط؛ القيد المرحّل يُلغى بقيد عكسي من شاشة التعديل
     */
    public function destroy(Request $request)
    {
        $ids     = (array) ($request->ids ?? $request->id ?? []);
        $entries = JournalEntry::whereIn('id', $ids)->get();

        foreach ($entries as $entry) {
            if ($entry->reference_type) {
                return response()->json(['success' => false, 'message' => "القيد {$entry->entry_number} ناتج عن مستند، احذفه من المستند نفسه"], 422);
            }
            if ($entry->status !== 'draft') {
                return response()->json(['success' => false, 'message' => "لا يمكن حذف القيد {$entry->entry_number} لأنه " . $entry->status_label . '؛ ألغِ القيد المرحّل بدلاً من حذفه'], 422);
            }
        }

        DB::transaction(fn () => $entries->each(fn ($entry) => $this->journal->deleteDraft($entry)));

        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $data = JournalEntry::query();

        if ($request->filled('entry_type')) {
            $data->where('entry_type', $request->entry_type);
        }
        if ($request->filled('status')) {
            $data->where('status', $request->status);
        }

        return (new FastExcel($data->orderBy('date', 'desc')->get()))->download('journal_entries.csv');
    }

    /**
     * سبب منع التعديل: القيود الناتجة عن مستند تُعدَّل من مستندها، والملغاة لا تُعدَّل
     */
    private function lockedReason(JournalEntry $entry): ?string
    {
        if ($entry->reference_type) {
            return 'هذا القيد ناتج عن مستند (' . class_basename($entry->reference_type) . ' #' . $entry->reference_id . ')، عدّله من المستند نفسه';
        }
        if ($entry->status === 'canceled') {
            return 'لا يمكن تعديل قيد ملغى';
        }

        return null;
    }
}