<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Product;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // 1. Metrik Finansial Hari Ini
        $todayTransactions = Transaction::where('status', 'success')
            ->whereDate('created_at', $today)
            ->with('details')
            ->get();

        $todaySales = $todayTransactions->sum('total_amount');
        $todayTrxCount = $todayTransactions->count();

        $todayHpp = $todayTransactions->flatMap->details->sum(function ($detail) {
            return $detail->cost_price * $detail->qty;
        });
        $todayGrossProfit = $todaySales - $todayHpp;

        // 2. Peringatan Stok Kritis (Fisik, aktif, stok <= min_stock_alert)
        $lowStockProducts = Product::where('product_type', 'physical')
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock_alert')
            ->orderBy('stock', 'asc')
            ->limit(5)
            ->get();

        $lowStockCount = Product::where('product_type', 'physical')
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock_alert')
            ->count();

        // 3. Data Tren Omzet 7 Hari Terakhir untuk Chart.js
        $sevenDaysAgo = Carbon::today()->subDays(6);
        $dailySales = Transaction::where('status', 'success')
            ->whereDate('created_at', '>=', $sevenDaysAgo)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('date')
            ->pluck('total', 'date');

        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateObj = Carbon::today()->subDays($i);
            $dateStr = $dateObj->toDateString();
            $chartLabels[] = $dateObj->format('d M');
            $chartData[] = (int) ($dailySales[$dateStr] ?? 0);
        }

        // 4. Top 5 Produk Terlaris Sepanjang Masa
        $topProducts = TransactionDetail::select('product_id', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(subtotal) as total_sales'))
            ->whereHas('transaction', function ($q) {
                $q->where('status', 'success');
            })
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // 5. Transaksi Kasir Terbaru (5 Transaksi Terakhir)
        $latestTransactions = Transaction::with(['user', 'customer'])
            ->where('status', 'success')
            ->latest()
            ->limit(5)
            ->get();

        // 6. Pengecekan Faktur Tempo yang Jatuh Tempo / Menunggak
        $overduePurchases = Purchase::with('supplier')
            ->where('payment_method', 'tempo')
            ->where('payment_status', 'unpaid')
            ->whereDate('due_date', '<=', $today)
            ->orderBy('due_date', 'asc')
            ->get();

        return view('dashboard', compact(
            'todaySales',
            'todayTrxCount',
            'todayGrossProfit',
            'lowStockProducts',
            'lowStockCount',
            'chartLabels',
            'chartData',
            'topProducts',
            'latestTransactions',
            'overduePurchases'
        ));
    }
}