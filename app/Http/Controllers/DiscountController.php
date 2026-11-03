<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\Product;
use App\Models\VoucherCode;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DiscountController extends Controller
{
    public function index()
    {
        $discounts = Discount::withCount(['voucherCodes', 'voucherCodes as unused_voucher_count' => function ($q) {
            $q->where('is_used', false);
        }])->with('products')->latest()->get();

        $products = Product::where('is_active', true)->where('product_type', 'physical')->get();

        return view('discounts.index', compact('discounts', 'products'));
    }

    public function store(Request $request)
    {
        // Bersihkan karakter non-angka pada amount dan min_purchase
        $cleanAmount = (int) preg_replace('/[^0-9]/', '', $request->amount ?? 0);
        $cleanMinPurchase = (int) preg_replace('/[^0-9]/', '', $request->min_purchase ?? 0);

        $request->merge([
            'amount' => $cleanAmount,
            'min_purchase' => $cleanMinPurchase,
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:voucher,period',
            'scope' => 'required|in:cart,product',
            'discount_type' => 'required|in:nominal,percentage',
            'amount' => 'required|integer|min:1',
            'min_purchase' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'product_ids' => 'nullable|array',
            'batch_qty' => 'nullable|integer|min:1|max:500',
        ]);

        $discount = Discount::create([
            'name' => $request->name,
            'type' => $request->type,
            'scope' => $request->scope,
            'discount_type' => $request->discount_type,
            'amount' => $request->amount,
            'min_purchase' => $request->min_purchase ?? 0,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => true,
        ]);

        if ($request->scope === 'product' && !empty($request->product_ids)) {
            $discount->products()->attach($request->product_ids);
        }

        if ($request->type === 'voucher' && $request->batch_qty) {
            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $discount->name), 0, 3)) ?: 'CK';
            for ($i = 0; $i < $request->batch_qty; $i++) {
                VoucherCode::create([
                    'discount_id' => $discount->id,
                    'code' => $prefix . '-' . strtoupper(Str::random(5)),
                    'is_used' => false,
                ]);
            }
        }

        ActivityLog::record('Tambah Diskon', "Membuat diskon baru: {$discount->name} ({$discount->scope})");
        return back()->with('success', "Promo '{$discount->name}' berhasil dibuat.");
    }

    public function showCodes($id)
    {
        $discount = Discount::with('voucherCodes')->findOrFail($id);
        return response()->json($discount->voucherCodes);
    }

    public function printVouchers($id)
    {
        $promo = Discount::with(['voucherCodes' => function ($q) {
            $q->where('is_used', false);
        }])->findOrFail($id);

        $voucherCodes = $promo->voucherCodes;

        return view('discounts.print_voucher', compact('promo', 'voucherCodes'));
    }

    public function toggleStatus($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->update(['is_active' => !$discount->is_active]);
        return back()->with('success', 'Status diskon berhasil diperbarui.');
    }

    public function update(Request $request, $id)
    {
        $discount = \App\Models\Discount::findOrFail($id);

        $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:period,voucher',
            'scope'         => 'required|in:cart,product',
            'discount_type' => 'required|in:percentage,nominal',
            'amount'        => 'required|numeric|min:0',
            'min_purchase'  => 'nullable|numeric|min:0',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
        ]);

        $discount->name = $request->name;
        $discount->type = $request->type;
        $discount->scope = $request->scope;
        $discount->discount_type = $request->discount_type;
        $discount->amount = $request->amount;
        $discount->min_purchase = $request->min_purchase ?? 0;
        $discount->start_date = $request->start_date;
        $discount->end_date = $request->end_date;
        
        // Memastikan status aktif (dari checkbox atau select dropdown)
        $discount->is_active = $request->boolean('is_active') ?: ($request->is_active == 1);
        
        $discount->save();

        // Jika diskon ini tipe "Cuci Gudang" (berlaku untuk produk tertentu)
        $selectedProducts = $request->input('product_ids', $request->input('products', []));
        if ($request->scope === 'product' && !empty($selectedProducts)) {
            $discount->products()->sync($selectedProducts);
        } else {
            if(method_exists($discount->products(), 'detach')) {
                $discount->products()->detach();
            }
        }

        if (class_exists(\App\Models\ActivityLog::class)) {
            \App\Models\ActivityLog::record('Edit Diskon', "Memperbarui program diskon/promo: {$discount->name}");
        }

        return back()->with('success', 'Data diskon/promo berhasil diperbarui!');
    }
    
    public function destroy($id)
    {
        $discount = Discount::findOrFail($id);
        $name = $discount->name;
        $discount->delete();
        return back()->with('success', "Diskon '{$name}' berhasil dihapus.");
    }
}