@extends('layouts.app')

@section('title', 'Riwayat Faktur Pembelian')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show fw-bold mb-3" style="border-radius: 10px;">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
<div class="card border-0 p-4" style="border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="m-0" style="color: #333; font-weight: 600;">Data Faktur Masuk</h5>
        <a href="{{ route('purchases.create') }}" class="btn" style="background-color: #f2a6b0; color: white; border-radius: 8px;">+ Input Faktur Baru</a>
    </div>

    <div class="table-responsive">
        <table id="purchasesTable" class="table table-hover align-middle" style="font-size: 14px;">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>No. Faktur</th>
                    <th>Supplier</th>
                    <th>Total Nilai</th>
                    <th>Metode Bayar</th>
                    <th>Admin Input</th>
                    <th class="text-center" width="10%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchases as $purchase)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d/m/Y') }}</td>
                    <td class="fw-bold">{{ $purchase->invoice_number }}
                        <span class="d-none">
                            @foreach($purchase->details as $detail)
                                {{ $detail->product->name ?? ($detail->product_name ?? '') }} 
                                {{ $detail->product->barcode ?? '' }}
                            @endforeach
                        </span>
                    </td>
                    <td>{{ $purchase->supplier->name ?? '-' }}</td>
                    <td class="text-success fw-bold">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</td>
                    <td>
                        @if($purchase->payment_method === 'tempo')
                            @if($purchase->payment_status === 'paid')
                                <!-- Jika tempo dan sudah dilunasi -->
                                <span class="badge bg-success" title="Dilunasi pada: {{ $purchase->paid_at ? \Carbon\Carbon::parse($purchase->paid_at)->format('d/m/Y H:i') : '-' }}">
                                    Tempo / Lunas
                                </span>
                            @else
                                <!-- Jika tempo dan masih menunggak -->
                                <span class="badge bg-warning text-dark">
                                    Tempo ({{ $purchase->due_date ? \Carbon\Carbon::parse($purchase->due_date)->format('d/m/Y') : '-' }})
                                </span>
                            @endif
                        @else
                            <span class="badge bg-success">Tunai / Lunas</span>
                        @endif
                    </td>
                    <td class="text-muted">{{ $purchase->user->name ?? '-' }}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-info text-white fw-bold px-2 py-1 btn-detail-faktur" data-id="{{ $purchase->id }}">
                            Detail
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL DETAIL FAKTUR PEMBELIAN -->
<div class="modal fade" id="purchaseDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold text-primary">📦 Rincian Faktur Pembelian</h5>
                    <span class="text-muted small" id="dtlNoFaktur">-</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pb-4">
                <!-- Info Header Faktur -->
                <div class="row g-2 mb-3 p-3 bg-light rounded" style="font-size: 13px;">
                    <div class="col-md-4">
                        <span class="text-muted d-block">Supplier:</span>
                        <strong id="dtlSupplier">-</strong>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted d-block">Metode Bayar:</span>
                        <span id="dtlMetode" class="badge bg-success">Tunai</span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted d-block">Admin Pencatat:</span>
                        <strong id="dtlAdmin">-</strong>
                    </div>
                </div>

                <!-- Tabel Item Barang yang Dibeli -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th>Nama Barang</th>
                                <th width="15%" class="text-center">Qty Masuk</th>
                                <th width="20%" class="text-end">Harga Beli Satuan</th>
                                <th width="20%" class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="dtlItemsBody">
                            <!-- Diisi via JavaScript -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end fw-bold">TOTAL PEMBELIAN:</th>
                                <th class="text-end fw-bold text-success fs-6" id="dtlGrandTotal">Rp 0</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal" style="border-radius: 8px;">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#purchasesTable').DataTable({ order: [[0, 'desc']] });

        // AUTO-OPEN DETAIL FAKTUR DARI LINK KLIK RIWAYAT BARANG
        const urlParams = new URLSearchParams(window.location.search);
        const fakturId = urlParams.get('show_faktur');
        
        if (fakturId) {
            // Tembak AJAX persis seperti saat tombol "Detail" diklik
            $.get('/purchases/' + fakturId, function(res) {
                $('#dtlNoFaktur').text(res.invoice_number || res.faktur_number || '-');
                $('#dtlSupplier').text(res.supplier ? res.supplier.name : '-');
                $('#dtlAdmin').text(res.user ? res.user.name : '-');
                
                let paymentBadge = res.payment_method === 'tempo' || res.status === 'tempo'
                    ? '<span class="badge bg-warning text-dark">Tempo (Hutang)</span>' 
                    : '<span class="badge bg-success">Tunai / Lunas</span>';
                $('#dtlMetode').html(paymentBadge);

                let items = res.details || res.purchase_details || [];
                let rows = ''; let total = 0;

                if (items.length === 0) {
                    rows = '<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data rincian barang.</td></tr>';
                } else {
                    items.forEach((item, index) => {
                        let namaBarang = item.product ? item.product.name : (item.product_name || 'Barang');
                        let costPrice = parseInt(item.cost_price || item.purchase_price || 0);
                        let qty = parseInt(item.qty || 0);
                        let subtotal = parseInt(item.subtotal || (costPrice * qty));
                        total += subtotal;

                        rows += `
                            <tr>
                                <td class="text-center">${index + 1}</td>
                                <td class="fw-bold">${namaBarang}</td>
                                <td class="text-center fw-bold">${qty}</td>
                                <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(costPrice)}</td>
                                <td class="text-end fw-bold">Rp ${new Intl.NumberFormat('id-ID').format(subtotal)}</td>
                            </tr>
                        `;
                    });
                }
                $('#dtlItemsBody').html(rows);
                let finalTotal = res.total_amount ? parseInt(res.total_amount) : total;
                $('#dtlGrandTotal').text('Rp ' + new Intl.NumberFormat('id-ID').format(finalTotal));
                
                $('#purchaseDetailModal').modal('show');
            });
            
            // Bersihkan URL bar secara otomatis agar jika di-refresh modal tidak terus terbuka
            window.history.replaceState(null, null, window.location.pathname);
        }
    });

    $(document).on('click', '.btn-detail-faktur', function() {
        let id = $(this).data('id');
        let btn = $(this);
        btn.prop('disabled', true).text('...');

        $.get('/purchases/' + id, function(res) {
            btn.prop('disabled', false).text('👁️ Detail');

            $('#dtlNoFaktur').text(res.invoice_number || res.faktur_number || '-');
            $('#dtlSupplier').text(res.supplier ? res.supplier.name : '-');
            $('#dtlAdmin').text(res.user ? res.user.name : '-');
            
            let paymentBadge = res.payment_method === 'tempo' || res.status === 'tempo'
                ? '<span class="badge bg-warning text-dark">Tempo (Hutang)</span>' 
                : '<span class="badge bg-success">Tunai / Lunas</span>';
            $('#dtlMetode').html(paymentBadge);

            let items = res.details || res.purchase_details || [];
            let rows = '';
            let total = 0;

            if (items.length === 0) {
                rows = '<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data rincian barang.</td></tr>';
            } else {
                items.forEach((item, index) => {
                    let namaBarang = item.product ? item.product.name : (item.product_name || 'Barang');
                    let costPrice = parseInt(item.cost_price || item.purchase_price || 0);
                    let qty = parseInt(item.qty || 0);
                    let subtotal = parseInt(item.subtotal || (costPrice * qty));
                    total += subtotal;

                    rows += `
                        <tr>
                            <td class="text-center">${index + 1}</td>
                            <td class="fw-bold">${namaBarang}</td>
                            <td class="text-center fw-bold">${qty}</td>
                            <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(costPrice)}</td>
                            <td class="text-end fw-bold">Rp ${new Intl.NumberFormat('id-ID').format(subtotal)}</td>
                        </tr>
                    `;
                });
            }

            $('#dtlItemsBody').html(rows);
            let finalTotal = res.total_amount ? parseInt(res.total_amount) : total;
            $('#dtlGrandTotal').text('Rp ' + new Intl.NumberFormat('id-ID').format(finalTotal));

            $('#purchaseDetailModal').modal('show');
        }).fail(function() {
            btn.prop('disabled', false).text('👁️ Detail');
            alert('Gagal memuat rincian faktur pembelian!');
        });
    });
</script>

<!-- ========================================================================= -->
<!-- MODAL AUTO-POPUP: PENYESUAIAN HARGA JUAL JIKA ADA KENAIKAN/PENURUNAN HPP -->
<!-- ========================================================================= -->
@if(session('price_changes'))
<div class="modal fade" id="autoPriceChangeModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold text-danger">⚠️ Perhatian: Terdapat Perubahan Harga Modal (HPP)</h5>
                    <p class="text-muted small m-0">Beberapa barang pada faktur barusan mengalami kenaikan/penurunan harga kulakan. Silakan sesuaikan Harga Jualnya di bawah ini jika diperlukan.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" title="Abaikan & Tutup"></button>
            </div>
            
            <form action="{{ route('products.bulk_update_prices') }}" method="POST" id="bulkPriceForm">
                @csrf
                <div class="modal-body pb-4 pt-3">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light text-center">
                                <tr>
                                    <th width="30%">Nama Barang</th>
                                    <th width="15%">HPP (Modal)</th>
                                    <th width="15%">Status HPP</th>
                                    <th width="20%">Set Harga Eceran Baru</th>
                                    <th width="20%">Set Harga Grosir Baru</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(session('price_changes') as $index => $item)
                                    <tr>
                                        <td class="fw-bold text-dark">
                                            {{ $item['name'] }}
                                            <input type="hidden" name="products[{{ $index }}][id]" value="{{ $item['id'] }}">
                                        </td>
                                        <td class="text-center">
                                            <span class="text-muted text-decoration-line-through" style="font-size: 11px;">Rp {{ number_format($item['old_cost'], 0, ',', '.') }}</span><br>
                                            <span class="fw-bold text-dark">Rp {{ number_format($item['new_cost'], 0, ',', '.') }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($item['type'] == 'Naik')
                                                <span class="badge bg-danger">Naik Rp {{ number_format($item['diff'], 0, ',', '.') }}</span>
                                            @else
                                                <span class="badge bg-success">Turun Rp {{ number_format($item['diff'], 0, ',', '.') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white fw-bold">Rp</span>
                                                <input type="text" name="products[{{ $index }}][retail_price]" class="form-control fw-bold format-rupiah-bulk input-retail" data-cost="{{ $item['new_cost'] }}" data-name="{{ $item['name'] }}" value="{{ number_format($item['retail_price'], 0, ',', '.') }}">
                                            </div>
                                        </td>
                                        <td>
                                            @if($item['has_wholesale'])
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white fw-bold">Rp</span>
                                                    <input type="text" name="products[{{ $index }}][wholesale_price]" class="form-control format-rupiah-bulk input-wholesale" data-cost="{{ $item['new_cost'] }}" data-name="{{ $item['name'] }}" value="{{ number_format($item['wholesale_price'], 0, ',', '.') }}">
                                                </div>
                                            @else
                                                <span class="badge bg-light text-muted border w-100 py-2">Tidak ada harga grosir</span>
                                                <input type="hidden" name="products[{{ $index }}][wholesale_price]" value="0">
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 bg-light rounded-bottom">
                    <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">Abaikan Dulu</button>
                    <button type="submit" class="btn btn-danger fw-bold px-5 shadow-sm">Simpan Harga Jual Baru</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <script>
    // Memunculkan Modal Secara Otomatis & Format Rupiah Realtime
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('autoPriceChangeModal'));
        myModal.show();
        
        // Pasang pendengar format rupiah di dalam modal
        $(document).on('input', '.format-rupiah-bulk', function() {
            let val = $(this).val().replace(/[^0-9]/g, '');
            $(this).val(val ? new Intl.NumberFormat('id-ID').format(val) : '');
        });

        // VALIDASI HARGA JUAL TIDAK BOLEH DI BAWAH HPP
        $('#bulkPriceForm').submit(function(e) {
            let isValid = true;
            let errorMessage = "";

            $(this).find('tbody tr').each(function() {
                let retailInput = $(this).find('.input-retail');
                let wholesaleInput = $(this).find('.input-wholesale');

                if (retailInput.length > 0) {
                    let cost = parseInt(retailInput.data('cost')) || 0;
                    let name = retailInput.data('name');
                    let retail = parseInt(retailInput.val().replace(/[^0-9]/g, '')) || 0;

                    if (retail < cost) {
                        isValid = false;
                        errorMessage += "• Harga Eceran [" + name + "] tidak boleh di bawah modal (Rp " + new Intl.NumberFormat('id-ID').format(cost) + ")\n";
                    }

                    if (wholesaleInput.length > 0 && wholesaleInput.attr('type') !== 'hidden') {
                        let wholesale = parseInt(wholesaleInput.val().replace(/[^0-9]/g, '')) || 0;
                        if (wholesale > 0 && wholesale < cost) {
                            isValid = false;
                            errorMessage += "• Harga Grosir [" + name + "] tidak boleh di bawah modal (Rp " + new Intl.NumberFormat('id-ID').format(cost) + ")\n";
                        }
                    }
                }
            });

            if (!isValid) {
                e.preventDefault(); // Hentikan proses simpan
                alert("GAGAL MENYIMPAN!\n\nSilakan perbaiki harga berikut agar toko tidak rugi:\n\n" + errorMessage);
                return false;
            }
        });
    });
</script>
@endif

@endsection