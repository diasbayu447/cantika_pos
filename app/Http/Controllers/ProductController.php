<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Models\PriceChangeHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->get();
        $categories = Category::orderBy('name')->get();
        $brands = Product::whereNotNull('brand')->where('brand', '!=', '')->distinct()->pluck('brand');

        return view('products.index', compact('products', 'categories', 'brands'));
    }

    private function generateInternalBarcode($type = 'physical', $catId = 1)
    {
        $typeMap = [
            'physical'           => 1, 
            'digital'            => 2, 
            'digital_prabayar'   => 2, 
            'digital_pascabayar' => 2, 
            'service'            => 3
        ];
        $typeId = $typeMap[$type] ?? 1;
        $brandId = 0; // Default brand ID internal

        $nextProductId = (Product::max('id') ?? 0) + 1;
        $generatedCode = sprintf("%d%02d%02d%04d", $typeId, $catId, $brandId, $nextProductId);
        
        while (Product::where('barcode', $generatedCode)->exists()) {
            $nextProductId++;
            $generatedCode = sprintf("%d%02d%02d%04d", $typeId, $catId, $brandId, $nextProductId);
        }
        return $generatedCode;
    }

    private function sanitizeAndValidateProduct(Request $request, $isQuick = false)
    {
        $costPrice = (int) preg_replace('/[^0-9]/', '', $request->cost_price ?? 0);
        $retailPrice = (int) preg_replace('/[^0-9]/', '', $request->retail_price ?? 0);
        $wholesalePrice = (int) preg_replace('/[^0-9]/', '', $request->wholesale_price ?? 0);
        $minWholesaleQty = (int) preg_replace('/[^0-9]/', '', $request->min_wholesale_qty ?? 0);
        $stock = (int) preg_replace('/[^0-9]/', '', $request->stock ?? 0);
        $minStockAlert = (int) preg_replace('/[^0-9]/', '', $request->min_stock_alert ?? 5);

        $productType = $request->product_type ?? 'physical';

        // Jika Pascabayar atau Jasa: Nol-kan seluruh elemen fisik & modal
        if ($productType === 'service' || $productType === 'digital_pascabayar') {
            $costPrice = 0;
            $wholesalePrice = 0;
            $minWholesaleQty = 0;
            $stock = 0;
            $minStockAlert = 0;
        } 
        // Jika Prabayar: Nol-kan stok saja, modal & harga jual tetap ada
        elseif ($productType === 'digital_prabayar' || $productType === 'digital') {
            $stock = 0;
            $minStockAlert = 0;
        }

        if (!$request->has('has_wholesale') && !$isQuick) {
            $wholesalePrice = 0;
            $minWholesaleQty = 0;
        }

        if ($productType === 'physical' || $productType === 'digital_prabayar' || $productType === 'digital') {
            if ($retailPrice < $costPrice) {
                return ['error' => 'Tidak bisa simpan, cek harga! Harga Eceran tidak boleh lebih murah dari Harga Modal (HPP).'];
            }
            if ($wholesalePrice > 0 && $wholesalePrice < $costPrice) {
                return ['error' => 'Tidak bisa simpan, cek harga! Harga Grosir tidak boleh lebih murah dari Harga Modal (HPP).'];
            }
        }

        return [
            'cost_price'        => $costPrice,
            'retail_price'      => $retailPrice,
            'wholesale_price'   => $wholesalePrice,
            'min_wholesale_qty' => $minWholesaleQty,
            'stock'             => $stock,
            'min_stock_alert'   => $minStockAlert,
            'product_type'      => $productType,
        ];
    }

    public function store(Request $request)
    {
        $check = $this->sanitizeAndValidateProduct($request);
        if (isset($check['error'])) {
            return back()->withInput()->withErrors(['error' => $check['error']]);
        }

        $request->validate([
            'name'          => 'required|string|max:255',
            'brand'         => 'nullable|string|max:100',
            'category_id'   => 'required|exists:categories,id',
            'product_type'  => 'required|in:physical,service,digital,digital_prabayar,digital_pascabayar',
            'rack_location' => 'nullable|string|max:255',
        ]);

        $cleanBarcode = $request->barcode ? preg_replace('/[^0-9A-Za-z]/', '', $request->barcode) : null;
        
        // AUTO-GENERATE BARCODE JIKA KOSONG (Wajib Ada Barcode)
        if (!$cleanBarcode) {
            $cleanBarcode = $this->generateInternalBarcode($request->product_type, $request->category_id);
        } else {
            // Validasi manual jika barcode diisi dari form
            if (Product::where('barcode', $cleanBarcode)->exists()) {
                return back()->withInput()->withErrors(['error' => 'Barcode tersebut sudah digunakan oleh produk lain!']);
            }
        }

        $product = Product::create(array_merge([
            'name'          => $request->name,
            'brand'         => $request->brand,
            'category_id'   => $request->category_id,
            'barcode'       => $cleanBarcode,
            'rack_location' => $request->rack_location,
            'is_active'     => true,
        ], $check));

        ActivityLog::record('Tambah Barang', "Menambahkan produk baru: {$product->name}");

        return back()->with('success', 'Barang baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $check = $this->sanitizeAndValidateProduct($request);
        if (isset($check['error'])) {
            return back()->withInput()->withErrors(['error' => $check['error']]);
        }

        $request->validate([
            'name'          => 'required|string|max:255',
            'brand'         => 'nullable|string|max:100',
            'category_id'   => 'required|exists:categories,id',
            'product_type'  => 'required|in:physical,service,digital,digital_prabayar,digital_pascabayar',
            'rack_location' => 'nullable|string|max:255',
        ]);

        $cleanBarcode = $request->barcode ? preg_replace('/[^0-9A-Za-z]/', '', $request->barcode) : null;
        
        // AUTO-GENERATE BARCODE JIKA KOSONG
        if (!$cleanBarcode) {
            $cleanBarcode = $this->generateInternalBarcode($request->product_type, $request->category_id);
        } else {
            if (Product::where('barcode', $cleanBarcode)->where('id', '!=', $id)->exists()) {
                return back()->withInput()->withErrors(['error' => 'Barcode tersebut sudah digunakan oleh produk lain!']);
            }
        }

        if ($product->cost_price != $check['cost_price'] || $product->retail_price != $check['retail_price'] || $product->wholesale_price != $check['wholesale_price']) {
            PriceChangeHistory::create([
                'product_id'          => $product->id,
                'user_id'             => auth()->id(),
                'old_price'           => $product->cost_price,
                'new_price'           => $check['cost_price'],
                'old_retail_price'    => $product->retail_price,
                'new_retail_price'    => $check['retail_price'],
                'old_wholesale_price' => $product->wholesale_price,
                'new_wholesale_price' => $check['wholesale_price'],
            ]);
        }

        $oldValues = $product->only(['name', 'brand', 'category_id', 'product_type', 'barcode', 'rack_location', 'cost_price', 'retail_price', 'wholesale_price', 'min_wholesale_qty', 'stock', 'min_stock_alert', 'is_active']);

        $updateData = array_merge([
            'name'          => $request->name,
            'brand'         => $request->brand,
            'category_id'   => $request->category_id,
            'barcode'       => $cleanBarcode,
            'rack_location' => $request->rack_location,
            'is_active'     => $request->has('is_active'),
        ], $check);

        $product->update($updateData);

        ActivityLog::recordWithSnapshot('Edit Barang', "Mengubah data produk: {$product->name}", $product, $oldValues, $product->only(array_keys($oldValues)));

        return back()->with('success', 'Data barang berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        try {
            $name = $product->name;
            $product->delete();
            ActivityLog::record('Hapus Barang', "Menghapus permanen barang: {$name}");
            return back()->with('success', "Barang '{$name}' berhasil dihapus permanen.");
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == "23000") {
                return back()->withErrors(['error' => "Barang '{$product->name}' tidak bisa dihapus permanen karena masih terikat riwayat transaksi/retur! Gunakan fitur Arsip/Nonaktifkan saja."]);
            }
            return back()->withErrors(['error' => 'Gagal menghapus data barang.']);
        }
    }

    public function toggleStatus($id)
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => !$product->is_active]);
        $status = $product->is_active ? 'Diaktifkan' : 'Diarsipkan (Nonaktif)';
        ActivityLog::record('Ubah Status Barang', "{$status} produk: {$product->name}");
        return back()->with('success', "Status barang berhasil diubah menjadi {$status}.");
    }

    public function history($id)
    {
        $product = Product::with([
            'category', 
            'priceHistories', 
            'purchaseDetails.purchase.supplier', 
            'transactionDetails.transaction',
            'latestPurchaseDetail.purchase.supplier' // Relasi supplier terakhir
        ])->findOrFail($id);

        return response()->json([
            'product'         => $product,
            'total_sold'      => $product->transactionDetails()->sum('qty'),
            'total_purchased' => $product->purchaseDetails()->sum('qty'),
            'price_histories' => $product->priceHistories,
            'last_supplier'   => $product->latestPurchaseDetail?->purchase?->supplier?->name ?? 'Belum ada data',
            'last_purchase_date' => $product->latestPurchaseDetail?->purchase?->purchase_date ? \Carbon\Carbon::parse($product->latestPurchaseDetail->purchase->purchase_date)->format('d/m/Y') : '-',
            'last_invoice_number' => $product->latestPurchaseDetail?->purchase?->invoice_number ?? '-',
            'last_invoice_id'     => $product->latestPurchaseDetail?->purchase?->id ?? null,
            'purchases'       => $product->purchaseDetails->map(function ($detail) {
                return [
                    'id'         => $detail->purchase->id ?? null,
                    'date' => $detail->purchase?->purchase_date ? \Carbon\Carbon::parse($detail->purchase->purchase_date)->format('d/m/Y') : '-',
                    'invoice'    => $detail->purchase->invoice_number ?? '-',
                    'supplier'   => $detail->purchase->supplier->name ?? '-',
                    'qty'        => $detail->qty,
                    'cost_price' => $detail->cost_price,
                ];
            }),
        ]);
    }

    public function generateBarcode(Request $request)
    {
        $type = $request->product_type ?? 'physical';
        $catId = (int) ($request->category_id ?? 1);
        return response()->json(['barcode' => $this->generateInternalBarcode($type, $catId)]);
    }

    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'cost_price' => 'required|integer|min:0',
            'retail_price' => 'required|integer|min:0',
        ]);
        
        $validated['product_type'] = 'physical';
        $validated['stock'] = 0;
        $validated['min_stock_alert'] = 5;
        $validated['is_active'] = true;
        // Wajib barcode
        $validated['barcode'] = $this->generateInternalBarcode('physical', $validated['category_id']);

        $product = Product::create($validated);
        return response()->json(['success' => true, 'product' => $product]);
    }
    
    // 1. Ekspor Seluruh Data Barang ke Berkas CSV
    public function exportCsv()
    {
        $fileName = 'katalog_produk_' . date('Ymd_His') . '.csv';
        $products = Product::with('category')->orderBy('id', 'asc')->get();

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Barcode', 
            'Nama Barang', 
            'Merek', 
            'Kategori', 
            'Tipe Produk', 
            'Harga Modal (HPP)', 
            'Harga Eceran', 
            'Harga Grosir', 
            'Min Qty Grosir', 
            'Stok', 
            'Batas Min Stok', 
            'Lokasi Rak'
        ];

        $callback = function () use ($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($products as $p) {
                fputcsv($file, [
                    $p->barcode ?? '',
                    $p->name,
                    $p->brand ?? '',
                    $p->category->name ?? 'Umum',
                    $p->product_type,
                    $p->cost_price,
                    $p->retail_price,
                    $p->wholesale_price,
                    $p->min_wholesale_qty,
                    $p->stock,
                    $p->min_stock_alert,
                    $p->rack_location ?? ''
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // 2. Unduh Format Template CSV Kosong untuk Panduan Impor
    public function downloadTemplate()
    {
        $fileName = 'template_impor_barang.csv';
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Barcode', 
            'Nama Barang', 
            'Merek', 
            'Kategori', 
            'Tipe Produk', 
            'Harga Modal (HPP)', 
            'Harga Eceran', 
            'Harga Grosir', 
            'Min Qty Grosir', 
            'Stok', 
            'Batas Min Stok', 
            'Lokasi Rak'
        ];

        $sampleRow = [
            '101000001',
            'Tempered Glass 5D iPhone 11',
            'Kingkong',
            'Aksesoris HP',
            'physical',
            '8000',
            '15000',
            '12000',
            '3',
            '50',
            '5',
            'Rak A-1'
        ];

        $callback = function () use ($columns, $sampleRow) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $sampleRow);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // 3. Impor Data Barang Massal dari Berkas CSV
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120'
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        // Lewati baris header judul kolom
        fgetcsv($handle, 2000, ",");

        $importedCount = 0;
        $validTypes = ['physical', 'digital', 'digital_prabayar', 'digital_pascabayar', 'service'];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 2000, ",")) !== false) {
                // Lewati baris kosong atau jika nama barang tidak diisi
                if (empty($row[1])) {
                    continue;
                }

                $barcode         = !empty(trim($row[0])) && trim($row[0]) !== '-' ? preg_replace('/[^0-9A-Za-z]/', '', trim($row[0])) : null;
                $name            = trim($row[1]);
                $brand           = !empty(trim($row[2])) ? trim($row[2]) : null;
                $categoryName    = !empty(trim($row[3])) ? trim($row[3]) : 'Umum';
                
                $rawType         = trim($row[4] ?? 'physical');
                $productType     = in_array($rawType, $validTypes) ? $rawType : 'physical';

                $costPrice       = (int) preg_replace('/[^0-9]/', '', $row[5] ?? 0);
                $retailPrice     = (int) preg_replace('/[^0-9]/', '', $row[6] ?? 0);
                $wholesalePrice  = (int) preg_replace('/[^0-9]/', '', $row[7] ?? 0);
                $minWholesaleQty = (int) preg_replace('/[^0-9]/', '', $row[8] ?? 0);
                $stock           = (int) preg_replace('/[^0-9]/', '', $row[9] ?? 0);
                $minStockAlert   = (int) preg_replace('/[^0-9]/', '', $row[10] ?? 5);
                $rackLocation    = !empty(trim($row[11])) ? trim($row[11]) : null;

                $category = Category::firstOrCreate(
                    ['name' => $categoryName],
                    ['slug' => Str::slug($categoryName)]
                );

                if ($productType === 'service' || $productType === 'digital_pascabayar') {
                    $stock = 0;
                    $minStockAlert = 0;
                    $costPrice = 0;
                } elseif ($productType === 'digital_prabayar' || $productType === 'digital') {
                    $stock = 0;
                    $minStockAlert = 0;
                }

                // FIX BUG DUPLIKASI: Jika barcode kosong di CSV, cari berdasarkan Nama & Kategori
                if (!$barcode) {
                    $existing = Product::where('name', $name)->where('category_id', $category->id)->first();
                    if ($existing) {
                        $barcode = $existing->barcode; // Gunakan barcode lama agar di-update
                    } else {
                        // Generate barcode baru jika benar-benar barang baru
                        $barcode = $this->generateInternalBarcode($productType, $category->id);
                    }
                }

                Product::updateOrCreate(
                    ['barcode' => $barcode],
                    [
                        'name'              => $name,
                        'brand'             => $brand,
                        'category_id'       => $category->id,
                        'product_type'      => $productType,
                        'cost_price'        => $costPrice,
                        'retail_price'      => $retailPrice,
                        'wholesale_price'   => $wholesalePrice,
                        'min_wholesale_qty' => $minWholesaleQty,
                        'stock'             => $stock,
                        'min_stock_alert'   => $minStockAlert,
                        'rack_location'     => $rackLocation,
                        'is_active'         => true,
                    ]
                );

                $importedCount++;
            }

            fclose($handle);
            DB::commit();

            ActivityLog::record('Impor Barang', "Mengimpor sebanyak {$importedCount} data barang via CSV.");
            return back()->with('success', "Berhasil mengimpor {$importedCount} data barang.");
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return back()->withErrors(['error' => 'Gagal memproses berkas CSV: ' . $e->getMessage()]);
        }
    }
    // FITUR UPDATE HARGA JUAL MASSAL (DARI POP-UP KULAKAN)
    public function bulkUpdatePrices(Request $request)
    {
        $request->validate([
            'products'                    => 'required|array',
            'products.*.id'               => 'required|exists:products,id',
            'products.*.retail_price'     => 'required|string',
            'products.*.wholesale_price'  => 'nullable|string',
        ]);

        $updatedCount = 0;
        $errors = [];

        foreach ($request->products as $item) {
            $product = \App\Models\Product::find($item['id']);
            if (!$product) continue;

            $oldRetail = $product->retail_price;
            $newRetail = (int) preg_replace('/[^0-9]/', '', $item['retail_price']);
            
            $oldWholesale = $product->wholesale_price;
            $newWholesale = (int) preg_replace('/[^0-9]/', '', $item['wholesale_price'] ?? 0);

            // VALIDASI BACKEND: Pastikan harga jual tidak di bawah HPP
            if ($newRetail < $product->cost_price) {
                $errors[] = "Harga Eceran {$product->name} tidak boleh di bawah HPP.";
            }
            if ($newWholesale > 0 && $newWholesale < $product->cost_price) {
                $errors[] = "Harga Grosir {$product->name} tidak boleh di bawah HPP.";
            }

            // Jika ada harga yang berubah dan lolos validasi, simpan!
            if (empty($errors) && ($oldRetail !== $newRetail || $oldWholesale !== $newWholesale)) {
                if (class_exists(\App\Models\PriceChangeHistory::class)) {
                    \App\Models\PriceChangeHistory::create([
                        'product_id'          => $product->id,
                        'user_id'             => auth()->id(),
                        'old_price'           => $product->cost_price, 
                        'new_price'           => $product->cost_price, // HPP tidak berubah di tahap ini
                        'old_retail_price'    => $oldRetail,
                        'new_retail_price'    => $newRetail,
                        'old_wholesale_price' => $oldWholesale,
                        'new_wholesale_price' => $newWholesale,
                        'note'                => 'Penyesuaian massal harga jual pasca-kulakan',
                    ]);
                }

                $product->retail_price = $newRetail;
                $product->wholesale_price = $newWholesale;
                $product->save();
                
                $updatedCount++;
            }
        }

        // Kembalikan error jika ada yang tidak valid
        if (!empty($errors)) {
            return back()->withErrors(['error' => 'Gagal mengubah harga! ' . implode(' ', $errors)]);
        }

        if (class_exists(\App\Models\ActivityLog::class) && $updatedCount > 0) {
            \App\Models\ActivityLog::record('Penyesuaian Harga', "Menyesuaikan harga jual massal untuk {$updatedCount} barang dari pop-up faktur.");
        }

        return back()->with('success', "Selesai! Harga jual untuk {$updatedCount} barang telah diperbarui.");
    }
    
}