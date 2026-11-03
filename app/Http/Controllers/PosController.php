<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\VoucherCode;
use App\Models\Discount;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PosController extends Controller
{
    public function index()
    {
        $customers = Customer::orderBy('name')->get();
        return view('pos.index', compact('customers'));
    }

    public function searchProduct(Request $request)
    {
        $keyword = $request->keyword;
        $products = Product::where('is_active', true)
            ->where(function ($q) use ($keyword) {
                $q->where('barcode', $keyword)
                  ->orWhere('name', 'LIKE', "%{$keyword}%");
            })
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    public function addToCart(Request $request)
    {
        $product = Product::findOrFail($request->product_id);
        $qty = $request->qty ?? 1;
        $customerId = $request->customer_id;

        $cart = session()->get('pos_cart', []);

        if ($product->product_type == 'physical') {
            $currentQty = isset($cart[$product->id]) ? $cart[$product->id]['qty'] : 0;
            if (($currentQty + $qty) > $product->stock) {
                return response()->json(['error' => 'Stok tidak mencukupi! Sisa stok: ' . $product->stock], 400);
            }
        }

        if (isset($cart[$product->id])) {
            $cart[$product->id]['qty'] += $qty;
        } else {
            $defaultAdmin = ($product->retail_price > 0) ? $product->retail_price : 3000;
            $isPascabayar = ($product->product_type === 'digital_pascabayar');

            $cart[$product->id] = [
                'id'                => $product->id,
                'barcode'           => $product->barcode,
                'name'              => $product->name,
                'cost_price'        => $isPascabayar ? 0 : $product->cost_price,
                'retail_price'      => $isPascabayar ? 0 : $product->retail_price,
                'wholesale_price'   => $product->wholesale_price,
                'min_wholesale_qty' => $product->min_wholesale_qty,
                'qty'               => $qty,
                'product_type'      => $product->product_type,
                'target_number'     => '',
                'bill_amount'       => 0,
                'admin_fee'         => $defaultAdmin
            ];
        }

        session()->put('pos_cart', $cart);
        return $this->calculateCart($customerId);
    }

    public function removeFromCart(Request $request)
    {
        $cart = session()->get('pos_cart', []);
        if (isset($cart[$request->product_id])) {
            unset($cart[$request->product_id]);
            session()->put('pos_cart', $cart);
        }
        return $this->calculateCart($request->customer_id);
    }

    public function updateQty(Request $request)
    {
        $cart = session()->get('pos_cart', []);
        if (isset($cart[$request->product_id])) {
            $qty = max(1, (int) $request->qty);
            $product = Product::find($request->product_id);

            if ($product && $product->product_type === 'physical' && $qty > $product->stock) {
                return response()->json([
                    'error' => 'Stok tidak mencukupi! Sisa stok: ' . $product->stock,
                ], 400);
            }

            $cart[$request->product_id]['qty'] = $qty;
            session()->put('pos_cart', $cart);
        }
        return $this->calculateCart($request->customer_id);
    }

    public function updatePrice(Request $request)
    {
        $cart = session()->get('pos_cart', []);
        if (isset($cart[$request->product_id])) {
            $price = (int) preg_replace('/[^0-9]/', '', $request->price);
            $cart[$request->product_id]['retail_price'] = max(0, $price);
            session()->put('pos_cart', $cart);
        }
        return $this->calculateCart($request->customer_id);
    }

    public function updateTargetNumber(Request $request)
    {
        $cart = session()->get('pos_cart', []);
        if (isset($cart[$request->product_id])) {
            $cart[$request->product_id]['target_number'] = trim($request->target_number);
            session()->put('pos_cart', $cart);
        }
        return response()->json(['success' => true]);
    }

    public function updatePascabayar(Request $request)
    {
        $cart = session()->get('pos_cart', []);
        if (isset($cart[$request->product_id])) {
            $bill = (int) preg_replace('/[^0-9]/', '', $request->bill_amount ?? 0);
            $admin = (int) preg_replace('/[^0-9]/', '', $request->admin_fee ?? 3000);

            $cart[$request->product_id]['bill_amount'] = $bill;
            $cart[$request->product_id]['admin_fee'] = $admin;
            
            $cart[$request->product_id]['cost_price'] = $bill;
            $cart[$request->product_id]['retail_price'] = $bill > 0 ? ($bill + $admin) : 0;

            session()->put('pos_cart', $cart);
        }
        return $this->calculateCart($request->customer_id);
    }

    public function calculateCart($customerId = null)
    {
        if ($customerId instanceof Request) {
            $customerId = $customerId->input('customer_id');
        }

        return response()->json($this->computeCartState($customerId ?: request('customer_id')));
    }

    private function computeCartState($customerId = null): array
    {
        $cart = session()->get('pos_cart', []);
        $customer = $customerId ? Customer::find($customerId) : null;
        $isRegistered = $customer && $customer->status === 'registered';

        $today = date('Y-m-d');
        $activeItemDiscounts = Discount::where('is_active', true)
            ->where('scope', 'product')
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->with('products')
            ->get();

        $itemDiscountMap = [];
        foreach ($activeItemDiscounts as $d) {
            foreach ($d->products as $p) {
                $itemDiscountMap[$p->id] = [
                    'name'   => $d->name,
                    'type'   => $d->discount_type,
                    'amount' => $d->amount
                ];
            }
        }

        $grandTotal = 0;
        $processedCart = [];
        $potentialWholesale = false;

        foreach ($cart as $item) {
            $isWholesale = false;
            $isPromoItem = false;
            $promoNote = '';
            $basePrice = (int) $item['retail_price'];
            $price = $basePrice;
            $lineDiscount = 0;

            if (isset($itemDiscountMap[$item['id']])) {
                $isPromoItem = true;
                $promo = $itemDiscountMap[$item['id']];
                $cut = ($promo['type'] === 'nominal') ? $promo['amount'] : ($price * $promo['amount']) / 100;
                $price = (int) max(0, $price - $cut);
                $lineDiscount = ($basePrice - $price) * $item['qty'];
                $promoNote = 'CUCI GUDANG';
            } elseif ($isRegistered && $item['min_wholesale_qty'] > 0 && $item['qty'] >= $item['min_wholesale_qty']) {
                $isWholesale = true;
                $price = (int) $item['wholesale_price'];
            } elseif ($item['min_wholesale_qty'] > 0 && $item['qty'] >= $item['min_wholesale_qty']) {
                $potentialWholesale = true;
            }

            $subtotal = $item['qty'] * $price;
            $grandTotal += $subtotal;

            $processedCart[] = [
                'id'              => $item['id'],
                'barcode'         => $item['barcode'],
                'name'            => $item['name'],
                'qty'             => $item['qty'],
                'cost_price'      => $item['cost_price'],
                'price'           => $price,
                'discount_amount' => $lineDiscount,
                'is_wholesale'    => $isWholesale,
                'is_promo_item'   => $isPromoItem,
                'promo_note'      => $promoNote,
                'subtotal'        => $subtotal,
                'product_type'    => $item['product_type'],
                'target_number'   => $item['target_number'] ?? '',
                'bill_amount'     => $item['bill_amount'] ?? 0,
                'admin_fee'       => $item['admin_fee'] ?? 3000
            ];
        }

        $autoPeriodDiscount = 0;
        $autoPeriodName = '';
        if (!session()->has('pos_discount')) {
            $periodPromo = Discount::where('is_active', true)
                ->where('type', 'period')
                ->where('scope', 'cart')
                ->where(function ($q) use ($today) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
                })
                ->where('min_purchase', '<=', $grandTotal)
                ->first();

            if ($periodPromo) {
                $autoPeriodDiscount = ($periodPromo->discount_type === 'nominal')
                    ? $periodPromo->amount
                    : ($grandTotal * $periodPromo->amount) / 100;
                $autoPeriodDiscount = (int) $autoPeriodDiscount;
                $autoPeriodName = $periodPromo->name;
            }
        }

        return [
            'cart'                 => $processedCart,
            'grand_total'          => $grandTotal,
            'auto_period_discount' => $autoPeriodDiscount,
            'auto_period_name'     => $autoPeriodName,
            'show_wholesale_alert' => $potentialWholesale,
            'is_registered'        => $isRegistered,
            'customer_points'      => 0,
        ];
    }

    public function applyDiscount(Request $request)
    {
        $code = strtoupper(trim($request->code));
        $grandTotal = (int) $request->grand_total;

        $voucherCode = VoucherCode::with('discount')->where('code', $code)->first();

        if (!$voucherCode) {
            return response()->json(['error' => 'Kode voucher tidak valid!'], 404);
        }

        if ($voucherCode->is_used) {
            return response()->json(['error' => 'Voucher ini sudah pernah digunakan sebelumnya!'], 400);
        }

        $discount = $voucherCode->discount;
        if (!$discount || !$discount->is_active) {
            return response()->json(['error' => 'Promo voucher sudah tidak aktif!'], 400);
        }

        if ($grandTotal < $discount->min_purchase) {
            return response()->json(['error' => 'Minimal belanja untuk voucher ini: Rp ' . number_format($discount->min_purchase, 0, ',', '.')], 400);
        }

        $discountAmount = ($discount->discount_type == 'nominal') 
                          ? $discount->amount 
                          : ($grandTotal * $discount->amount) / 100;

        $discountAmount = min($discountAmount, $grandTotal);

        session()->put('pos_discount', [
            'voucher_code_id' => $voucherCode->id,
            'code'            => $voucherCode->code,
            'name'            => $discount->name,
            'amount'          => $discountAmount
        ]);

        return response()->json([
            'success'         => 'Voucher berhasil diterapkan!',
            'discount_amount' => $discountAmount,
            'new_total'       => $grandTotal - $discountAmount
        ]);
    }

    public function removeDiscount()
    {
        session()->forget('pos_discount');
        return response()->json(['success' => 'Voucher dibatalkan.']);
    }

    public function checkout(Request $request)
    {
        $cart = session()->get('pos_cart', []);
        if (empty($cart)) {
            return response()->json(['error' => 'Keranjang belanja kosong!'], 400);
        }

        $customerId = !empty($request->customer_id) ? (int) $request->customer_id : null;
        $computed = $this->computeCartState($customerId);
        $grandTotal = (int) $computed['grand_total'];

        $appliedDiscount = session()->get('pos_discount');
        $discountAmount = 0;
        $discountNote = null;

        if ($appliedDiscount) {
            $discountAmount = (int) $appliedDiscount['amount'];
            $discountNote = $appliedDiscount['name'] . ' (' . $appliedDiscount['code'] . ')';
        } elseif ($computed['auto_period_discount'] > 0) {
            $discountAmount = (int) $computed['auto_period_discount'];
            $discountNote = $computed['auto_period_name'] ?: 'Promo periode';
        }

        $discountAmount = min($discountAmount, $grandTotal);
        $finalPayable = max(0, $grandTotal - $discountAmount);
        $cashReceived = (int) ($request->cash_received ?? 0);
        $paymentMethod = $request->payment_method;

        if ($paymentMethod === 'cash' && $cashReceived < $finalPayable) {
            return response()->json(['error' => 'Uang tunai kurang dari total belanja!'], 400);
        }

        try {
            $transaction = DB::transaction(function () use (
                $customerId,
                $computed,
                $finalPayable,
                $discountAmount,
                $discountNote,
                $cashReceived,
                $paymentMethod,
                $appliedDiscount
            ) {
                $trx = Transaction::create([
                    'invoice_number'         => 'TRX-' . date('YmdHis') . '-' . rand(100, 999),
                    'user_id'                => Auth::id(),
                    'customer_id'            => $customerId,
                    'payment_method'         => $paymentMethod === 'cash' ? 'cash' : 'qris',
                    'total_amount'           => $finalPayable,
                    'discount_amount'        => $discountAmount,
                    'discount_note'          => $discountNote,
                    'points_earned'          => 0,
                    'points_redeemed'        => 0,
                    'points_discount_amount' => 0,
                    'cash_amount'            => $paymentMethod === 'cash' ? $cashReceived : 0,
                    'non_cash_amount'        => $paymentMethod !== 'cash' ? $finalPayable : 0,
                    'change_amount'          => $paymentMethod === 'cash' ? ($cashReceived - $finalPayable) : 0,
                    'status'                 => 'success'
                ]);

                foreach ($computed['cart'] as $item) {
                    $isDigital = in_array($item['product_type'], ['digital', 'digital_prabayar', 'digital_pascabayar']);
                    
                    $trx->details()->create([
                        'product_id'      => $item['id'],
                        'qty'             => $item['qty'],
                        'cost_price'      => $item['cost_price'],
                        'selling_price'   => $item['price'],
                        'is_wholesale'    => $item['is_wholesale'],
                        'discount_amount' => $item['discount_amount'],
                        'subtotal'        => $item['subtotal'],
                        'target_number'   => $isDigital ? ($item['target_number'] ?? null) : null,
                    ]);

                    if ($item['product_type'] === 'physical') {
                        $p = Product::lockForUpdate()->find($item['id']);
                        if (!$p || $p->stock < $item['qty']) {
                            throw new \RuntimeException(
                                'Stok tidak mencukupi untuk ' . ($p->name ?? 'barang') . '.'
                            );
                        }
                        $p->decrement('stock', $item['qty']);
                    }
                }

                if ($appliedDiscount && isset($appliedDiscount['voucher_code_id'])) {
                    VoucherCode::where('id', $appliedDiscount['voucher_code_id'])->update([
                        'is_used'        => true,
                        'used_at'        => now(),
                        'transaction_id' => $trx->id
                    ]);
                }

                // LOG DIPERBARUI: Menyertakan nama pelanggan dan metode pembayaran
                $namaPelanggan = $customerId ? Customer::find($customerId)->name : 'Pelanggan Umum';
                ActivityLog::record(
                    'Penjualan Kasir', 
                    "Menyelesaikan transaksi {$trx->invoice_number} senilai Rp " . number_format($finalPayable, 0, ',', '.') . " ({$namaPelanggan}) via " . strtoupper($paymentMethod) . "."
                );
                
                return $trx;
            });

            session()->forget('pos_cart');
            session()->forget('pos_discount');

            return response()->json([
                'success'        => 'Transaksi Berhasil Disimpan!',
                'transaction_id' => $transaction->id
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memproses transaksi: ' . $e->getMessage()], 500);
        }
    }

    public function receipt($id)
    {
        $transaction = Transaction::with(['details.product', 'user', 'customer'])->findOrFail($id);
        $isReprint = request('reprint') == '1';

        return response()
            ->view('pos.receipt', compact('transaction', 'isReprint'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin'       => 'required|string',
            'item_name' => 'nullable|string'
        ]);

        $authorizedUser = User::whereIn('role', ['supervisor', 'owner'])
                              ->where('pin', $request->pin)
                              ->where('is_active', true)
                              ->first();

        if ($authorizedUser) {
            $cashier = Auth::user(); 
            $itemName = $request->item_name ? $request->item_name : 'barang';
            
            ActivityLog::record(
                'Otorisasi Void', 
                "Penghapusan {$itemName} pada keranjang kasir disetujui oleh {$authorizedUser->name} ({$authorizedUser->username}) selaku {$authorizedUser->role}."
            );
            
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'PIN salah atau Anda tidak memiliki akses otorisasi!'], 403);
    }

    public function holdCart(Request $request)
    {
        $cart = session()->get('pos_cart', []);
        if (empty($cart)) {
            return response()->json(['error' => 'Keranjang kosong!'], 400);
        }

        $heldCarts = session()->get('pos_held_carts', []);
        $customerName = 'Pelanggan Umum';
        if ($request->customer_id) {
            $customer = Customer::find($request->customer_id);
            $customerName = $customer ? $customer->name : 'Pelanggan Umum';
        }

        $holdId = time();
        $heldCarts[$holdId] = [
            'id'            => $holdId,
            'time'          => date('H:i:s'),
            'customer_id'   => $request->customer_id,
            'customer_name' => $customerName,
            'cart'          => $cart,
            'total_items'   => array_sum(array_column($cart, 'qty')),
            'grand_total'   => $request->grand_total
        ];

        session()->put('pos_held_carts', $heldCarts);
        session()->forget('pos_cart');
        session()->forget('pos_discount');

        return response()->json(['success' => 'Antrean berhasil ditahan!']);
    }

    public function getHeldCarts()
    {
        $heldCarts = session()->get('pos_held_carts', []);
        return response()->json(array_values($heldCarts));
    }

    public function recallCart(Request $request)
    {
        $holdId = $request->hold_id;
        $heldCarts = session()->get('pos_held_carts', []);

        if (isset($heldCarts[$holdId])) {
            session()->put('pos_cart', $heldCarts[$holdId]['cart']);
            $customerId = $heldCarts[$holdId]['customer_id'];
            unset($heldCarts[$holdId]);
            session()->put('pos_held_carts', $heldCarts);

            return $this->calculateCart($customerId);
        }

        return response()->json(['error' => 'Data antrean tidak ditemukan!'], 404);
    }


    public function generateWaReceipt(Request $request, $id)
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $trx = Transaction::with(['details.product', 'user', 'customer'])->findOrFail($id);

        $phone = preg_replace('/[^0-9]/', '', $request->phone ?? '');
        if ($phone) {
            if (str_starts_with($phone, '62')) {
                $phone = substr($phone, 2);
            } elseif (str_starts_with($phone, '0')) {
                $phone = substr($phone, 1);
            }
            $phone = '62' . $phone;
        }

        $store = \App\Models\StoreSetting::current();
        $shopName = "*" . strtoupper($store->shop_name ?? 'CANTIKA POS') . "*";
        $header = "{$shopName}\n{$store->address}\nKomplain/WA: {$store->phone}\n--------------------------------\n";
        $info = "No. Faktur : {$trx->invoice_number}\n";
        $info .= "Tanggal    : " . $trx->created_at->format('d/m/Y H:i') . "\n";
        $info .= "Kasir      : " . ($trx->user->display_name ?? $trx->user->name) . "\n";
        $info .= "Pelanggan  : " . ($trx->customer->name ?? 'Pelanggan Umum') . "\n";
        $info .= "--------------------------------\n*DETAIL BELANJA*\n";

        $items = "";
        foreach ($trx->details as $item) {
            $itemName = $item->product->name ?? 'Barang';
            $subtotal = number_format($item->subtotal, 0, ',', '.');
            $price = number_format($item->selling_price, 0, ',', '.');
            
            if ($item->product && $item->product->product_type === 'digital_pascabayar' && $item->cost_price > 0) {
                $tagihanPokok = number_format($item->cost_price, 0, ',', '.');
                $adminFee = number_format($item->selling_price - $item->cost_price, 0, ',', '.');
                $items .= "• {$itemName}\n  Tagihan: Rp {$tagihanPokok}\n  Biaya Admin: Rp {$adminFee}\n  Total: Rp {$subtotal}\n";
            } else {
                $items .= "• {$itemName}\n  {$item->qty} x Rp {$price} = Rp {$subtotal}\n";
            }

            if (!empty($item->target_number)) {
                $items .= "  ↳ No. Tujuan/ID: *{$item->target_number}*\n";
            }
        }

        $calc = "--------------------------------\n";
        if ($trx->discount_amount > 0) {
            $subBeforeDisc = number_format($trx->total_amount + $trx->discount_amount, 0, ',', '.');
            $disc = number_format($trx->discount_amount, 0, ',', '.');
            $calc .= "Subtotal   : Rp {$subBeforeDisc}\n";
            $calc .= "Diskon     : -Rp {$disc} (" . ($trx->discount_note ?? 'Promo') . ")\n";
        }
        $calc .= "TOTAL      : *Rp " . number_format($trx->total_amount, 0, ',', '.') . "*\n";
        $calc .= "Metode     : " . strtoupper($trx->payment_method) . "\n";
        $calc .= "Kembalian  : Rp " . number_format($trx->change_amount, 0, ',', '.') . "\n";
        $calc .= "--------------------------------\n";
        $footer = "Terima kasih telah berbelanja di {$store->shop_name}!\n_{$store->receipt_footer}_";

        $fullMessage = $header . $info . $items . $calc . $footer;
        $waUrl = "https://wa.me/{$phone}?text=" . urlencode($fullMessage);

        return response()->json(['url' => $waUrl]);
    }
    
    public function storeCustomer(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $request->phone ?? '');
        if ($phone && str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $customer = Customer::create([
            'name'   => $request->name,
            'phone'  => $phone ? '+' . $phone : null,
            'status' => 'registered',
            'points' => 0
        ]);

        ActivityLog::record('Tambah Member', "Mendaftarkan pelanggan baru dari layar kasir: {$customer->name}");

        return response()->json([
            'success'  => true,
            'customer' => $customer
        ]);
    }

    public function clearCart(Request $request)
    {
        $request->session()->forget('pos_cart');
        $request->session()->forget('pos_discount');
        
        return $this->calculateCart($request->customer_id); 
    }
}