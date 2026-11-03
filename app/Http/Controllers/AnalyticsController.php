<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        // ----------------------------------------------------
        // 1. ANALITIK BARANG MATI (DEAD STOCK)
        // ----------------------------------------------------
        $daysThreshold = (int) $request->input('dead_stock_days', 60);
        $cutoffDate = Carbon::now()->subDays($daysThreshold);

        $physicalProducts = Product::with('category')
            ->where('product_type', 'physical')
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->get();

        $deadStockList = [];
        $totalDeadCapital = 0;

        foreach ($physicalProducts as $product) {
            $lastSold = TransactionDetail::where('product_id', $product->id)
                ->whereHas('transaction', function ($q) {
                    $q->where('status', 'success');
                })
                ->latest('created_at')
                ->first();

            $isDead = false;
            $daysSinceLastSold = null;

            if ($lastSold) {
                if ($lastSold->created_at < $cutoffDate) {
                    $isDead = true;
                    $daysSinceLastSold = Carbon::parse($lastSold->created_at)->diffInDays(Carbon::now());
                }
            } else {
                if ($product->created_at < $cutoffDate) {
                    $isDead = true;
                    $daysSinceLastSold = Carbon::parse($product->created_at)->diffInDays(Carbon::now());
                }
            }

            if ($isDead) {
                $tiedCapital = $product->stock * $product->cost_price;
                $totalDeadCapital += $tiedCapital;

                $deadStockList[] = [
                    'product' => $product,
                    'stock' => $product->stock,
                    'cost_price' => $product->cost_price,
                    'tied_capital' => $tiedCapital,
                    'days_idle' => $daysSinceLastSold ?? $daysThreshold,
                    'last_sold_date' => $lastSold ? $lastSold->created_at->format('d/m/Y') : 'Belum Pernah'
                ];
            }
        }

        usort($deadStockList, function ($a, $b) {
            return $b['tied_capital'] <=> $a['tied_capital'];
        });

        // ----------------------------------------------------
        // 2. ANALITIK JAM SIBUK TOKO (FLEKSIBEL 24 JAM PENUH)
        // ----------------------------------------------------
        $hourlyPeriodDays = (int) $request->input('peak_period_days', 30);
        $peakStartDate = Carbon::now()->subDays($hourlyPeriodDays)->startOfDay();

        $hourlyTransactions = Transaction::where('status', 'success')
            ->where('created_at', '>=', $peakStartDate)
            ->select(
                DB::raw('HOUR(created_at) as hour_num'),
                DB::raw('COUNT(id) as total_trx'),
                DB::raw('SUM(total_amount) as total_omzet')
            )
            ->groupBy('hour_num')
            ->get()
            ->keyBy('hour_num');

        $hoursLabels = [];
        $trxPerHours = [];
        $omzetPerHours = [];
        $peakHourLabel = '-';
        $maxTrx = 0;

        // Rentang fleksibel 24 Jam (00:00 s/d 23:00)
        for ($h = 0; $h <= 23; $h++) {
            $label = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
            $hoursLabels[] = $label;

            $count = isset($hourlyTransactions[$h]) ? $hourlyTransactions[$h]->total_trx : 0;
            $omzet = isset($hourlyTransactions[$h]) ? (int) $hourlyTransactions[$h]->total_omzet : 0;

            $trxPerHours[] = $count;
            $omzetPerHours[] = $omzet;

            if ($count > $maxTrx) {
                $maxTrx = $count;
                $nextHour = ($h + 1) % 24;
                $peakHourLabel = $label . ' - ' . str_pad($nextHour, 2, '0', STR_PAD_LEFT) . ':00 (' . $count . ' Transaksi)';
            }
        }

        return view('analytics.index', compact(
            'deadStockList',
            'totalDeadCapital',
            'daysThreshold',
            'hoursLabels',
            'trxPerHours',
            'omzetPerHours',
            'peakHourLabel',
            'hourlyPeriodDays'
        ));
    }
}