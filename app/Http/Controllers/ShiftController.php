<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Transaction;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    // Tampilkan Form Buka Shift
    public function create()
    {
        // 1. Jika kasir SUDAH punya shift terbuka, dilarang keras melihat halaman ini, langsung lempar ke POS!
        $activeShift = \App\Models\CashierShift::where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();

        if ($activeShift) {
            return redirect()->route('pos.index');
        }

        // 2. Matikan BFcache browser agar halaman ini tidak pernah disimpan di memori tombol Back
        return response()
            ->view('shift.create') // sesuaikan dengan nama view buka shift kamu
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    // Simpan Uang Modal Awal
    public function store(Request $request)
    {
        $request->validate([
            'start_cash' => 'required|integer|min:0',
        ]);

        // Buat Shift Baru
        CashierShift::create([
            'user_id' => Auth::id(),
            'start_cash' => $request->start_cash,
            'status' => 'open',
        ]);

        // Catat ke Log
        ActivityLog::record('Buka Shift', 'Kasir memasukkan modal awal laci sebesar Rp ' . number_format($request->start_cash, 0, ',', '.'));

        return redirect()->route('pos.index')->with('success', 'Shift berhasil dibuka! Selamat bertugas.');
    }

    // Catat Pengeluaran Kasir Kecil (Petty Cash) via AJAX
    public function storePettyCash(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'required|string|max:255',
        ]);

        $activeShift = CashierShift::where('user_id', Auth::id())->where('status', 'open')->first();
        
        if ($activeShift) {
            $activeShift->pettyCashes()->create([
                'amount' => $request->amount,
                'description' => $request->description,
            ]);
            
            ActivityLog::record('Petty Cash', 'Kas keluar: Rp ' . number_format($request->amount, 0, ',', '.') . ' (' . $request->description . ')');
            
            return response()->json(['success' => 'Pengeluaran kas kecil berhasil dicatat!']);
        }
        
        return response()->json(['error' => 'Tidak ada shift aktif!'], 400);
    }

    public function summary()
    {
        $activeShift = \App\Models\CashierShift::with('pettyCashes')
            ->where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->where('status', 'open')
            ->first();
        
        if (!$activeShift) {
            return response()->json(['error' => 'Tidak ada shift aktif'], 400);
        }

        // Uang masuk dari penjualan tunai murni
        $cashSales = \App\Models\Transaction::where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->where('payment_method', 'cash')
            ->where('created_at', '>=', $activeShift->opened_at)
            ->sum('total_amount');
        
        // Retur/Refund (jika ada)
        $totalRefund = 0;
        if (class_exists(\App\Models\SaleReturn::class)) {
            $totalRefund = \App\Models\SaleReturn::where('user_id', \Illuminate\Support\Facades\Auth::id())
                ->where('action_type', 'refund')
                ->where('created_at', '>=', $activeShift->opened_at)
                ->sum('total_refund_amount');
        }

        $pettyCashes = $activeShift->pettyCashes;
        $pettyTotal = $pettyCashes->sum('amount');

        // Rumus matematika laci fisik
        $systemCash = $activeShift->start_cash + $cashSales - $pettyTotal - $totalRefund;

        return response()->json([
            'start_cash' => $activeShift->start_cash,
            'cash_sales' => $cashSales,
            'refunds' => $totalRefund,
            'petty_total' => $pettyTotal,
            'petty_details' => $pettyCashes,
            'system_cash' => $systemCash
        ]);
    }
    
    // Tutup Shift dan Hitung Selisih Uang Laci
    public function closeShift(Request $request)
    {
        $request->validate([
            'end_cash_actual' => 'required|integer|min:0',
        ]);

        $activeShift = CashierShift::where('user_id', Auth::id())->where('status', 'open')->first();
        
        if ($activeShift) {
            // Hitung total penjualan tunai murni (omzet cash) selama shift ini
            $cashSales = Transaction::where('user_id', Auth::id())
                            ->where('payment_method', 'cash')
                            ->where('created_at', '>=', $activeShift->opened_at)
                            ->sum('total_amount');
            
            // Hitung total kas keluar (petty cash)
            $pettyCashTotal = $activeShift->pettyCashes()->sum('amount');
            
            // Hitung total refund tunai ke pelanggan selama shift ini
            $totalRefund = \App\Models\SaleReturn::where('user_id', Auth::id())
                                ->where('action_type', 'refund')
                                ->where('created_at', '>=', $activeShift->opened_at)
                                ->sum('total_refund_amount');

            // Rumus Uang Sistem:
            $endCashSystem = $activeShift->start_cash + $cashSales - $pettyCashTotal - $totalRefund;
            
            // Hitung selisih (Minus atau Lebih)
            $difference = $request->end_cash_actual - $endCashSystem;

            // Kunci Shift
            $activeShift->update([
                'end_cash_system' => $endCashSystem,
                'end_cash_actual' => $request->end_cash_actual,
                'difference' => $difference,
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            ActivityLog::record('Tutup Shift', 'Kasir menutup shift. Selisih fisik vs sistem: Rp ' . number_format($difference, 0, ',', '.'));
            
            // Otomatis Logout setelah tutup shift
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect('/')->withErrors(['username' => 'Shift berhasil ditutup dan uang tersimpan. Silakan login kembali untuk memulai shift baru.']);
        }

        return redirect('/');
    }
}