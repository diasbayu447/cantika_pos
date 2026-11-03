<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:150']);

        // Buat slug otomatis dari nama (misal: "Es Krim" -> "es-krim")
        $slug = Str::slug($request->name);
        
        // Pastikan slug unik jika sudah ada nama yang mirip
        $count = Category::where('slug', 'LIKE', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        $cols = Schema::getColumnListing('categories');
        $data = [
            'name' => $request->name,
            'slug' => $slug,
        ];

        $category = Category::create(array_intersect_key($data, array_flip($cols)));

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:150']);

        $category = Category::findOrFail($id);
        $slug = Str::slug($request->name);

        $category->update([
            'name' => $request->name,
            'slug' => $slug,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        if (Product::where('category_id', $id)->exists()) {
            return response()->json(['error' => 'Gagal! Kategori ini sedang digunakan oleh barang di gudang.'], 400);
        }

        $category->delete();
        return response()->json(['success' => true]);
    }
}