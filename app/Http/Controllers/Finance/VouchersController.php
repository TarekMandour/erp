<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Finance\Voucher;
use App\Models\Finance\Customer;
use App\Models\Finance\Supplier;
use App\Models\Finance\CustomerWallet;
use App\Models\Finance\SupplierWallet;
use App\Models\Finance\AccountTree;
use App\Models\Finance\Treasury;
use App\Models\Finance\BankAccount;
use App\Http\Requests\Finance\VoucherRequest;
use App\Services\Finance\TransactionsService;
use App\Services\Finance\WalletBalanceService;
use Illuminate\Support\Facades\DB;

class VouchersController extends Controller
{
    protected $viewPath  = 'finance.vouchers';
    private $route       = 'finance.vouchers';
    private $objectModel = Voucher::class;

    public function __construct(
        private TransactionsService $transactionsService,
        private WalletBalanceService $walletBalance,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->objectModel::with('creator');

            if ($request->filled('ftype')) {
                $query->where('type', $request->ftype);
            }

            if ($request->filled('fpayment_type')) {
                $query->where('payment_type', $request->fpayment_type);
            }

            if ($request->filled('fparty_type')) {
                $query->where('party_type', $request->fparty_type);
            }

            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            if ($request->filled('search') || $request->filled('fsearch')) {
                $search = $request->search ?? $request->fsearch;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'LIKE', "%{$search}%")
                      ->orWhere('total_amount', 'LIKE', "%{$search}%");
                });
            }

            $query->orderBy('date', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                            </div>';
                })
                ->addColumn('type_badge', function ($row) {
                    $color = $row->type === 'payment' ? 'danger' : 'success';
                    return '<span class="badge badge-light-' . $color . ' fw-bold">' . $row->type_name . '</span>';
                })
                ->addColumn('payment_type_name', function ($row) {
                    return $row->payment_type_name;
                })
                ->addColumn('party_type_name', function ($row) {
                    return $row->party_type_name;
                })
                ->addColumn('total_amount', function ($row) {
                    return '<strong>' . number_format($row->total_amount, 2) . '</strong>';
                })
                ->addColumn('creator_name', function ($row) {
                    return $row->creator ? $row->creator->name : '—';
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route($this->route . '.edit', $row->id) . '" class="btn btn-xs btn-icon btn-primary me-2"><i class="bi bi-pencil-square fs-4"></i></a>';
                    $btn .= '<a href="' . route($this->route . '.show', $row->id) . '" class="btn btn-xs btn-icon btn-info me-2"><i class="bi bi-eye fs-4"></i></a>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'type_badge', 'total_amount', 'action'])
                ->make(true);
        }

        return view($this->viewPath . '.index');
    }

    public function create()
    {
        $data      = new Voucher();
        $customers = Customer::where('account_status', 'active')->orderBy('name')->get();
        $suppliers = Supplier::where('account_status', 'active')->orderBy('name')->get();
        $financialAccounts = $this->getFinancialAccounts();
        $counterAccounts   = $this->getCounterAccounts();

        return view($this->viewPath . '.create', compact(
            'data',
            'customers',
            'suppliers',
            'financialAccounts',
            'counterAccounts'
        ));
    }

    public function store(VoucherRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = Auth::guard('admin')->id();

        try {
            // السند + حركة المحفظة + القيد في معاملة واحدة (QA 2.1 / 2.4): لا سند بلا أثر
            DB::transaction(function () use ($validated) {
                $voucher = $this->objectModel::create($validated);
                $this->createWalletEntry($voucher);
                $this->postJournalEntry($voucher);
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('error', 'تعذّر حفظ السند ولم يُطبَّق أي أثر: ' . $e->getMessage());
        }

        return redirect()->route($this->route . '.index')->with('success', 'تم إضافة السند بنجاح');
    }

    public function edit($id)
    {
        $data      = $this->objectModel::findOrFail($id);
        $customers = Customer::where('account_status', 'active')->orderBy('name')->get();
        $suppliers = Supplier::where('account_status', 'active')->orderBy('name')->get();
        $financialAccounts = $this->getFinancialAccounts();
        $counterAccounts   = $this->getCounterAccounts();

        return view($this->viewPath . '.edit', compact(
            'data',
            'customers',
            'suppliers',
            'financialAccounts',
            'counterAccounts'
        ));
    }

    public function update(VoucherRequest $request)
    {
        try {
            // مسار تنفيذ واحد متزامن (QA 2.4): عكس القيد والمحفظة القديمين ثم إنشاء الجديدين
            DB::transaction(function () use ($request) {
                $voucher = $this->objectModel::lockForUpdate()->findOrFail($request->id);

                $this->deleteWalletEntry($voucher);
                $this->transactionsService->reverse($voucher);

                $voucher->update($request->validated());
                $voucher->refresh();

                $this->createWalletEntry($voucher);
                $this->postJournalEntry($voucher);
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('error', 'تعذّر تحديث السند ولم يتغير أي شيء: ' . $e->getMessage());
        }

        return redirect()->route($this->route . '.index')->with('success', 'تم تحديث السند بنجاح');
    }

    public function show($id)
    {
        $data      = $this->objectModel::with('creator')->findOrFail($id);
        $customers = Customer::orderBy('name')->get()->keyBy('id');
        $suppliers = Supplier::orderBy('name')->get()->keyBy('id');
        return view($this->viewPath . '.show', compact('data', 'customers', 'suppliers'));
    }

    public function destroy(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ((array) ($request->ids ?? $request->id) as $id) {
                    $voucher = $this->objectModel::lockForUpdate()->find($id);
                    if ($voucher) {
                        $this->deleteWalletEntry($voucher);
                        $this->transactionsService->reverse($voucher);
                        $voucher->delete();
                    }
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'تعذّر الحذف: ' . $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $query = $this->objectModel::with('creator');

        if ($request->filled('ftype')) {
            $query->where('type', $request->ftype);
        }
        if ($request->filled('fpayment_type')) {
            $query->where('payment_type', $request->fpayment_type);
        }
        if ($request->filled('fparty_type')) {
            $query->where('party_type', $request->fparty_type);
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        return (new FastExcel($query->orderBy('date', 'desc')->get()))->download('vouchers.csv');
    }

    /**
     * الحسابات المالية المرتبطة فعلياً بخزائن أو حسابات بنكية.
     */
    private function getFinancialAccounts()
    {
        $treasuryAccountIds = Treasury::where('is_active', true)
            ->whereNotNull('account_tree_id')
            ->pluck('account_tree_id');

        $bankAccountIds = BankAccount::whereNotNull('account_tree_id')
            ->pluck('account_tree_id');

        return AccountTree::with('parent')
            ->where('is_active', true)
            ->whereIn(
                'id',
                $treasuryAccountIds->merge($bankAccountIds)->unique()->values()
            )
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * الحسابات المقابلة المتاحة للطرف «أخرى»: حسابات فرعية مفعّلة ليست خزنة أو بنكاً (QA 2.5)
     */
    private function getCounterAccounts()
    {
        $financialIds = Treasury::whereNotNull('account_tree_id')->pluck('account_tree_id')
            ->merge(BankAccount::whereNotNull('account_tree_id')->pluck('account_tree_id'));

        return AccountTree::where('is_active', true)
            ->whereNotIn('id', $financialIds)
            ->whereNotIn('id', AccountTree::whereNotNull('parent_id')->select('parent_id'))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    // ──────────────────────────────────────────────────────────────────
    // Wallet helpers
    // ──────────────────────────────────────────────────────────────────

    /**
     * ترحيل القيد متزامناً؛ لا يُقبل قيد احتياطي (مسودة) لأن معناه عدم وجود سيناريو صالح.
     */
    private function postJournalEntry(Voucher $voucher): void
    {
        $entry = $this->transactionsService->process($voucher);

        if (! $entry || $entry->status !== 'posted') {
            throw new \RuntimeException('لا يوجد سيناريو ترحيل صالح لهذا السند (' . $voucher->getScenarioCode() . ').');
        }
    }

    private function createWalletEntry(Voucher $voucher): void
    {
        if ($voucher->party_type === 'customer' && $voucher->party_id) {
            // محفظة العميل (مدين − دائن) تقابل 1104:
            // قبض من العميل → دائن ، صرف للعميل → مدين
            $isReceipt = $voucher->type === 'receipt';
            CustomerWallet::create([
                'customer_id' => $voucher->party_id,
                'voucher_id'  => $voucher->id,
                'date'        => $voucher->date,
                'description' => $voucher->description ?? $voucher->type_name,
                'debit'       => $isReceipt ? 0 : $voucher->total_amount,
                'credit'      => $isReceipt ? $voucher->total_amount : 0,
                'balance'     => 0,
            ]);
            $this->walletBalance->recalculateCustomer((int) $voucher->party_id);
        }

        if ($voucher->party_type === 'supplier' && $voucher->party_id) {
            // محفظة المورد (دائن − مدين) تقابل 2101 — نفس قاعدة المشتريات (QA 2.3):
            // صرف للمورد → مدين (يقلل ما علينا) ، قبض من المورد → دائن
            $isPayment = $voucher->type === 'payment';
            SupplierWallet::create([
                'supplier_id'      => $voucher->party_id,
                'voucher_id'       => $voucher->id,
                'date'             => $voucher->date,
                'description'      => $voucher->description ?? $voucher->type_name,
                'debit'            => $isPayment ? $voucher->total_amount : 0,
                'credit'           => $isPayment ? 0 : $voucher->total_amount,
                'previous_balance' => 0,
                'balance'          => 0,
            ]);
            $this->walletBalance->recalculateSupplier((int) $voucher->party_id);
        }
    }

    private function deleteWalletEntry(Voucher $voucher): void
    {
        if ($voucher->party_type === 'customer' && $voucher->party_id) {
            CustomerWallet::where('voucher_id', $voucher->id)->delete();
            $this->walletBalance->recalculateCustomer((int) $voucher->party_id);
        }

        if ($voucher->party_type === 'supplier' && $voucher->party_id) {
            SupplierWallet::where('voucher_id', $voucher->id)->delete();
            $this->walletBalance->recalculateSupplier((int) $voucher->party_id);
        }
    }
}
