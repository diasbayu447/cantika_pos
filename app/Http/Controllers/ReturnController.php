<?php

namespace App\Http\Controllers;

use App\Models\SaleReturn;
use App\Models\SaleReturnDetail;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Models\Transaction;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReturnController extends Controller
{
    // 1. Halaman Riwayat Retur
    public function index()
    {
        $saleReturns = SaleReturn::with(['transaction', 'user', 'details.product'])->latest()->get();
        $purchaseReturns = PurchaseReturn::with(['supplier', 'user', 'details.product'])->latest()->get();

        return view('returns.index', compact('saleReturns', 'purchaseReturns'));
    }

    // 2. Form Retur Penjualan (Dari Pelanggan)
    public function createSaleReturn()
    {
        return view('returns.create_sale');
    }

    // API Cari Transaksi Berdasarkan No. Faktur
    public function searchTransaction(Request $request)
    {
        $invoice = trim($request->invoice_number);
        $transaction = Transaction::with(['customer', 'details.product'])
            ->where('invoice_number', $invoice)
            ->where('status', 'success')
            ->first();

        if (!$transaction) {
            return response()->json(['error' => 'Faktur transaksi tidak ditemukan atau belum selesai!'], 404);
        }

        return response()->json($transaction);
    }

    // Simpan Retur Penjualan
    public function storeSaleReturn(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'action_type' => 'required|in:refund,replace',
            'reason' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price_per_item' => 'required|integer|min:0',
            'items.*.replacement_product_id' => 'nullable|exists:products,id',
        ]);

        DB::beginTransaction();
        try {
            $today = date('Ymd');
            $countToday = SaleReturn::whereDate('created_at', today())->count() + 1;
            $returnNumber = 'RET-JUAL-' . $today . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

            $totalRefund = 0;
            if ($request->action_type === 'refund') {
                foreach ($request->items as $item) {
                    $totalRefund += $item['qty'] * $item['price_per_item'];
                }
            }

            $saleReturn = SaleReturn::create([
                'return_number' => $returnNumber,
                'transaction_id' => $request->transaction_id,
                'user_id' => Auth::id(),
                'action_type' => $request->action_type,
                'total_refund_amount' => $totalRefund,
                'reason' => $request->reason,
            ]);

            foreach ($request->items as $item) {
                $subtotal = $item['qty'] * $item['price_per_item'];

                SaleReturnDetail::create([
                    'sale_return_id' => $saleReturn->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price_per_item' => $item['price_per_item'],
                    'subtotal' => $subtotal,
                    'replacement_product_id' => $request->action_type === 'replace' ? ($item['replacement_product_id'] ?? $item['product_id']) : null,
                ]);

                $product = Product::lockForUpdate()->find($item['product_id']);

                // Refund tunai: barang kembali ke toko, stok harus bertambah.
                if ($request->action_type === 'refund') {
                    if ($product && $product->product_type === 'physical') {
                        $product->increment('stock', $item['qty']);
                    }
                    continue;
                }

                // Tukar barang sejenis: unit retur dianggap tidak dijual lagi, stok pengganti berkurang.
                $replaceId = $item['replacement_product_id'] ?? $item['product_id'];
                $replacingProduct = $replaceId == $item['product_id']
                    ? $product
                    : Product::lockForUpdate()->find($replaceId);
                if ($replacingProduct && $replacingProduct->product_type === 'physical') {
                    $replacingProduct->decrement('stock', $item['qty']);
                }
            }

            DB::commit();

            ActivityLog::record('Retur Penjualan', "Memproses retur {$returnNumber} untuk transaksi {$saleReturn->transaction->invoice_number}");
            return redirect()->route('returns.index')->with('success', "Retur Penjualan {$returnNumber} berhasil diproses.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal memproses retur: ' . $e->getMessage()])->withInput();
        }
    }

    // 3. Form Retur Pembelian (Ke Supplier)
    public function createPurchaseReturn()
    {
        $suppliers = Supplier::where('is_active', true)->get();
        $products = Product::where('product_type', 'physical')->where('is_active', true)->get();

        return view('returns.create_purchase', compact('suppliers', 'products'));
    }

    // Simpan Retur Pembelian
    public function storePurchaseReturn(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'reason' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $today = date('Ymd');
            $countToday = PurchaseReturn::whereDate('created_at', today())->count() + 1;
            $returnNumber = 'RET-BELI-' . $today . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += $item['qty'] * $item['cost_price'];
            }

            $purchaseReturn = PurchaseReturn::create([
                'return_number' => $returnNumber,
                'supplier_id' => $request->supplier_id,
                'user_id' => Auth::id(),
                'total_amount' => $totalAmount,
                'reason' => $request->reason,
            ]);

            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);
                if ($product && $product->product_type === 'physical') {
                    if ($product->stock < $item['qty']) {
                        throw new \Exception("Jumlah retur untuk {$product->name} ({$item['qty']} pcs) melebihi stok yang ada ({$product->stock} pcs)!");
                    }
                    $product->decrement('stock', $item['qty']);
                }
                
                $subtotal = $item['qty'] * $item['cost_price'];

                PurchaseReturnDetail::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'cost_price' => $item['cost_price'],
                    'subtotal' => $subtotal,
                ]);

                // Kurangi stok toko karena barang dikembalikan ke supplier
                $product = Product::find($item['product_id']);
                if ($product && $product->product_type === 'physical') {
                    $product->decrement('stock', $item['qty']);
                }
            }

            DB::commit();

            ActivityLog::record('Retur Pembelian', "Memproses retur barang ke supplier {$returnNumber}");
            return redirect()->route('returns.index')->with('success', "Retur Pembelian {$returnNumber} berhasil dicatat.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal memproses retur: ' . $e->getMessage()])->withInput();
        }
    }
}