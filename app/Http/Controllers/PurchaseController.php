<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\PriceChangeHistory;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with(['supplier', 'user', 'details.product'])
            ->latest()
            ->get();

        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::where('product_type', 'physical')->where('is_active', true)->orderBy('name')->get();

        // Generate Nomor Faktur Berurutan Hari Ini: FCT-YYYYMMDD-0001
        $todayPrefix = 'FCT-' . date('Ymd') . '-';
        $lastPurchaseToday = Purchase::where('invoice_number', 'LIKE', $todayPrefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPurchaseToday && preg_match('/-(\d+)$/', $lastPurchaseToday->invoice_number, $matches)) {
            $nextSeq = intval($matches[1]) + 1;
        } else {
            $nextSeq = Purchase::whereDate('created_at', today())->count() + 1;
        }

        $nextInvoiceNumber = $todayPrefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        return view('purchases.create', compact('suppliers', 'products', 'nextInvoiceNumber'));
    }

    public function store(Request $request)
    {
        // JEMBATAN OTOMATIS: Baik form mengirim 'items' maupun 'products', keduanya langsung diterima!
        if ($request->has('products') && !$request->has('items')) {
            $request->merge(['items' => $request->products]);
        }

        // Bersihkan format rupiah pada cost_price jika masih ada yang lolos
        if ($request->has('items') && is_array($request->items)) {
            $cleanedItems = [];
            foreach ($request->items as $idx => $item) {
                $item['cost_price'] = (int) preg_replace('/[^0-9]/', '', $item['cost_price'] ?? 0);
                $item['qty'] = (int) preg_replace('/[^0-9]/', '', $item['qty'] ?? 0);
                $cleanedItems[$idx] = $item;
            }
            $request->merge(['items' => $cleanedItems]);
        }

        $request->validate([
            'invoice_number' => 'required|string|unique:purchases,invoice_number',
            'supplier_id'    => 'required|exists:suppliers,id',
            'purchase_date'  => 'required|date',
            'payment_method' => 'required|in:cash,credit,tempo',
            'due_date'       => 'nullable|date',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
        ]);

        $totalAmount = 0;
        foreach ($request->items as $item) {
            $totalAmount += ($item['qty'] * $item['cost_price']);
        }

        $isCredit = in_array($request->payment_method, ['credit', 'tempo']);
        $changedPriceItems = []; // Menampung barang yang HPP-nya berubah

        DB::transaction(function () use ($request, $totalAmount, $isCredit, &$changedPriceItems) {
            $purchaseData = [
                'invoice_number' => $request->invoice_number,
                'supplier_id'    => $request->supplier_id,
                'user_id'        => Auth::id(),
                'purchase_date'  => $request->purchase_date,
                'total_amount'   => $totalAmount,
                'payment_method' => $isCredit ? 'tempo' : 'cash',
                'payment_status' => $isCredit ? 'unpaid' : 'paid',
                'due_date'       => $isCredit ? $request->due_date : null,
                'paid_at'        => $isCredit ? null : now(),
            ];

            $validColumns = Schema::getColumnListing('purchases');
            $filteredPurchaseData = array_intersect_key($purchaseData, array_flip($validColumns));
            $purchase = Purchase::create($filteredPurchaseData);

            foreach ($request->items as $item) {
                $subtotal = $item['qty'] * $item['cost_price'];

                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $item['product_id'],
                    'qty'         => $item['qty'],
                    'cost_price'  => $item['cost_price'],
                    'subtotal'    => $subtotal,
                ]);

                // Update stok & deteksi perubahan HPP (Harga Modal)
                $product = Product::find($item['product_id']);
                if ($product) {
                    $oldCost = (int) $product->cost_price;
                    $newCost = (int) $item['cost_price'];

                    $product->stock += $item['qty'];

                    // CROSSCHECK: Apakah HPP berubah?
                    if ($oldCost !== $newCost) {
                        $selisih = $newCost - $oldCost;
                        $tipePerubahan = $newCost > $oldCost ? 'Naik' : 'Turun';

                        $changedPriceItems[] = [
                            'id'              => $product->id,
                            'name'            => $product->name,
                            'old_cost'        => $oldCost,
                            'new_cost'        => $newCost,
                            'type'            => $tipePerubahan,
                            'diff'            => abs($selisih),
                            'retail_price'    => $product->retail_price,
                            'wholesale_price' => $product->wholesale_price ?? 0,
                            'has_wholesale'   => ($product->wholesale_price > 0)
                        ];

                        // Catat ke tabel riwayat perubahan harga
                        if (class_exists(PriceChangeHistory::class)) {
                            $historyCols = Schema::getColumnListing('price_change_histories');
                            $historyData = [
                                'product_id'          => $product->id,
                                'purchase_id'         => $purchase->id, // <--- TAMBAHKAN INI
                                'user_id'             => Auth::id(),
                                'old_price'           => $oldCost,
                                'new_price'           => $newCost,
                                'old_retail_price'    => $product->retail_price,
                                'new_retail_price'    => $product->retail_price,
                                'old_wholesale_price' => $product->wholesale_price ?? 0,
                                'new_wholesale_price' => $product->wholesale_price ?? 0,
                            ];
                            PriceChangeHistory::create(array_intersect_key($historyData, array_flip($historyCols)));
                        }

                        // Perbarui HPP acuan pada produk
                        $product->cost_price = $newCost;
                    }

                    $product->save();
                }
            }

            if (class_exists(ActivityLog::class)) {
                ActivityLog::record('Kulakan Barang', "Menginput faktur kulakan {$purchase->invoice_number} senilai Rp " . number_format($totalAmount, 0, ',', '.'));
            }
        });

        // Pesan Sukses Dinamis
        $message = 'Faktur kulakan berhasil disimpan dan stok gudang telah ditambahkan!';
        if (!empty($changedPriceItems)) {
            $daftarBarang = collect($changedPriceItems)->map(function($i) {
                return "{$i['name']} (HPP {$i['type']} jadi Rp " . number_format($i['new_cost'], 0, ',', '.') . ")";
            })->join(', ');
            
            return redirect()->route('purchases.index')
                ->with('success', $message)
                ->with('price_changes', $changedPriceItems) // <--- PASTIKAN BARIS INI ADA & BERNAMA price_changes
                ->with('price_alert', "Perhatian: Terdapat perubahan HPP pada barang: {$daftarBarang}. Silakan tinjau harga jual jika diperlukan.");
        }

        return redirect()->route('purchases.index')->with('success', $message);
    }

    public function debts()
    {
        // 1. Ambil faktur tempo yang BELUM LUNAS
        $unpaidPurchases = Purchase::with('supplier')
            ->where(function ($q) {
                $q->where('payment_status', 'unpaid')
                  ->orWhere(function ($sub) {
                      $sub->whereIn('payment_method', ['tempo', 'credit'])
                          ->whereNull('paid_at');
                  });
            })
            ->where('payment_status', '!=', 'paid')
            ->latest()
            ->get();

        // 2. Ambil faktur tempo yang SUDAH LUNAS
        $paidPurchases = Purchase::with('supplier')
            ->where(function ($q) {
                $q->where('payment_status', 'paid')
                  ->orWhereNotNull('paid_at');
            })
            ->whereIn('payment_method', ['tempo', 'credit'])
            ->latest()
            ->get();

        // 3. Hitung total nominal hutang yang masih berjalan
        $totalUnpaidDebt = $unpaidPurchases->sum('total_amount');

        return view('purchases.debts', compact('unpaidPurchases', 'paidPurchases', 'totalUnpaidDebt'));
    }

    public function payDebt($id)
    {
        $purchase = Purchase::findOrFail($id);
        $cols = Schema::getColumnListing('purchases');

        if (in_array('payment_status', $cols)) {
            $purchase->payment_status = 'paid';
        }
        if (in_array('paid_at', $cols)) {
            $purchase->paid_at = now();
        }
        $purchase->save();

        if (class_exists(ActivityLog::class)) {
            ActivityLog::record('Pelunasan Hutang', "Melunasi tagihan faktur kulakan {$purchase->invoice_number}");
        }

        return back()->with('success', "Tagihan faktur {$purchase->invoice_number} berhasil ditandai LUNAS!");
    }

    // --- FITUR QUICK ADD DARI MODAL KULAKAN --- //

    public function quickSupplier(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);

        $phone = preg_replace('/[^0-9]/', '', $request->phone ?? '');
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }

        $cols = Schema::getColumnListing('suppliers');
        $data = [
            'name'           => $request->name,
            'pic'            => $request->pic,
            'contact_person' => $request->pic,
            'phone'          => $phone ? '+' . $phone : '+62000000000',
            'address'        => $request->address,
        ];

        $supplier = Supplier::create(array_intersect_key($data, array_flip($cols)));

        return response()->json(['success' => true, 'supplier' => $supplier]);
    }

    public function quickProduct(Request $request)
    {
        $request->validate([
            'name'         => 'required|string',
            'category_id'  => 'required',
            'cost_price'   => 'required|numeric',
            'retail_price' => 'required|numeric',
        ]);

        $cols = Schema::getColumnListing('products');
        $data = [
            'name'                => $request->name,
            'brand'               => $request->brand,
            'category_id'         => $request->category_id,
            'product_type'        => 'physical',
            'barcode'             => $request->barcode ?: null,
            'rack'                => $request->rack,
            'rack_location'       => $request->rack,
            'cost_price'          => $request->cost_price,
            'retail_price'        => $request->retail_price,
            'wholesale_price'     => $request->wholesale_price ?: 0,
            'min_wholesale_qty'   => $request->min_wholesale_qty ?: 0,
            'min_stock_alert'     => $request->low_stock_threshold ?: 5,
            'stock'               => 0,
            'is_active'           => true,
        ];

        $product = Product::create(array_intersect_key($data, array_flip($cols)));

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function show($id)
    {
        // Panggil data faktur beserta supplier, admin input, dan rincian barangnya
        $purchase = \App\Models\Purchase::with([
            'supplier', 
            'user', 
            'details.product' // jika relasi Anda bernama purchaseDetails, ganti menjadi 'purchaseDetails.product'
        ])->findOrFail($id);

        return response()->json($purchase);
    }
    
    public function searchProduct(Request $request)
    {
        $keyword = $request->keyword;
        $products = Product::where('is_active', true)
            ->where('product_type', 'physical')
            ->where(function ($q) use ($keyword) {
                $q->where('barcode', 'LIKE', "%{$keyword}%")
                  ->orWhere('name', 'LIKE', "%{$keyword}%");
            })
            ->limit(20)
            ->get();

        return response()->json($products);
    }
}