<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    public function index()
    {
        $products = Product::where('is_active', true)
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->orderBy('name')
            ->get();

        return view('barcodes.index', compact('products'));
    }

    public function print(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1|max:100',
            'paper_size' => 'nullable|string',
        ]);

        $labels = [];
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            for ($i = 0; $i < $item['qty']; $i++) {
                $labels[] = $product;
            }
        }

        $paperSize = $request->paper_size ?? '40x30';

        return view('barcodes.print', compact('labels', 'paperSize'));
    }
}