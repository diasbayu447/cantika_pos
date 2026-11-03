@extends('layouts.app')

@section('title', 'Manajemen Promo & Diskon')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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
        <h5 class="m-0 fw-bold" style="color: #333;">Daftar Promo & Voucher Diskon</h5>
        <button class="btn btn-sm fw-bold px-3" data-bs-toggle="modal" data-bs-target="#discountModal" id="btnAddDiscount" style="background-color: #f2a6b0; color: white; border-radius: 8px;">
            + Tambah Promo Baru
        </button>
    </div>

    <div class="table-responsive">
        <table id="discountsTable" class="table table-hover align-middle" style="font-size: 13px; width: 100%;">
            <thead class="table-light">
                <tr>
                    <th width="4%">No</th>
                    <th>Nama Promo</th>
                    <th>Tipe & Lingkup</th>
                    <th>Kode Voucher</th>
                    <th>Nilai Potongan</th>
                    <th>Min. Belanja</th>
                    <th>Sisa Kuota / Periode</th>
                    <th class="text-center">Status</th>
                    <th width="15%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($discounts as $index => $d)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="fw-bold text-dark">{{ $d->name }}</td>
                    <td>
                        @if($d->type === 'voucher')
                            <span class="badge bg-primary">Voucher</span>
                        @else
                            <span class="badge bg-info text-dark">Event Periode</span>
                        @endif
                        <br>
                        <small class="text-muted">{{ $d->scope === 'product' ? 'Cuci Gudang' : 'Total Belanja' }}</small>
                    </td>
                    <td>
                        @if($d->code)
                            <span class="badge bg-dark font-monospace">{{ $d->code }}</span>
                        @elseif($d->type === 'voucher')
                            <span class="badge bg-secondary">Batch Unik</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="fw-bold text-success">
                        @if($d->discount_type === 'nominal')
                            Rp {{ number_format($d->amount, 0, ',', '.') }}
                        @else
                            {{ $d->amount }}%
                        @endif
                    </td>
                    <td>Rp {{ number_format($d->min_purchase, 0, ',', '.') }}</td>
                    <td>
                        @if($d->type === 'voucher')
                            <span class="badge bg-secondary">
                                {{ $d->unused_voucher_count ?? 0 }} Tersedia
                            </span>
                        @else
                            <small class="text-muted">
                                {{ $d->start_date ? \Carbon\Carbon::parse($d->start_date)->format('d/m/Y') : 'Awal' }}
                                s/d 
                                {{ $d->end_date ? \Carbon\Carbon::parse($d->end_date)->format('d/m/Y') : 'Seterusnya' }}
                            </small>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($d->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            @if($d->type === 'voucher')
                            <button class="btn btn-sm btn-outline-primary btn-view-codes px-2 py-1" 
                                    data-id="{{ $d->id }}" 
                                    data-name="{{ $d->name }}"
                                    title="Lihat Daftar Kode Kupon Unik">
                                🎟️ Kode
                            </button>
                            @endif

                            <button class="btn btn-sm btn-outline-secondary btn-edit px-2 py-1" 
                                    data-discount="{{ json_encode($d) }}"
                                    title="Edit Data">
                                ✏️
                            </button>

                            <form action="{{ route('discounts.toggle', $d->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $d->is_active ? 'btn-outline-warning text-dark' : 'btn-outline-success' }} px-2 py-1" 
                                        title="{{ $d->is_active ? 'Nonaktifkan Promo' : 'Aktifkan Kembali' }}">
                                    {{ $d->is_active ? '⏸️' : '▶️' }}
                                </button>
                            </form>

                            <form action="{{ route('discounts.destroy', $d->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" 
                                        onclick="return confirm('Hapus promo diskon ini?');">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL FORM TAMBAH / EDIT DISKON                -->
<!-- ============================================== -->
<div class="modal fade" id="discountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="discountModalTitle" style="color: #d17885;">Tambah Promo Diskon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="discountForm" method="POST" action="{{ route('discounts.store') }}">
                @csrf
                <div id="methodSpoof"></div>
                <div class="modal-body pb-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Nama Promo <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="d_name" class="form-control" required placeholder="Contoh: Voucher Diskon Gajian / Obral Casing">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted small">Skema Diskon <span class="text-danger">*</span></label>
                            <select name="type" id="d_type" class="form-select" required>
                                <option value="voucher">Voucher Kupon</option>
                                <option value="period">Event Periode</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted small">Lingkup Potongan <span class="text-danger">*</span></label>
                            <select name="scope" id="d_scope" class="form-select" required>
                                <option value="cart">Total Belanja</option>
                                <option value="product">Cuci Gudang (Barang)</option>
                            </select>
                        </div>

                        <div class="col-12" id="productSelectionContainer" style="display: none;">
                            <label class="form-label text-muted small fw-bold">Pilih Barang yang Dicuci Gudang</label>
                            <select name="product_ids[]" id="promoProducts" class="form-select select2-products" multiple="multiple" style="width: 100%;">
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}">
                                        {{ $p->barcode }} | {{ $p->name }} (Normal: Rp {{ number_format($p->retail_price, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Pilih satu atau lebih barang yang akan dipotong harga khusus.</small>
                        </div>

                        <div class="col-md-6" id="voucherCodeContainer">
                            <label class="form-label text-muted small">Kode Voucher Tunggal</label>
                            <input type="text" name="code" id="d_code" class="form-control font-monospace text-uppercase" placeholder="Kosongkan jika buat kode unik otomatis">
                        </div>
                        <div class="col-md-6" id="batchQtyContainer">
                            <label class="form-label text-muted small">Generate Kupon Unik Otomatis</label>
                            <input type="text" name="batch_qty" id="d_batch_qty" class="form-control" placeholder="Contoh: 50" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>

                        <div class="col-md-6" id="periodStartContainer" style="display: none;">
                            <label class="form-label text-muted small">Tanggal Mulai Berlaku</label>
                            <input type="date" name="start_date" id="d_start_date" class="form-control">
                        </div>
                        <div class="col-md-6" id="periodEndContainer" style="display: none;">
                            <label class="form-label text-muted small">Tanggal Berakhir</label>
                            <input type="date" name="end_date" id="d_end_date" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-muted small">Bentuk Potongan <span class="text-danger">*</span></label>
                            <select name="discount_type" id="d_discount_type" class="form-select" required>
                                <option value="nominal">Nominal Rupiah (Rp)</option>
                                <option value="percentage">Persentase (%)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Besaran Potongan <span class="text-danger">*</span></label>
                            <input type="text" name="amount" id="d_amount" class="form-control format-rupiah-pos" required placeholder="Contoh: 10.000 atau 10">
                        </div>
                        <div class="col-md-4" id="minPurchaseContainer">
                            <label class="form-label text-muted small">Minimal Total Belanja (Rp)</label>
                            <input type="text" name="min_purchase" id="d_min_purchase" class="form-control format-rupiah-pos" placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn fw-bold px-4" style="background-color: #f2a6b0; color: white; border-radius: 8px;">Simpan Promo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL DAFTAR KODE KUPON UNIK BATCH             -->
<!-- ============================================== -->
<div class="modal fade" id="voucherCodesModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h6 class="modal-title fw-bold" id="codesModalTitle" style="color: #d17885;">Daftar Kupon Unik</h6>
                    <small class="text-muted" id="codesModalSubtitle"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pb-3">
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle" style="font-size: 13px;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>No</th>
                                <th>Kode Kupon</th>
                                <th class="text-center">Status</th>
                                <th>Dipakai Pada</th>
                            </tr>
                        </thead>
                        <tbody id="codesTableBody">
                            <!-- Diisi lewat AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btnPrintVouchers" target="_blank" class="btn btn-sm btn-dark fw-bold">
                    🖨️ Cetak Lembaran Kupon (A4)
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#discountsTable').DataTable({
            language: { search: "Cari Promo:", lengthMenu: "Tampil _MENU_", info: "_START_ - _END_ dari _TOTAL_" }
        });

        // Inisialisasi Select2
        $('.select2-products').select2({
            placeholder: "Ketik nama atau barcode barang...",
            allowClear: true,
            dropdownParent: $('#discountModal')
        });

        function adjustFields() {
            let type = $('#d_type').val();
            let scope = $('#d_scope').val();

            if (type === 'voucher') {
                $('#voucherCodeContainer, #batchQtyContainer').show();
                $('#periodStartContainer, #periodEndContainer').hide();
            } else {
                $('#voucherCodeContainer, #batchQtyContainer').hide();
                $('#periodStartContainer, #periodEndContainer').show();
                $('#d_code, #d_batch_qty').val('');
            }

            if (scope === 'product') {
                $('#productSelectionContainer').show();
                $('#minPurchaseContainer').hide();
                $('#d_min_purchase').val('');
            } else {
                $('#productSelectionContainer').hide();
                $('#minPurchaseContainer').show();
                $('#promoProducts').val(null).trigger('change');
            }
        }

        $('#d_type, #d_scope').on('change', function() {
            adjustFields();
        });

        // Event listener format rupiah langsung
        $(document).on('input', '.format-rupiah-pos', function() {
            if ($(this).attr('id') === 'd_amount' && $('#d_discount_type').val() === 'percentage') {
                let val = $(this).val().replace(/[^0-9]/g, '');
                if (parseInt(val) > 100) val = '100';
                $(this).val(val);
            } else {
                let val = $(this).val().replace(/[^0-9]/g, '');
                $(this).val(val ? new Intl.NumberFormat('id-ID').format(val) : '');
            }
        });

        $('#btnAddDiscount').click(function() {
            $('#discountModalTitle').text('Tambah Promo Diskon Baru');
            $('#discountForm').attr('action', "{{ route('discounts.store') }}");
            $('#methodSpoof').html('');
            $('#discountForm')[0].reset();
            
            $('#d_type').val('voucher');
            $('#d_scope').val('cart');
            $('#d_amount').val('');
            $('#d_min_purchase').val('');
            $('#promoProducts').val(null).trigger('change');
            adjustFields();
        });

        $(document).on('click', '.btn-edit', function() {
            let d = $(this).data('discount');
            $('#discountModalTitle').text('Edit Promo: ' + d.name);
            $('#discountForm').attr('action', `/discounts/${d.id}`);
            $('#methodSpoof').html('@method("PUT")');

            $('#d_name').val(d.name);
            $('#d_type').val(d.type);
            $('#d_scope').val(d.scope);
            $('#d_code').val(d.code);
            $('#d_start_date').val(d.start_date);
            $('#d_end_date').val(d.end_date);
            $('#d_discount_type').val(d.discount_type);
            
            // Format angka nominal jika bukan persentase
            if (d.discount_type === 'nominal') {
                $('#d_amount').val(d.amount ? new Intl.NumberFormat('id-ID').format(d.amount) : '');
            } else {
                $('#d_amount').val(d.amount);
            }

            $('#d_min_purchase').val(d.min_purchase > 0 ? new Intl.NumberFormat('id-ID').format(d.min_purchase) : '');
            $('#d_batch_qty').val('');

            adjustFields();
            $('#discountModal').modal('show');
        });

        // Bersihkan titik sebelum submit form
        $('#discountForm').submit(function() {
            $('#d_min_purchase').val($('#d_min_purchase').val().replace(/[^0-9]/g, ''));
            $('#d_amount').val($('#d_amount').val().replace(/[^0-9]/g, ''));
        });

        // Tampilkan modal daftar kode
        $(document).on('click', '.btn-view-codes', function() {
            let promoId = $(this).data('id');
            let promoName = $(this).data('name');

            $('#codesModalTitle').text('Daftar Kode Kupon');
            $('#codesModalSubtitle').text(promoName);
            $('#codesTableBody').html('<tr><td colspan="4" class="text-center py-3 text-muted">Memuat daftar kode...</td></tr>');
            
            $('#btnPrintVouchers').attr('href', '/discounts/' + promoId + '/print');
            $('#voucherCodesModal').modal('show');

            $.ajax({
                url: `/discounts/${promoId}/codes`,
                type: 'GET',
                success: function(codes) {
                    let rows = '';
                    if (codes.length === 0) {
                        rows = '<tr><td colspan="4" class="text-center py-3 text-muted">Belum ada kode kupon yang di-generate.</td></tr>';
                    } else {
                        codes.forEach(function(c, idx) {
                            let statusBadge = c.is_used 
                                ? '<span class="badge bg-danger">Hangus</span>' 
                                : '<span class="badge bg-success">Tersedia</span>';
                            
                            let usedDate = c.used_at ? new Date(c.used_at).toLocaleDateString('id-ID') : '-';

                            rows += `
                                <tr>
                                    <td>${idx + 1}</td>
                                    <td><span class="badge bg-dark font-monospace fs-6">${c.code}</span></td>
                                    <td class="text-center">${statusBadge}</td>
                                    <td><small class="text-muted">${usedDate}</small></td>
                                </tr>
                            `;
                        });
                    }
                    $('#codesTableBody').html(rows);
                },
                error: function() {
                    $('#codesTableBody').html('<tr><td colspan="4" class="text-center py-3 text-danger">Gagal memuat kode kupon!</td></tr>');
                }
            });
        });

        // Kontrol input persentase maksimal 100%
        $('#d_discount_type').change(function() {
            let curVal = $('#d_amount').val().replace(/[^0-9]/g, '');
            if ($(this).val() === 'percentage') {
                $('#d_amount').attr('placeholder', 'Maksimal 100%');
                if (parseInt(curVal) > 100) $('#d_amount').val('100');
            } else {
                $('#d_amount').attr('placeholder', 'Nominal Rupiah (contoh: 10.000)');
                if (curVal) $('#d_amount').val(new Intl.NumberFormat('id-ID').format(curVal));
            }
        });
    });
</script>
@endsection