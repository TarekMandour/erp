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
use App\Http\Requests\Finance\VoucherRequest;

class VouchersController extends Controller
{
    protected $viewPath  = 'finance.vouchers';
    private $route       = 'finance.vouchers';
    private $objectModel = Voucher::class;

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
        return view($this->viewPath . '.create', compact('data', 'customers', 'suppliers'));
    }

    public function store(VoucherRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = Auth::guard('admin')->id();

        $voucher = $this->objectModel::create($validated);
        $this->createWalletEntry($voucher);

        return redirect()->route($this->route . '.index')->with('success', 'تم إضافة السند بنجاح');
    }

    public function edit($id)
    {
        $data      = $this->objectModel::findOrFail($id);
        $customers = Customer::where('account_status', 'active')->orderBy('name')->get();
        $suppliers = Supplier::where('account_status', 'active')->orderBy('name')->get();
        return view($this->viewPath . '.edit', compact('data', 'customers', 'suppliers'));
    }

    public function update(VoucherRequest $request)
    {
        $voucher = $this->objectModel::findOrFail($request->id);

        $this->deleteWalletEntry($voucher);
        $voucher->update($request->validated());
        $voucher->refresh();
        $this->createWalletEntry($voucher);

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
        foreach ((array) $request->ids as $id) {
            $voucher = $this->objectModel::find($id);
            if ($voucher) {
                $this->deleteWalletEntry($voucher);
                $voucher->delete();
            }
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

    // ──────────────────────────────────────────────────────────────────
    // Wallet helpers
    // ──────────────────────────────────────────────────────────────────

    private function createWalletEntry(Voucher $voucher): void
    {
        if ($voucher->party_type === 'customer' && $voucher->party_id) {
            // receipt (قبض) = customer paid us  → debit
            // payment (صرف) = we paid customer → credit
            $isReceipt = $voucher->type === 'receipt';
            CustomerWallet::create([
                'customer_id' => $voucher->party_id,
                'voucher_id'  => $voucher->id,
                'date'        => $voucher->date,
                'description' => $voucher->description ?? $voucher->type_name,
                'debit'       => $isReceipt ? $voucher->total_amount : 0,
                'credit'      => $isReceipt ? 0 : $voucher->total_amount,
                'balance'     => 0,
            ]);
            $this->recalculateCustomerBalance((int) $voucher->party_id);
        }

        if ($voucher->party_type === 'supplier' && $voucher->party_id) {
            // payment (صرف) = we paid supplier  → debit (reduces what we owe)
            // receipt (قبض) = supplier paid us  → credit
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
            $this->recalculateSupplierBalance((int) $voucher->party_id);
        }
    }

    private function deleteWalletEntry(Voucher $voucher): void
    {
        if ($voucher->party_type === 'customer' && $voucher->party_id) {
            CustomerWallet::where('voucher_id', $voucher->id)->delete();
            $this->recalculateCustomerBalance((int) $voucher->party_id);
        }

        if ($voucher->party_type === 'supplier' && $voucher->party_id) {
            SupplierWallet::where('voucher_id', $voucher->id)->delete();
            $this->recalculateSupplierBalance((int) $voucher->party_id);
        }
    }

    private function recalculateCustomerBalance(int $customerId): void
    {
        $balance = 0;
        foreach (CustomerWallet::where('customer_id', $customerId)->orderBy('id')->get() as $wallet) {
            $balance += $wallet->debit - $wallet->credit;
            $wallet->balance = $balance;
            $wallet->save();
        }
        Customer::where('id', $customerId)->update(['wallet_balance' => $balance]);
    }

    private function recalculateSupplierBalance(int $supplierId): void
    {
        $prev = 0;
        foreach (SupplierWallet::where('supplier_id', $supplierId)->orderBy('id')->get() as $wallet) {
            $balance = $prev + $wallet->debit - $wallet->credit;
            $wallet->previous_balance = $prev;
            $wallet->balance          = $balance;
            $wallet->save();
            $prev = $balance;
        }
        Supplier::where('id', $supplierId)->update(['wallet_balance' => $prev]);
    }
}
