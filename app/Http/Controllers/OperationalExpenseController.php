<?php

namespace App\Http\Controllers;

use App\Models\OperationalExpense;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperationalExpenseController extends Controller
{
    public function index()
    {
        $expenses = OperationalExpense::with('user')->latest('expense_date')->get();
        $totalExpense = $expenses->sum('amount');

        return view('expenses.index', compact('expenses', 'totalExpense'));
    }

    public function store(Request $request)
    {
        // Bersihkan format titik ribuan sebelum masuk ke validasi integer
        $cleanAmount = (int) preg_replace('/[^0-9]/', '', $request->amount ?? 0);
        $request->merge(['amount' => $cleanAmount]);

        $validated = $request->validate([
            'expense_date' => 'required|date',
            'category' => 'required|string|max:50',
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = Auth::id();

        $expense = OperationalExpense::create($validated);

        ActivityLog::record(
            'Biaya Operasional', 
            "Mencatat pengeluaran {$expense->category} senilai Rp " . number_format($expense->amount, 0, ',', '.')
        );

        return back()->with('success', "Biaya operasional '{$expense->category}' berhasil dicatat.");
    }

    public function destroy($id)
    {
        $expense = OperationalExpense::findOrFail($id);
        $cat = $expense->category;
        $amt = $expense->amount;
        $expense->delete();

        ActivityLog::record(
            'Hapus Biaya Operasional', 
            "Menghapus catatan biaya {$cat} senilai Rp " . number_format($amt, 0, ',', '.')
        );

        return back()->with('success', "Catatan pengeluaran berhasil dihapus.");
    }
}