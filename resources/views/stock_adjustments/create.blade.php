@extends('layouts.app')

@section('title', 'Form Stock Opname')

@section('content')
<style>
    /* Desain melayang untuk hasil pencarian */
    .search-results {
        display: none; 
        z-index: 1050; 
        max-height: 250px; 
        overflow-y: auto; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border-radius: 8px;
    }
    .product-item { cursor: pointer; transition: background-color 0.2s; }
    .product-item:hover { background-color: #fdf2f4; color: #d17885; font-weight: bold; }
</style>

<div class="card border-0 p-4 mx-auto" style="max-width: 600px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
    <h5 class="mb-4 text-center fw-bold" style="color: #333;">Penyesuaian Stok (Opname)</h5>

    @if($errors->any())
        <div class="alert alert-danger" style="border-radius: 10px;">
            <ul class="mb-0">
                @foreach($errors->all() as $error) <li>{{$error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('stock-adjustments.store') }}" method="POST">
        @csrf
        
        <!-- Area Autocomplete Kustom -->
        <div class="mb-4 position-relative">
            <label class="form-label text-muted">Cari Barang Fisik <span class="text-danger">*</span></label>
            
            <!-- Input yang akan dikirim ke database (Tersembunyi) -->
            <input type="hidden" name="product_id" id="product_id" required>
            
            <!-- Input murni untuk diketik oleh user -->
            <input type="text" id="productSearch" class="form-control form-control-lg" style="border-radius: 8px;" placeholder="Mulai ketik barcode atau nama barang..." autocomplete="off">
            
            <!-- Tempat hasil pencarian melayang -->
            <ul id="searchResults" class="list-group position-absolute w-100 mt-1 search-results"></ul>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label text-muted">Stok Sistem</label>
                <input type="text" id="systemStock" class="form-control text-center bg-light fw-bold" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted">Stok Fisik <span class="text-danger">*</span></label>
                <input type="number" name="actual_stock" id="actualStock" class="form-control text-center fw-bold" min="0" required>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted">Selisih</label>
                <input type="text" id="diffStock" class="form-control text-center fw-bold" readonly>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label text-muted">Alasan Penyesuaian <span class="text-danger">*</span></label>
            <input type="text" name="reason" class="form-control" placeholder="Contoh: Rusak saat dipajang, Hilang, dll" required autocomplete="off">
        </div>

        <hr class="text-muted">

        <div class="mb-4 p-3 rounded" style="background-color: #fdf2f4; border: 1px dashed #d17885;">
            <label class="form-label text-danger fw-bold">🛡️ Otorisasi Supervisor</label>
            <input type="password" name="pin" class="form-control text-center fw-bold form-control-lg" placeholder="Masukkan 6 Digit PIN" maxlength="6" required autocomplete="off">
        </div>

        <button type="submit" class="btn w-100 fw-bold" style="background-color: #f2a6b0; color: white; border-radius: 8px; padding: 12px;">Sesuaikan Stok</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
    $(document).ready(function() {
        // 1. Ambil data produk dari server PHP dan ubah menjadi format JavaScript (JSON)
        let products = @json($products);

        // 2. Logika Pencarian Saat Mengetik
        $('#productSearch').on('input', function() {
            let keyword = $(this).val().toLowerCase();
            let resultsHTML = '';
            
            if (keyword.length > 0) {
                // Saring data berdasarkan nama atau barcode
                let filtered = products.filter(p => 
                    (p.name && p.name.toLowerCase().includes(keyword)) || 
                    (p.barcode && p.barcode.toLowerCase().includes(keyword))
                );
                
                // Susun elemen daftar
                filtered.forEach(p => {
                    let displayName = `${p.barcode || '-'} | ${p.name}`;
                    resultsHTML += `<li class="list-group-item product-item" 
                                    data-id="${p.id}" data-stock="${p.stock}" data-name="${displayName}">
                                    <strong>${p.barcode || '-'}</strong> | ${p.name}
                                </li>`;
                });
                
                if (filtered.length === 0) {
                    resultsHTML = `<li class="list-group-item text-muted text-center">Barang tidak ditemukan</li>`;
                }
                
                $('#searchResults').html(resultsHTML).slideDown('fast');
            } else {
                // Jika input kosong, sembunyikan daftar dan reset form
                $('#searchResults').slideUp('fast');
                $('#product_id').val('');
                $('#systemStock').val('');
                calculateDiff();
            }
        });

        // 3. Logika Saat Item dari Dropdown Diklik
        $(document).on('click', '.product-item', function() {
            // Ambil data yang tersimpan di dalam tag HTML
            let id = $(this).data('id');
            let name = $(this).data('name');
            let stock = $(this).data('stock');
            
            // Isi form
            $('#productSearch').val(name);
            $('#product_id').val(id);
            $('#systemStock').val(stock);
            
            // Sembunyikan dropdown dan hitung selisih
            $('#searchResults').slideUp('fast');
            calculateDiff();
            
            // Fokuskan kursor ke input Stok Fisik
            $('#actualStock').focus();
        });

        // Sembunyikan dropdown jika user mengklik sembarang tempat di luar form
        $(document).click(function(e) {
            if (!$(e.target).closest('#productSearch, #searchResults').length) {
                $('#searchResults').slideUp('fast');
            }
        });

        // 4. Kalkulasi Otomatis Selisih Stok
        function calculateDiff() {
            let sys = parseInt($('#systemStock').val());
            let act = parseInt($('#actualStock').val());
            
            if(!isNaN(sys) && !isNaN(act)) {
                let diff = act - sys;
                let diffInput = $('#diffStock');
                diffInput.val((diff > 0 ? '+' : '') + diff);
                
                if(diff < 0) diffInput.removeClass('text-success').addClass('text-danger');
                else if(diff > 0) diffInput.removeClass('text-danger').addClass('text-success');
                else diffInput.removeClass('text-danger text-success');
            }
        }
        $('#actualStock').on('input', calculateDiff);
    });
</script>
@endsection