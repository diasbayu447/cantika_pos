@extends('layouts.app')

@section('title', 'Tambah Master Barang')

@section('content')
<div class="card border-0 p-4" style="border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="m-0" style="color: #333; font-weight: 600;">Form Tambah Barang</h5>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary" style="border-radius: 8px;">Batal & Kembali</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" style="border-radius: 10px;">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('products.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <!-- Kolom Kiri -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label text-muted">Nama Barang <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" style="border-radius: 8px;" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Barcode (Opsional)</label>
                    <input type="text" name="barcode" class="form-control" style="border-radius: 8px;">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Kategori <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" style="border-radius: 8px;" required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Tipe Produk <span class="text-danger">*</span></label>
                    <select name="product_type" class="form-select" style="border-radius: 8px;" required>
                        <option value="physical">Fisik (Punya Stok & Rak)</option>
                        <option value="service">Jasa (Tanpa Stok)</option>
                        <option value="digital">Digital (Pulsa/Kuota)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Lokasi Rak / Laci (Opsional)</label>
                    <input type="text" name="rack_location" class="form-control" style="border-radius: 8px;">
                </div>
            </div>

            <!-- Kolom Kanan (Harga & Stok) -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label text-muted">Harga Modal (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="cost_price" class="form-control" value="0" min="0" style="border-radius: 8px;" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Harga Jual Eceran (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="retail_price" class="form-control" value="0" min="0" style="border-radius: 8px;" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Harga Grosir (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="wholesale_price" class="form-control" value="0" min="0" style="border-radius: 8px;" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Min. Qty Grosir <span class="text-danger">*</span></label>
                        <input type="number" name="min_wholesale_qty" class="form-control" value="0" min="0" style="border-radius: 8px;" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Stok Fisik Awal <span class="text-danger">*</span></label>
                        <input type="number" name="stock" class="form-control" value="0" min="0" style="border-radius: 8px;" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Batas Stok Menipis <span class="text-danger">*</span></label>
                        <input type="number" name="min_stock_alert" class="form-control" value="5" min="0" style="border-radius: 8px;" required>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4 text-muted">
        <div class="text-end">
            <button type="submit" class="btn px-4" style="background-color: #f2a6b0; color: white; border-radius: 8px; font-weight: 600;">Simpan Barang Baru</button>
        </div>
    </form>
</div>
@endsection