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

class JournalEntryController extends Controller
{
    protected $viewPath  = 'finance.journal_entries';
    private $route       = 'finance.journal_entries';

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
        $accounts    = AccountTree::where('is_active', true)->orderBy('code')->get();
        $costCenters = CostCenter::where('is_active', true)->orderBy('name')->get();
        return view($this->viewPath . '.create', compact('accounts', 'costCenters'));
    }

    public function store(JournalEntryRequest $request)
    {
        $data               = $request->validated();
        $data['entry_number'] = $this->generateEntryNumber();
        $data['created_by']   = auth()->guard('admin')->id();
        $items              = $data['items'];
        unset($data['items']);

        $entry = JournalEntry::create($data);

        foreach ($items as $item) {
            $entry->items()->create([
                'account_tree_id' => $item['account_tree_id'],
                'debit'           => $item['debit'] ?? 0,
                'credit'          => $item['credit'] ?? 0,
                'cost_center_id'  => $item['cost_center_id'] ?? null,
                'description'     => $item['description'] ?? null,
                'created_at'      => now(),
            ]);
        }

        $this->recalculateBalances($entry);

        return redirect()->route($this->route . '.show', $entry->id)
            ->with('success', 'تم إنشاء القيد بنجاح: ' . $entry->entry_number);
    }

    public function edit($id)
    {
        $data        = JournalEntry::with('items')->findOrFail($id);
        $accounts    = AccountTree::where('is_active', true)->orderBy('code')->get();
        $costCenters = CostCenter::where('is_active', true)->orderBy('name')->get();
        return view($this->viewPath . '.edit', compact('data', 'accounts', 'costCenters'));
    }

    public function update(JournalEntryRequest $request)
    {
        $entry         = JournalEntry::findOrFail($request->id);
        $oldAccountIds = $entry->items()->pluck('account_tree_id')->toArray();

        $data  = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        $entry->update($data);
        $entry->items()->each(fn($item) => $item->delete());

        foreach ($items as $item) {
            $entry->items()->create([
                'account_tree_id' => $item['account_tree_id'],
                'debit'           => $item['debit'] ?? 0,
                'credit'          => $item['credit'] ?? 0,
                'cost_center_id'  => $item['cost_center_id'] ?? null,
                'description'     => $item['description'] ?? null,
                'created_at'      => now(),
            ]);
        }

        $newAccountIds = $entry->items()->pluck('account_tree_id')->toArray();
        $allAccountIds = array_unique(array_merge($oldAccountIds, $newAccountIds));
        foreach ($allAccountIds as $accountId) {
            $this->updateAccountBalance($accountId);
        }

        return redirect()->route($this->route . '.show', $entry->id)
            ->with('success', 'تم تحديث القيد بنجاح');
    }

    public function show($id)
    {
        $data = JournalEntry::with(['items.account', 'items.costCenter', 'createdBy', 'approvedBy'])->findOrFail($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function destroy(Request $request)
{
    $entries = JournalEntry::with('items')->whereIn('id', $request->ids)->get();

        foreach ($entries as $entry) {
            $entry->items()->each(fn($item) => $item->delete());
            $entry->delete();
        }

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

    private function recalculateBalances(JournalEntry $entry): void
    {
        $accountIds = $entry->items()->pluck('account_tree_id')->unique()->toArray();
        foreach ($accountIds as $accountId) {
            $this->updateAccountBalance($accountId);
        }
    }

    private function updateAccountBalance(int $accountId): void
    {
        $account = AccountTree::find($accountId);
        if (!$account) return;

        $totals = JournalEntryItem::where('account_tree_id', $accountId)
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $totalDebit  = $totals->total_debit  ?? 0;
        $totalCredit = $totals->total_credit ?? 0;

        $account->update([
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'balance'      => $totalDebit - $totalCredit,
        ]);
    }

    private function generateEntryNumber(): string
    {
        $year   = now()->year;
        $prefix = 'JE-' . $year . '-';

        $last = JournalEntry::where('entry_number', 'LIKE', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(entry_number, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->value('entry_number');

        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}