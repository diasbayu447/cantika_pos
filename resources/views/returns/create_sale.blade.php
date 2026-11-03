@extends('layouts.app')

@section('title', 'Proses Retur Penjualan')

@section('content')
<div class="card border-0 p-4 shadow-sm mb-4" style="border-radius: 15px; background: #fff;">
    <h5 class="fw-bold mb-3" style="color: #333;">📥 Form Retur Penjualan (Dari Pelanggan)</h5>
    
    <!-- Step 1: Cari Nomor Faktur -->
    <div class="row g-2 align-items-center mb-3">
        <div class="col-md-5">
            <label class="form-label text-muted small fw-bold">Nomor Struk / Faktur Transaksi</label>
            <div class="input-group">
                <input type="text" id="inputInvoice" class="form-control font-monospace" placeholder="Contoh: TRX-20261018-..." autocomplete="off">
                <button type="button" id="btnSearchTrx" class="btn btn-dark fw-bold px-3">Cari Struk</button>
            </div>
        </div>
    </div>
</div>

<!-- Step 2: Form Detail Retur (Muncul setelah struk ditemukan) -->
<form action="{{ route('returns.sale.store') }}" method="POST" id="formSaleReturn" style="display: none;">
    @csrf
    <input type="hidden" name="transaction_id" id="resTransactionId">

    <div class="card border-0 p-4 shadow-sm mb-4" style="border-radius: 15px; background: #fff;">
        <div class="row g-3 mb-4 p-3 bg-light rounded">
            <div class="col-md-3">
                <small class="text-muted d-block">Nomor Faktur</small>
                <span class="fw-bold font-monospace" id="resInvoiceText">-</span>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Tanggal Transaksi</small>
                <span class="fw-bold" id="resDateText">-</span>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Nama Pelanggan</small>
                <span class="fw-bold" id="resCustomerText">-</span>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Total Belanja Asli</small>
                <span class="fw-bold text-primary" id="resTotalText">-</span>
            </div>
        </div>

        <h6 class="fw-bold mb-3">Pilih Barang yang Diretur</h6>
        <div class="table-responsive mb-3">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="5%">Pilih</th>
                        <th>Nama Barang</th>
                        <th width="15%">Harga Beli</th>
                        <th width="12%">Qty Beli</th>
                        <th width="15%">Qty Retur</th>
                    </tr>
                </thead>
                <tbody id="trxItemsBody"></tbody>
            </table>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label text-muted small fw-bold">Bentuk Penyelesaian <span class="text-danger">*</span></label>
                <select name="action_type" class="form-select" required>
                    <option value="replace">Tukar Barang Sejenis (Stok Toko Berkurang)</option>
                    <option value="refund">Kembalikan Uang Kas (Refund Tunai)</option>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label text-muted small fw-bold">Alasan Retur / Kerusakan <span class="text-danger">*</span></label>
                <input type="text" name="reason" class="form-control" placeholder="Contoh: Tempered glass retak saat dibuka / Salah tipe casing" required>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" class="btn fw-bold px-4 py-2 text-white" style="background-color: #f2a6b0; border-radius: 8px;">
                Simpan & Proses Retur
            </button>
        </div>
    </div>
</form>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
    $(document).ready(function() {$('#btnSearchTrx').click(function() {
            let inv = $('#inputInvoice').val().trim();
            if (!inv) return alert('Ketik nomor faktur struk!');

            let btn = $(this);
            btn.prop('disabled', true).text('Mencari...');

            $.ajax({
                url: "{{ route('returns.sale.search_trx') }}",
                type: "POST",
                data: { _token: "{{ csrf_token() }}", invoice_number: inv },
                success: function(trx) {
                    btn.prop('disabled', false).text('Cari Struk');
                    $('#resTransactionId').val(trx.id);
                    $('#resInvoiceText').text(trx.invoice_number);
                    $('#resDateText').text(new Date(trx.created_at).toLocaleDateString('id-ID'));
                    $('#resCustomerText').text(trx.customer ? trx.customer.name : 'Pelanggan Umum');
                    $('#resTotalText').text('Rp ' + trx.total_amount.toLocaleString('id-ID'));

                    let rows = '';
                    trx.details.forEach(function(item, idx) {
                        rows += `
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input item-check" data-idx="${idx}">
                                </td>
                                <td>
                                    <strong>${item.product ? item.product.name : 'Produk'}</strong>
                                    <input type="hidden" name="items[${idx}][product_id]" value="${item.product_id}" disabled class="input-data">
                                    <input type="hidden" name="items[${idx}][price_per_item]" value="${item.selling_price}" disabled class="input-data">
                                </td>
                                <td>Rp ${item.selling_price.toLocaleString('id-ID')}</td>
                                <td class="text-center fw-bold">${item.qty} pcs</td>
                                <td>
                                    <input type="number" name="items[${idx}][qty]" class="form-control form-control-sm text-center fw-bold input-data" value="1" min="1" max="${item.qty}" disabled>
                                </td>
                            </tr>
                        `;
                    });

                    $('#trxItemsBody').html(rows);
                    $('#formSaleReturn').slideDown();
                },
                error: function(err) {
                    btn.prop('disabled', false).text('Cari Struk');
                    alert(err.responseJSON?.error || 'Struk tidak ditemukan!');
                }
            });
        });

        // Aktifkan baris hanya jika checkbox dicentang
        $(document).on('change', '.item-check', function() {
            let row = $(this).closest('tr');
            let isChecked = $(this).is(':checked');
            row.find('.input-data').prop('disabled', !isChecked);
        });

        $('#formSaleReturn').submit(function(e) {
            if ($('.item-check:checked').length === 0) {
                e.preventDefault();
                alert('Pilih minimal 1 barang yang ingin diretur!');
            }
        });
    });
</script>
@endsection