<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Finance\Customer;
use App\Models\Finance\Order;
use App\Models\Finance\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $year  = $request->input('year', now()->year);
        $from  = $request->input('from');
        $to    = $request->input('to');

        // ── KPI cards (respect date filter if provided) ───────────────────
        $orderQuery    = Order::query();
        $purchaseQuery = Purchase::query();

        if ($from && $to) {
            $orderQuery->whereBetween('date', [$from, $to]);
            $purchaseQuery->whereBetween('date', [$from, $to]);
        } else {
            $orderQuery->whereYear('date', $year);
            $purchaseQuery->whereYear('date', $year);
        }

        $totalRevenue   = (clone $orderQuery)->sum('total');
        $totalOrders    = (clone $orderQuery)->count();
        $totalPaid      = (clone $orderQuery)->sum('paid');
        $totalRemaining = $totalRevenue - $totalPaid;

        $totalPurchases    = (clone $purchaseQuery)->sum('total');
        $totalPurchasesPaid = (clone $purchaseQuery)->sum('paid');

        $totalCustomers = Customer::count();
        $newCustomers   = Customer::whereYear('created_at', $year)->count();

        // ── Monthly revenue & orders for the selected year ────────────────
        $monthlyData = Order::selectRaw('MONTH(date) as month, SUM(total) as revenue, COUNT(*) as orders_count')
            ->whereYear('date', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $monthlyPurchases = Purchase::selectRaw('MONTH(date) as month, SUM(total) as total')
            ->whereYear('date', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months         = range(1, 12);
        $revenueChart   = [];
        $ordersChart    = [];
        $purchasesChart = [];

        foreach ($months as $m) {
            $revenueChart[]   = $monthlyData->has($m)   ? (float) $monthlyData[$m]->revenue      : 0;
            $ordersChart[]    = $monthlyData->has($m)   ? (int)   $monthlyData[$m]->orders_count  : 0;
            $purchasesChart[] = $monthlyPurchases->has($m) ? (float) $monthlyPurchases[$m]->total : 0;
        }

        // ── Top 5 customers by revenue ────────────────────────────────────
        $topCustomers = Order::selectRaw('customer_id, SUM(total) as total_revenue, COUNT(*) as orders_count')
            ->whereYear('date', $year)
            ->with('customer:id,name')
            ->groupBy('customer_id')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        // ── Orders by status ──────────────────────────────────────────────
        $ordersByStatus = Order::selectRaw('status, COUNT(*) as cnt')
            ->whereYear('date', $year)
            ->groupBy('status')
            ->pluck('cnt', 'status');

        // ── Available years ───────────────────────────────────────────────
        $years = Order::selectRaw('YEAR(date) as y')->distinct()->orderByDesc('y')->pluck('y')->toArray();
        if (empty($years)) {
            $years = [now()->year];
        }

        return view('admin.dashboard', compact(
            'year', 'from', 'to',
            'totalRevenue', 'totalOrders', 'totalPaid', 'totalRemaining',
            'totalPurchases', 'totalPurchasesPaid',
            'totalCustomers', 'newCustomers',
            'revenueChart', 'ordersChart', 'purchasesChart',
            'topCustomers', 'ordersByStatus', 'years'
        ));
    }
}
