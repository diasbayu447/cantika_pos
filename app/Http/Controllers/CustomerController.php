<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index()
    {
        // Ambil data pelanggan beserta total belanja dan jumlah transaksi
        $customers = Customer::withCount('transactions')
            ->withSum('transactions as total_spent', 'total_amount')
            ->orderBy('name')
            ->get();

        return view('customers.index', compact('customers'));
    }

    public function show($id)
    {
        $customer = Customer::findOrFail($id);

        // Ambil seluruh riwayat transaksi pelanggan
        $transactions = Transaction::where('customer_id', $id)
            ->with(['details.product', 'user'])
            ->latest()
            ->get();

        $totalSpent = $transactions->sum('total_amount');
        $totalTrx = $transactions->count();

        // Data Histogram Belanja per Bulan (Untuk Chart.js)
        $monthlyData = Transaction::where('customer_id', $id)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%b %Y') as month_year"),
                DB::raw("YEAR(created_at) as year, MONTH(created_at) as month"),
                DB::raw("SUM(total_amount) as total_amount")
            )
            ->groupBy('year', 'month', 'month_year')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->limit(12)
            ->get();

        $chartLabels = $monthlyData->pluck('month_year');
        $chartTotals = $monthlyData->pluck('total_amount');

        return view('customers.show', compact('customer', 'transactions', 'totalSpent', 'totalTrx', 'chartLabels', 'chartTotals'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'nullable|string|max:20',
            'status' => 'required|in:general,registered', // Ganti 'regular' menjadi 'general'
        ]);

        $phone = preg_replace('/[^0-9]/', '', $request->phone ?? '');
        if ($phone) {
            if (str_starts_with($phone, '62')) {
                $phone = substr($phone, 2);
            } elseif (str_starts_with($phone, '0')) {
                $phone = substr($phone, 1);
            }
            $phone = '62' . $phone;
        }

        $customer = Customer::create([
            'name'   => $request->name,
            'phone'  => $phone ? '+' . $phone : null,
            'status' => $request->status,
            'points' => 0
        ]);

        ActivityLog::record('Tambah Pelanggan', "Menambahkan pelanggan baru: {$customer->name}");

        return back()->with('success', 'Pelanggan berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'nullable|string|max:20',
            'status' => 'required|in:general,registered',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $request->phone ?? '');
        if ($phone && str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $customer->update([
            'name'   => $request->name,
            'phone'  => $phone ? '+' . $phone : null,
            'status' => $request->status,
        ]);

        ActivityLog::record('Edit Pelanggan', "Memperbarui data pelanggan: {$customer->name}");

        return back()->with('success', 'Data pelanggan berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);

        if ($customer->transactions()->exists()) {
            return back()->withErrors(['error' => "Pelanggan '{$customer->name}' tidak bisa dihapus karena memiliki riwayat belanja!"]);
        }

        $name = $customer->name;
        $customer->delete();

        ActivityLog::record('Hapus Pelanggan', "Menghapus pelanggan: {$name}");

        return back()->with('success', "Pelanggan '{$name}' berhasil dihapus.");
    }
}