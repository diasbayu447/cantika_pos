<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function index()
    {
        // Tampilkan riwayat opname terbaru
        $adjustments = StockAdjustment::with(['product', 'user'])->latest()->get();
        return view('stock_adjustments.index', compact('adjustments'));
    }

    public function create()
    {
        // Hanya ambil barang fisik yang berstatus aktif
        $products = Product::where('product_type', 'physical')->where('is_active', true)->get();
        return view('stock_adjustments.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'actual_stock' => 'required|integer|min:0',
            'reason' => 'required|string|max:255',
            'pin' => 'required|string'
        ]);

        // Verifikasi PIN Otorisasi (Supervisor/Owner)
        $supervisor = User::whereIn('role', ['supervisor', 'owner'])
                          ->where('pin', $request->pin)
                          ->where('is_active', true)
                          ->first();

        if (!$supervisor) {
            return back()->withErrors(['pin' => 'PIN otorisasi salah atau Anda tidak memiliki akses!'])->withInput();
        }

        try {
            DB::transaction(function () use ($request, $supervisor) {
                $product = Product::findOrFail($request->product_id);
                $systemStock = $product->stock;
                $difference = $request->actual_stock - $systemStock;

                if ($difference == 0) {
                    throw new \Exception("Stok fisik sama dengan sistem, tidak perlu disesuaikan.");
                }

                // 1. Catat ke tabel Stock Adjustments
                StockAdjustment::create([
                    'product_id' => $product->id,
                    'user_id' => Auth::id(),
                    'system_stock' => $systemStock,
                    'actual_stock' => $request->actual_stock,
                    'difference' => $difference,
                    'reason' => $request->reason,
                    'authorized_by' => $supervisor->id,
                ]);

                // 2. Ubah Master Stok
                $product->update(['stock' => $request->actual_stock]);

                // 3. Catat ke Log Aktivitas
                ActivityLog::record('Stock Opname', "Penyesuaian stok {$product->name} (Selisih: {$difference}) diotorisasi oleh {$supervisor->name}");
            });

            return redirect()->route('stock-adjustments.index')->with('success', 'Stok berhasil disesuaikan dan dicatat!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}