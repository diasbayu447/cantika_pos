@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">📥 Input Faktur Pembelian (Kulakan)</h3>
            <p class="text-muted small m-0">Catat barang masuk, harga modal baru, dan tagihan dari supplier.</p>
        </div>
        <a href="{{ route('purchases.index') }}" class="btn btn-secondary fw-bold px-4 py-2" style="border-radius: 8px;">Kembali</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger fw-bold">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
        <div class="card-body p-4">
            <form action="{{ route('purchases.store') }}" method="POST" id="purchaseForm">
                @csrf
                
                <div class="row g-3 mb-4 p-3 rounded" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">No. Faktur <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="invoice_number" id="invoiceNumber" class="form-control fw-bold font-monospace" value="{{ $nextInvoiceNumber }}" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="randomizeInvoice()">🔄</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Pilih Supplier <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <div style="flex-grow: 1;">
                                <select name="supplier_id" id="supplierSelect" class="form-select select2-supplier" required>
                                    <option value=""></option>
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#quickSupplierModal" title="Tambah Supplier Baru">+</button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">Tanggal Masuk <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Metode Bayar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select name="payment_method" id="paymentMethod" class="form-select" required>
                                <option value="cash">Tunai / Lunas</option>
                                <option value="credit">Tempo / Utang</option>
                            </select>
                            <input type="date" name="due_date" id="dueDate" class="form-control border-danger" style="display: none;" title="Tanggal Jatuh Tempo">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark m-0">Daftar Barang Masuk</h6>
                    <button type="button" class="btn btn-sm text-white fw-bold px-3" style="background-color: #d17885; border-radius: 6px;" id="btnAddRow">+ Tambah Baris</button>
                </div>
                
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle" id="purchaseTable">
                        <thead class="table-light">
                            <tr>
                                <th width="45%">Cari Barang (Fisik)</th>
                                <th width="15%">Qty Masuk</th>
                                <th width="25%">Harga Modal / Pcs (Rp)</th>
                                <th width="15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- PERBAIKAN: name="products" SESUAI PERMINTAAN CONTROLLER -->
                            <tr class="purchase-row">
                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">🔍</span>
                                        <input type="hidden" name="products[0][product_id]" class="product-id" required>
                                        <input type="text" class="form-control product-name bg-white" placeholder="Klik untuk mencari barang..." readonly style="cursor: pointer;" required>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" name="products[0][qty]" class="form-control fw-bold text-center product-qty" min="1" placeholder="1" required>
                                </td>
                                <td>
                                    <input type="text" name="products[0][cost_price]" class="form-control fw-bold format-rupiah product-cost" placeholder="0" required>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger btn-remove-row" disabled>Hapus</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn fw-bold text-white px-5 py-3 shadow-sm" style="background-color: #d17885; border-radius: 10px; font-size: 16px;">
                        💾 Simpan & Masukkan Stok
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 1: PENCARIAN BARANG                  -->
<!-- ========================================== -->
<div class="modal fade" id="searchProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary">🔍 Cari Barang (Gudang)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pb-4">
                <div class="d-flex justify-content-between mb-3">
                    <div class="input-group w-75">
                        <span class="input-group-text bg-light fw-bold text-muted">Kata Kunci</span>
                        <input type="text" id="modalSearchInput" class="form-control" placeholder="Ketik nama atau barcode..." autocomplete="off">
                    </div>
                    <button type="button" class="btn btn-primary fw-bold px-3" data-bs-toggle="modal" data-bs-target="#quickProductModal">
                        ✨ Tambah Barang Baru
                    </button>
                </div>
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover align-middle" style="font-size: 13px;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Barcode</th>
                                <th>Nama Barang</th>
                                <th class="text-center">Stok</th>
                                <th>H. Modal Lama</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="modalSearchResults">
                            <tr><td colspan="5" class="text-center text-muted py-4">Ketik nama barang di atas untuk mencari.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 2: TAMBAH SUPPLIER BARU              -->
<!-- ========================================== -->
<div class="modal fade" id="quickSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark">📦 Tambah Supplier Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pb-4 pt-3">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Nama Perusahaan / Toko *</label>
                    <input type="text" id="qSupName" class="form-control" required placeholder="Contoh: CV Maju Mapan">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold small">PIC (Nama Sales)</label>
                        <input type="text" id="qSupPic" class="form-control" placeholder="Contoh: Mas Doni">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold small">No. WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">+62</span>
                            <input type="text" id="qSupPhone" class="form-control font-monospace" placeholder="812..." oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold small">Alamat Lengkap</label>
                    <textarea id="qSupAddress" class="form-control" rows="2" placeholder="Alamat gudang supplier..."></textarea>
                </div>
                <button type="button" id="btnSaveQuickSupplier" class="btn w-100 fw-bold text-white py-2" style="background-color: #d17885; border-radius: 8px;">Simpan & Pilih Supplier</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 3: TAMBAH BARANG BARU (LENGKAP)      -->
<!-- ========================================== -->
<div class="modal fade" id="quickProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark">✨ Tambah Barang Baru (Fisik)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pb-4 pt-3">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Nama Barang *</label>
                        <input type="text" id="qProdName" class="form-control" placeholder="Contoh: Kabel Data Type-C" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Merek (Opsional)</label>
                        <input type="text" id="qProdBrand" class="form-control" placeholder="Contoh: Robot">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Kategori *</label>
                        <select id="qProdCategory" class="form-select">
                            @foreach(\App\Models\Category::orderBy('name')->get() as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Barcode (Opsional)</label>
                        <input type="text" id="qProdBarcode" class="form-control font-monospace" placeholder="Scan / Kosongkan">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Lokasi Rak (Opsional)</label>
                        <input type="text" id="qProdRack" class="form-control" placeholder="Contoh: Rak A-1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Batas Stok Menipis</label>
                        <input type="number" id="qProdLowStock" class="form-control" value="5">
                    </div>
                </div>

                <div class="p-3 rounded mt-2 mb-4" style="background-color: #f8f9fa; border: 1px dashed #ccc;">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-danger">Harga Modal (HPP) *</label>
                            <input type="text" id="qProdCost" class="form-control format-rupiah fw-bold" placeholder="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-success">Harga Eceran *</label>
                            <input type="text" id="qProdRetail" class="form-control format-rupiah fw-bold" placeholder="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-primary">Harga Grosir</label>
                            <input type="text" id="qProdWholesale" class="form-control format-rupiah fw-bold" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-primary">Min. Qty Grosir</label>
                            <input type="number" id="qProdMinWholesale" class="form-control fw-bold" placeholder="0">
                        </div>
                    </div>
                </div>
                <button type="button" id="btnSaveQuickProduct" class="btn w-100 fw-bold text-white py-2" style="background-color: #d17885; border-radius: 8px;">Simpan & Masukkan ke Faktur</button>
            </div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single { height: 38px !important; border: 1px solid #ced4da !important; border-radius: 0.375rem !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px !important; color: #212529 !important; font-weight: bold; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px !important; }
</style>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    let currentRowIndex = 0;
    let activeRowElement = null;

    function randomizeInvoice() {
        let rand = Math.floor(1000 + Math.random() * 9000);
        let date = new Date().toISOString().slice(0, 10).replace(/-/g, "");
        $('#invoiceNumber').val('FCT-' + date + '-' + rand);
    }

    $(document).ready(function() {
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        if ($.fn.select2) {
            $('.select2-supplier').select2({ placeholder: "-- Ketik / Pilih Supplier --", allowClear: true });
        }

        $('#paymentMethod').change(function() {
            if ($(this).val() === 'credit')$('#dueDate').show().prop('required', true);
            else $('#dueDate').hide().prop('required', false).val('');
        });

        $('#btnAddRow').click(function() {
            currentRowIndex++;
            let newRow = `<tr class="purchase-row">
                <td>
                    <div class="input-group">
                        <span class="input-group-text bg-light">🔍</span>
                        <input type="hidden" name="products[${currentRowIndex}][product_id]" class="product-id" required>
                        <input type="text" class="form-control product-name bg-white" placeholder="Klik untuk mencari barang..." readonly style="cursor: pointer;" required>
                    </div>
                </td>
                <td><input type="number" name="products[${currentRowIndex}][qty]" class="form-control fw-bold text-center product-qty" min="1" placeholder="1" required></td>
                <td><input type="text" name="products[${currentRowIndex}][cost_price]" class="form-control fw-bold format-rupiah product-cost" placeholder="0" required></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-remove-row">Hapus</button></td>
            </tr>`;
            $('#purchaseTable tbody').append(newRow);
            updateRemoveButtons();
        });

        $(document).on('click', '.btn-remove-row', function() {$(this).closest('tr').remove(); updateRemoveButtons(); });
        function updateRemoveButtons() { $('.btn-remove-row').prop('disabled',$('#purchaseTable tbody tr').length <= 1); }

        $(document).on('click', '.product-name', function() {
            activeRowElement = $(this).closest('tr');$('#searchProductModal').modal('show');
        });

        let searchTimeout = null;
        $('#modalSearchInput').on('keyup', function() {
            let kw = $(this).val().trim();
            clearTimeout(searchTimeout);
            if (kw.length < 2) { 
                $('#modalSearchResults').html('<tr><td colspan="5" class="text-center text-muted py-4">Ketik minimal 2 huruf...</td></tr>'); 
                return; 
            }
            
            searchTimeout = setTimeout(function() {
                $('#modalSearchResults').html('<tr><td colspan="5" class="text-center text-muted py-4">Mencari...</td></tr>');
                $.ajax({
                    url: "{{ route('purchases.search_product') }}", 
                    type: "GET", 
                    data: { keyword: kw },
                    success: function(res) {
                        let tbody = '';
                        if (res.length === 0) {
                            tbody = '<tr><td colspan="5" class="text-center text-muted py-4">Barang tidak ditemukan.</td></tr>';
                        } else {
                            res.forEach(function(item) {
                                let badge = item.stock > 0 ? `<span class="badge bg-success">${item.stock}</span>` : `<span class="badge bg-danger">0</span>`;
                                let code = item.barcode ? item.barcode : '-';
                                tbody += `<tr>
                                    <td class="font-monospace">${code}</td>
                                    <td class="fw-bold">${item.name}</td>
                                    <td class="text-center">${badge}</td>
                                    <td class="fw-bold text-muted">Rp ${new Intl.NumberFormat('id-ID').format(item.cost_price)}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary fw-bold px-3 btn-pilih-barang" 
                                                data-id="${item.id}" 
                                                data-name="${item.name} (${code})" 
                                                data-cost="${item.cost_price}">
                                            Pilih
                                        </button>
                                    </td>
                                </tr>`;
                            });
                        }
                        $('#modalSearchResults').html(tbody);
                    },
                    error: function(err) {
                        $('#modalSearchResults').html('<tr><td colspan="5" class="text-center text-danger py-4">Gagal memuat barang. Silakan coba lagi.</td></tr>');
                    }
                });
            }, 300);
        });

        $(document).on('click', '.btn-pilih-barang', function() {
            if (activeRowElement) {
                activeRowElement.find('.product-id').val($(this).data('id'));
                activeRowElement.find('.product-name').val($(this).data('name'));
                activeRowElement.find('.product-cost').val(new Intl.NumberFormat('id-ID').format($(this).data('cost')));
                activeRowElement.find('.product-qty').focus();
            }
            $('#searchProductModal').modal('hide');
        });
        $('#searchProductModal').on('shown.bs.modal', function () { $('#modalSearchInput').val('').focus(); $('#modalSearchResults').html('<tr><td colspan="5" class="text-center text-muted py-4">Ketik nama...</td></tr>'); });

        // AJAX Quick Supplier
        $('#btnSaveQuickSupplier').click(function() {
            let name = $('#qSupName').val().trim();
            if(!name) return alert('Nama perusahaan wajib diisi!');
            let btn = $(this); btn.prop('disabled', true).text('Menyimpan...');
            $.post("{{ route('purchases.quick_supplier') }}", { name: name, pic: $('#qSupPic').val(), phone: $('#qSupPhone').val(), address: $('#qSupAddress').val() })
             .done(function(res) {
                 let opt = new Option(res.supplier.name, res.supplier.id, true, true);
                 $('#supplierSelect').append(opt).trigger('change');
                 $('#quickSupplierModal').modal('hide');
                 $('#qSupName, #qSupPic, #qSupPhone, #qSupAddress').val('');
                 btn.prop('disabled', false).text('Simpan & Pilih Supplier');
             }).fail(function() { alert('Gagal!'); btn.prop('disabled', false).text('Simpan & Pilih Supplier'); });
        });

        // AJAX Quick Product
        $('#btnSaveQuickProduct').click(function() {
            let name = $('#qProdName').val().trim();
            let cost = $('#qProdCost').val().replace(/[^0-9]/g, '');
            let retail = $('#qProdRetail').val().replace(/[^0-9]/g, '');
            if(!name || !cost || !retail) return alert('Nama, Modal (HPP), dan Eceran wajib diisi!');
            
            let btn = $(this); btn.prop('disabled', true).text('Menyimpan...');$.post("{{ route('purchases.quick_product') }}", {
                name: name, brand: $('#qProdBrand').val(), category_id: $('#qProdCategory').val(),
                barcode: $('#qProdBarcode').val(), rack: $('#qProdRack').val(),
                low_stock_threshold: $('#qProdLowStock').val(),
                cost_price: cost, retail_price: retail,
                wholesale_price: $('#qProdWholesale').val().replace(/[^0-9]/g, ''),
                min_wholesale_qty: $('#qProdMinWholesale').val()
            }).done(function(res) {
                if (activeRowElement) {
                    activeRowElement.find('.product-id').val(res.product.id);
                    activeRowElement.find('.product-name').val(res.product.name + ' (' + (res.product.barcode || '-') + ')');
                    activeRowElement.find('.product-cost').val(new Intl.NumberFormat('id-ID').format(res.product.cost_price));
                    activeRowElement.find('.product-qty').focus();
                }
                $('#quickProductModal, #searchProductModal').modal('hide');
                $('#qProdName, #qProdBrand, #qProdBarcode, #qProdRack, #qProdCost, #qProdRetail, #qProdWholesale, #qProdMinWholesale').val('');
                btn.prop('disabled', false).text('Simpan & Masukkan ke Faktur');
            }).fail(function() { alert('Gagal menyimpan barang!'); btn.prop('disabled', false).text('Simpan & Masukkan ke Faktur'); });
        });

        $(document).on('input', '.format-rupiah', function() { let v = $(this).val().replace(/[^0-9]/g, '');$(this).val(v ? new Intl.NumberFormat('id-ID').format(v) : ''); });
        
        $('#purchaseForm').submit(function(e) {
            let isValid = true;
            $('.product-id').each(function() { if (!$(this).val()) isValid = false; });
            if (!isValid) { e.preventDefault(); return alert('Ada baris barang kosong! Pilih barang atau hapus barisnya.'); }
            $(this).find('.format-rupiah').each(function() { $(this).val($(this).val().replace(/[^0-9]/g, '')); });
        });
    });
</script>
@endsection