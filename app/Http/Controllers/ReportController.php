<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\CashierShift;
use App\Models\PettyCash;
use App\Models\OperationalExpense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil Periode Filter (Default: Hari Ini)
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        // 2. Ambil Transaksi Berstatus Sukses
        $transactions = Transaction::with(['user', 'customer', 'details.product'])
            ->where('status', 'success')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->latest()
            ->get();

        // 3. Kalkulasi Metrik Finansial Transaksi
        $totalGrossSales = $transactions->sum(function ($trx) {
            return $trx->total_amount + $trx->discount_amount;
        });

        $totalDiscount = $transactions->sum('discount_amount');
        $totalRefund = \App\Models\SaleReturn::where('action_type', 'refund')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->sum('total_refund_amount');
        $totalNetSales = $transactions->sum('total_amount') - $totalRefund;

        // Hitung Total HPP (Harga Modal) dari detail produk terjual
        $totalHpp = $transactions->flatMap->details->sum(function ($detail) {
            return $detail->cost_price * $detail->qty;
        });

        // Laba Kotor (Gross Profit)
        $grossProfit = $totalNetSales - $totalHpp;

        // 4. Pembagian Kas Masuk (Tunai vs Non-Tunai)
        $totalCash = $transactions->where('payment_method', 'cash')->sum('total_amount');
        $totalNonCash = $transactions->where('payment_method', '!=', 'cash')->sum('total_amount');

        // 5. Kas Keluar Kasir (Petty Cash) pada periode ini
        $totalPettyCash = PettyCash::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->sum('amount');

        // 6. Biaya Operasional Toko (Listrik, Wifi, Gaji, Perlengkapan, Sewa)
        $totalOperationalExpense = OperationalExpense::whereDate('expense_date', '>=', $startDate)
            ->whereDate('expense_date', '<=', $endDate)
            ->sum('amount');

        // Total Seluruh Pengeluaran/Beban Toko
        $totalAllExpenses = $totalPettyCash + $totalOperationalExpense;

        // LABA BERSIH RIIL (Net Profit)
        $netProfit = $grossProfit - ($totalPettyCash + $totalOperationalExpense);

        // 7. Rekap Shift & Rekonsiliasi Laci Kasir
        $shifts = CashierShift::with('user')
            ->whereDate('opened_at', '>=', $startDate)
            ->whereDate('opened_at', '<=', $endDate)
            ->latest('opened_at')
            ->get();

        return view('reports.index', compact(
            'startDate',
            'endDate',
            'transactions',
            'totalGrossSales',
            'totalDiscount',
            'totalNetSales',
            'totalHpp',
            'grossProfit',
            'totalCash',
            'totalNonCash',
            'totalPettyCash',
            'totalOperationalExpense',
            'netProfit',
            'shifts'
        ));
    }
}