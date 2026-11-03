@extends('layouts.app')

@section('title', 'Data Master Barang')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    .table td, .table th { vertical-align: middle; }
    .filter-btn { text-decoration: none; font-size: 11px; padding: 0 4px; color: #888; }
    .filter-btn:hover { color: #d17885; }
    .dropdown-menu .dropdown-item.active { background-color: #f2a6b0; color: white; }
</style>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 10px;" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger" style="border-radius: 10px;">
        <ul class="mb-0">
            @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 p-4" style="border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="m-0 fw-bold" style="color: #333;">Katalog Master Barang</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('products.export') }}" class="btn btn-outline-success btn-sm" style="border-radius: 8px;">
                📊 Ekspor Excel
            </a>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importProductModal" style="border-radius: 8px;">
                📥 Impor Massal
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal" style="border-radius: 8px;">
                📁 Kelola Kategori
            </button>
            <button type="button" class="btn btn-sm fw-bold px-3" data-bs-toggle="modal" data-bs-target="#productModal" id="btnOpenAddModal" style="background-color: #f2a6b0; color: white; border-radius: 8px;">
                + Tambah Barang Baru
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="productsTable" class="table table-hover align-middle" style="font-size: 13px; width: 100%;">
            <thead class="table-light">
                <tr>
                    <th width="4%">No</th>
                    <th>Barcode</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Tipe</th>
                    <th>H. Modal</th>
                    <th>H. Jual (Normal)</th>
                    <th>Stok</th>
                    <th>Status</th>
                    <th width="15%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $index => $product)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-nowrap">
                        @if($product->barcode) <span class="badge bg-secondary font-monospace">{{ $product->barcode }}</span>
                        @else <span class="text-muted">-</span> @endif
                    </td>
                    <td class="fw-bold text-dark">{{ $product->name }}</td>
                    <td class="text-nowrap">{{ $product->category->name ?? '-' }}</td>
                    <td>
                        @if($product->product_type == 'physical') <span class="badge bg-light text-dark border">Fisik</span>
                        @elseif($product->product_type == 'service') <span class="badge bg-info text-dark">Jasa</span>
                        @elseif($product->product_type == 'digital_pascabayar') <span class="badge bg-warning text-dark">Pascabayar</span>
                        @else <span class="badge bg-primary">Prabayar</span> @endif
                    </td>
                    <td class="text-nowrap text-muted">
                        {{ $product->product_type == 'service' || $product->product_type == 'digital_pascabayar' ? '-' : 'Rp ' . number_format($product->cost_price, 0, ',', '.') }}
                    </td>
                    <td class="text-nowrap text-primary fw-bold">
                        {{ $product->product_type == 'service' || $product->product_type == 'digital_pascabayar' ? 'Dinamis (Di Kasir)' : 'Rp ' . number_format($product->retail_price, 0, ',', '.') }}
                    </td>
                    <td class="text-nowrap">
                        @if($product->product_type == 'physical')
                            <span class="badge {{ $product->stock <= $product->min_stock_alert ? 'bg-danger' : 'bg-success' }}">{{ $product->stock }}</span>
                        @else <span class="badge bg-light text-dark">-</span> @endif
                    </td>
                    <td class="text-nowrap">
                        @if($product->is_active) <span class="badge bg-success">Aktif</span>
                        @else <span class="badge bg-warning text-dark">Arsip</span> @endif
                    </td>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-info btn-history px-2 py-1" data-id="{{ $product->id }}" title="Histori & Grafik">📋 Riwayat</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit px-2 py-1" data-product="{{ json_encode($product) }}">✏️ Edit</button>
                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus permanen barang ini?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1">🗑️</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL KELOLA KATEGORI BARANG (AJAX)                      -->
<!-- ======================================================== -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="categoryModalLabel">📁 Kelola Kategori Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-4">
                <div class="input-group mb-3 shadow-sm" style="border-radius: 8px; overflow: hidden;">
                    <input type="text" id="newCategoryName" class="form-control" placeholder="Ketik nama kategori baru..." required autocomplete="off">
                    <button type="button" id="btnSaveCategory" class="btn btn-primary fw-bold px-4">Tambah</button>
                </div>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto; border-radius: 8px; border: 1px solid #e9ecef;">
                    <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">Nama Kategori</th>
                                <th class="text-center" width="35%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="categoryTableBody">
                            <tr><td colspan="2" class="text-center text-muted py-3">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL FORM TAMBAH / EDIT BARANG -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="productModalTitle" style="color: #d17885;">Tambah Barang Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="productForm" method="POST" action="{{ route('products.store') }}">
                @csrf
                <div id="methodSpoof"></div>
                <div class="modal-body pb-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="p_name" class="form-control" required placeholder="Contoh: Tempered Glass 5D / Token PLN">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Tipe Produk <span class="text-danger">*</span></label>
                            <select name="product_type" id="p_type" class="form-select" required>
                                <option value="physical">Fisik (Barang Jadi di Rak)</option>
                                <option value="digital_prabayar">Digital Prabayar (Pulsa/Token PLN/Voucher Game)</option>
                                <option value="digital_pascabayar">Digital Pascabayar (Tagihan PLN/PAM/BPJS)</option>
                                <option value="service">Jasa (Pasang / Servis)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Kategori <span class="text-danger">*</span></label>
                            <select name="category_id" id="p_category" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $category) <option value="{{ $category->id }}">{{ $category->name }}</option> @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <!-- BARCODE WAJIB (AUTO GEN JIKA KOSONG) -->
                            <label class="form-label text-muted small fw-bold">Barcode <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="barcode" id="p_barcode" class="form-control font-monospace" placeholder="Scan barcode / kosongkan" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <button type="button" class="btn btn-outline-secondary fw-bold" id="btnGenBarcode">⚡ Buat</button>
                            </div>
                        </div>

                        <!-- HARGA & HPP -->
                        <div class="col-md-6" id="costPriceContainer">
                            <label class="form-label text-muted small fw-bold text-danger">Harga Modal (HPP) <span class="text-danger">*</span></label>
                            <input type="text" name="cost_price" id="p_cost" class="form-control format-rupiah fw-bold" required placeholder="0">
                        </div>
                        <div class="col-md-6" id="retailPriceContainer">
                            <label class="form-label text-muted small fw-bold text-success">Harga Eceran (Normal) <span class="text-danger">*</span></label>
                            <input type="text" name="retail_price" id="p_retail" class="form-control format-rupiah fw-bold" required placeholder="0">
                        </div>
                        
                        <div class="col-12" id="wholesaleToggleContainer">
                            <div class="p-2 px-3 rounded bg-light border d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold d-block text-dark small">Barang Ini Melayani Harga Grosir?</span>
                                </div>
                                <div class="form-check form-switch fs-5">
                                    <input class="form-check-input" type="checkbox" id="has_wholesale" name="has_wholesale" value="1">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 wholesale-field" style="display: none;">
                            <label class="form-label text-muted small fw-bold text-primary">Harga Grosir (Khusus Member)</label>
                            <input type="text" name="wholesale_price" id="p_wholesale" class="form-control format-rupiah fw-bold" value="0">
                        </div>
                        <div class="col-md-6 wholesale-field" style="display: none;">
                            <label class="form-label text-muted small fw-bold text-primary">Min. Qty Pembelian Grosir</label>
                            <input type="text" name="min_wholesale_qty" id="p_min_wholesale" class="form-control number-only fw-bold" value="0" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>

                        <div class="col-md-4 stock-field">
                            <label class="form-label text-muted small fw-bold">Stok Fisik Awal</label>
                            <input type="text" name="stock" id="p_stock" class="form-control number-only fw-bold text-primary" value="0" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <div class="col-md-4 stock-field">
                            <!-- MINIMUM STOCK ALERT FIELD -->
                            <label class="form-label text-muted small fw-bold text-danger">Batas Minimal Stok (Alert)</label>
                            <input type="text" name="min_stock_alert" id="p_min_stock_alert" class="form-control number-only" value="5" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <div class="col-md-4 stock-field" id="rackContainer">
                            <label class="form-label text-muted small fw-bold">Lokasi Rak / Laci</label>
                            <input type="text" name="rack_location" id="p_rack" class="form-control" placeholder="Contoh: Rak A-2">
                        </div>
                        
                        <div class="col-12" id="statusContainer" style="display: none;">
                            <div class="p-3 rounded bg-light border d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark">Status Aktifasi Barang</span>
                                <div class="form-check form-switch fs-5"><input class="form-check-input" type="checkbox" name="is_active" id="p_is_active" value="1"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn fw-bold px-4" style="background-color: #f2a6b0; color: white; border-radius: 8px;">Simpan Barang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL IMPOR MASSAL -->
<div class="modal fade" id="importProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: #0d6efd;">📥 Impor Data Barang Massal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body pb-3">
                    <div class="p-3 mb-3 rounded" style="background-color: #f0f7ff; border: 1px dashed #0d6efd;">
                        <span class="fw-bold d-block text-dark small mb-1">Panduan Format File:</span>
                        <ul class="text-muted small ps-3 mb-2" style="font-size: 12px;">
                            <li>Gunakan file berekstensi <strong>.CSV</strong>.</li>
                            <li>Tipe yang didukung: <code>physical</code>, <code>digital_prabayar</code>, <code>digital_pascabayar</code>, <code>service</code>.</li>
                            <li>Kategori baru otomatis dibuat jika belum terdaftar.</li>
                        </ul>
                        <a href="{{ route('products.template') }}" class="btn btn-sm btn-light border fw-bold text-primary">📄 Unduh Template Kosong</a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Pilih Berkas CSV <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv, .txt" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-bold px-4">Mulai Impor Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL HISTORI & GRAFIK (Untuk tombol riwayat) -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-info" id="historyModalLabel">📋 Riwayat Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="historyContent" class="text-center py-4">Memuat data...</div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        $('#productsTable').DataTable({ order: [[0, 'asc']] });

        // LOGIKA PENYESUAIAN FORM BERDASARKAN TIPE PRODUK
        function adjustProductFormFields() {
            let type = $('#p_type').val();
            let hasWholesale = $('#has_wholesale').is(':checked');

            if (type === 'service' || type === 'digital_pascabayar') {
                // Jasa & Pascabayar: Harga dinamis di kasir, HPP 0, tidak ada stok
                $('#costPriceContainer, #retailPriceContainer, #wholesaleToggleContainer, .wholesale-field, .stock-field, #rackContainer').hide();
                $('#p_cost, #p_retail, #p_wholesale, #p_min_wholesale, #p_stock, #p_min_stock_alert').val(0);
                $('#p_cost, #p_retail').prop('required', false);
            } 
            else if (type === 'digital_prabayar' || type === 'digital') {
                // Prabayar: Ada Harga Modal & Eceran. Tidak ada stok & rak.
                $('.stock-field, #rackContainer').hide();
                $('#p_stock, #p_min_stock_alert').val(0);
                $('#costPriceContainer, #retailPriceContainer, #wholesaleToggleContainer').show();
                $('#p_cost, #p_retail').prop('required', true);
                if (hasWholesale) $('.wholesale-field').show(); else $('.wholesale-field').hide();
            } 
            else {
                // Fisik: Tampilkan semua
                $('#costPriceContainer, #retailPriceContainer, #wholesaleToggleContainer, .stock-field, #rackContainer').show();
                $('#p_cost, #p_retail').prop('required', true);
                if (hasWholesale) $('.wholesale-field').show(); else $('.wholesale-field').hide();
            }
        }

        $('#p_type').change(adjustProductFormFields);
        $('#has_wholesale').change(adjustProductFormFields);

        $('#btnGenBarcode').click(function() {
            let catId = $('#p_category').val();
            if (!catId) return alert('Pilih Kategori terlebih dahulu!');
            $.get("{{ route('products.generate_barcode') }}", { product_type: $('#p_type').val(), category_id: catId })
             .done(function(res) { $('#p_barcode').val(res.barcode); });
        });

        $('#btnOpenAddModal').click(function() {
            $('#productModalTitle').text('Tambah Barang Baru');
            $('#productForm').attr('action', "{{ route('products.store') }}");
            $('#methodSpoof').html('');
            $('#productForm')[0].reset();
            $('#p_type').val('physical');
            $('#has_wholesale').prop('checked', false);
            $('#p_min_stock_alert').val(5); // Default alert
            $('#statusContainer').hide();
            adjustProductFormFields();
        });

        $(document).on('click', '.btn-edit', function() {
            let p = $(this).data('product');
            $('#productModalTitle').text('Edit Barang: ' + p.name);
            $('#productForm').attr('action', `/products/${p.id}`);
            $('#methodSpoof').html('@method("PUT")');

            $('#p_name').val(p.name);
            $('#p_category').val(p.category_id);
            $('#p_type').val(p.product_type);
            $('#p_barcode').val(p.barcode);
            $('#p_rack').val(p.rack_location);
            $('#p_cost').val(p.cost_price ? new Intl.NumberFormat('id-ID').format(p.cost_price) : 0);
            $('#p_retail').val(p.retail_price ? new Intl.NumberFormat('id-ID').format(p.retail_price) : 0);
            
            let hasGrosir = (p.wholesale_price > 0);
            $('#has_wholesale').prop('checked', hasGrosir);
            $('#p_wholesale').val(p.wholesale_price ? new Intl.NumberFormat('id-ID').format(p.wholesale_price) : 0);
            $('#p_min_wholesale').val(p.min_wholesale_qty);
            $('#p_stock').val(p.stock);
            
            // Masukkan nilai min_stock_alert dari data barang
            $('#p_min_stock_alert').val(p.min_stock_alert ?? 5);
            
            $('#statusContainer').show();
            $('#p_is_active').prop('checked', p.is_active == 1);
            adjustProductFormFields();
            $('#productModal').modal('show');
        });

        // ==========================================
        // TOMBOL HISTORI & GRAFIK (Membuka Riwayat)
        // ==========================================
        $(document).on('click', '.btn-history', function() {
            let id = $(this).data('id');
            $('#historyContent').html('<div class="spinner-border text-info" role="status"><span class="visually-hidden">Loading...</span></div>');
            $('#historyModal').modal('show');

            $.get(`/products/${id}/history`, function(res) {
                let html = `
                    <div class="row text-start align-items-center">
                        <div class="col-md-5 mb-3">
                            <strong class="d-block text-muted small">Nama Barang</strong>
                            <span class="fs-5 fw-bold">${res.product.name}</span>
                        </div>
                        <div class="col-md-7 mb-3">
                            <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="d-block text-muted small" style="font-size: 11px;">Supplier Terakhir:</strong>
                                    <span class="fw-bold text-dark">${res.last_supplier}</span>
                                </div>
                                <div class="text-end">
                                    <strong class="d-block text-muted small" style="font-size: 11px;">Faktur & Tanggal:</strong>
                                    ${res.last_invoice_id 
                                        ? `<a href="/purchases?show_faktur=${res.last_invoice_id}" class="fw-bold text-primary text-decoration-none" title="Buka Detail Faktur">#${res.last_invoice_number}</a>`
                                        : `<span class="fw-bold text-muted">-</span>`
                                    }
                                    <span class="d-block text-muted small mt-1">${res.last_purchase_date}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row text-start mb-2">
                        <div class="col-6">
                            <div class="border rounded p-2 text-center bg-white shadow-sm">
                                <strong class="d-block text-muted small">Total Terjual (Keluar)</strong>
                                <span class="fs-4 text-success fw-bold">${res.total_sold} <small class="text-muted fs-6">pcs</small></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-2 text-center bg-white shadow-sm">
                                <strong class="d-block text-muted small">Total Kulakan (Masuk)</strong>
                                <span class="fs-4 text-primary fw-bold">${res.total_purchased} <small class="text-muted fs-6">pcs</small></span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6 class="fw-bold mt-3 text-start">Riwayat Perubahan Harga (HPP)</h6>
                    <div class="table-responsive text-start">
                        <table class="table table-sm table-bordered table-hover" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Faktur</th>
                                    <th>Waktu</th>
                                    <th>Harga Lama</th>
                                    <th>Harga Baru</th>
                                    <th class="text-center">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                if(res.price_histories.length > 0) {
                    let histories = res.price_histories.reverse();
                    histories.forEach(h => {
                        // Bikin format tanggal manual d/m/Y untuk dicocokkan dengan data faktur
                        let d = new Date(h.created_at);
                        let day = ("0" + d.getDate()).slice(-2);
                        let month = ("0" + (d.getMonth() + 1)).slice(-2);
                        let year = d.getFullYear();
                        let hDateStr = `${day}/${month}/${year}`;
                        
                        let dateStr = d.toLocaleDateString('id-ID') + ' ' + d.toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
                        
                        let invoiceLink = '<span class="text-muted">-</span>';
                        
                        // SMART MATCHING: Cari faktur kulakan di tanggal yang sama dengan harga modal yang sama
                        let matchedPurchase = res.purchases.find(p => p.date === hDateStr && parseInt(p.cost_price) === parseInt(h.new_price));
                        
                        if (matchedPurchase && matchedPurchase.id) {
                            invoiceLink = `<a href="/purchases?show_faktur=${matchedPurchase.id}" class="fw-bold text-primary text-decoration-none" title="Buka Detail Faktur">${matchedPurchase.invoice}</a>`;
                        }

                        // Menentukan warna panah naik/turun
                        let arrow = '';
                        if (h.new_price > h.old_price) {
                            arrow = '<span class="text-danger fw-bold">↑ Naik</span>';
                        } else if (h.new_price < h.old_price) {
                            arrow = '<span class="text-success fw-bold">↓ Turun</span>';
                        } else {
                            arrow = '<span class="text-muted">-</span>';
                        }

                        let finalNote = h.note ? h.note : arrow;
                        if(matchedPurchase && h.note) {
                            finalNote = finalNote.replace(' dari Faktur Kulakan ' + matchedPurchase.invoice, '');
                        }

                        html += `<tr>
                            <td class="text-nowrap text-center">${invoiceLink}</td>
                            <td class="text-nowrap">${dateStr}</td>
                            <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(h.old_price)}</td>
                            <td class="text-end fw-bold">Rp ${new Intl.NumberFormat('id-ID').format(h.new_price)}</td>
                            <td class="text-center">${finalNote}</td>
                        </tr>`;
                    });
                } else {
                    html += `<tr><td colspan="5" class="text-center text-muted py-3">Belum ada riwayat perubahan harga modal (HPP).</td></tr>`;
                }
                html += `</tbody></table></div>`;

                $('#historyContent').html(html);
                    }).fail(function() {
                        $('#historyContent').html('<div class="alert alert-danger">Gagal memuat riwayat produk!</div>');
                    });
        });

        $(document).on('input', '.format-rupiah', function() {
            let val = $(this).val().replace(/[^0-9]/g, '');
            $(this).val(val ? new Intl.NumberFormat('id-ID').format(val) : '');
        });

        $('#productForm').submit(function(e) {
            let type = $('#p_type').val();
            if (type !== 'service' && type !== 'digital_pascabayar') {
                let cost = parseInt($('#p_cost').val().replace(/[^0-9]/g, '')) || 0;
                let retail = parseInt($('#p_retail').val().replace(/[^0-9]/g, '')) || 0;
                if (retail < cost) {
                    e.preventDefault(); 
                    alert('Tidak bisa simpan, cek harga!\nHarga Eceran tidak boleh lebih murah dari HPP.'); 
                    return false;
                }
            }
            $(this).find('.format-rupiah').each(function() { $(this).val($(this).val().replace(/[^0-9]/g, '')); });
        });

        // ==========================================
        // KELOLA KATEGORI (AJAX)
        // ==========================================
        function loadCategories() {
            $.get("{{ route('categories.index') }}", function(data) {
                let tbody = '';
                let selectOptions = '<option value="">-- Pilih Kategori --</option>';
                data.forEach(function(cat) {
                    tbody += `<tr>
                        <td class="ps-3 fw-bold" id="cat-name-${cat.id}">${cat.name}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-warning px-2 py-1 me-1" onclick="editCategory(${cat.id}, '${cat.name}')">✏️</button>
                            <button type="button" class="btn btn-sm btn-danger px-2 py-1" onclick="deleteCategory(${cat.id})">🗑️</button>
                        </td>
                    </tr>`;
                    selectOptions += `<option value="${cat.id}">${cat.name}</option>`;
                });
                $('#categoryTableBody').html(tbody);
                $('#p_category').html(selectOptions);
            });
        }

        $('#categoryModal').on('show.bs.modal', function () {
            $('#newCategoryName').val('');
            loadCategories();
        });

        $('#btnSaveCategory').click(function() {
            let name = $('#newCategoryName').val().trim();
            if (!name) return alert('Nama kategori wajib diisi!');
            let btn = $(this); btn.prop('disabled', true).text('...');
            $.post("{{ route('categories.store') }}", { name: name })
             .done(function() {
                $('#newCategoryName').val('').focus();
                loadCategories();
                btn.prop('disabled', false).text('Tambah');
             }).fail(function() { 
                alert('Gagal menyimpan kategori!'); 
                btn.prop('disabled', false).text('Tambah'); 
             });
        });

        window.deleteCategory = function(id) {
            if(confirm('Yakin hapus kategori ini?')) {
                $.ajax({ url: '/categories/' + id, type: 'DELETE', success: loadCategories, error: function(err) { alert(err.responseJSON?.error || 'Gagal!'); } });
            }
        };

        window.editCategory = function(id, oldName) {
            $('#cat-name-' + id).html(`<div class="input-group input-group-sm"><input type="text" class="form-control" id="edit-cat-${id}" value="${oldName}"><button type="button" class="btn btn-success" onclick="updateCategory(${id})">OK</button></div>`);
        };

        window.updateCategory = function(id) {
            let newName = $('#edit-cat-' + id).val().trim();
            if (!newName) return;
            $.ajax({ url: '/categories/' + id, type: 'PUT', data: { name: newName }, success: loadCategories });
        };
    });
</script>
@endsection