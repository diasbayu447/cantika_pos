@extends('layouts.app')

@section('title', 'Proses Retur ke Supplier')

@section('content')
<div class="card border-0 p-4 shadow-sm" style="border-radius: 15px; background: #fff;">
    <h5 class="fw-bold mb-4" style="color: #333;">📤 Form Retur Pembelian (Toko ke Supplier)</h5>

    <form action="{{ route('returns.purchase.store') }}" method="POST">
        @csrf
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">Pilih Supplier / Distributor <span class="text-danger">*</span></label>
                <select name="supplier_id" class="form-select" required>
                    <option value="">-- Pilih Supplier Tujuan --</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->name }} ({{$sup->phone ?? '-' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">Alasan Pengembalian Barang <span class="text-danger">*</span></label>
                <input type="text" name="reason" class="form-control" placeholder="Contoh: Cacat produksi / Segel rusak saat unboxing" required>
            </div>
        </div>

        <h6 class="fw-bold mb-3">Daftar Barang yang Dikembalikan</h6>
        <div class="table-responsive mb-3">
            <table class="table table-bordered align-middle" id="retPurchaseTable">
                <thead class="table-light">
                    <tr>
                        <th width="45%">Barang</th>
                        <th width="20%">Qty Retur</th>
                        <th width="25%">Harga Modal Asli (HPP)</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="items[0][product_id]" class="form-select prod-select" required>
                                <option value="">-- Pilih Barang --</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" data-cost="{{ $p->cost_price }}">{{ $p->name }} (Sisa Stok: {{$p->stock }})</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" name="items[0][qty]" class="form-control text-center fw-bold" min="1" value="1" required></td>
                        <td><input type="number" name="items[0][cost_price]" class="form-control cost-input text-end" min="0" required></td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove" disabled>x</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <button type="button" id="btnAddRow" class="btn btn-outline-secondary btn-sm mb-4" style="border-radius: 8px;">+ Tambah Barang Lain</button>

        <hr class="text-muted">
        <div class="text-end">
            <button type="submit" class="btn fw-bold px-4 py-2 text-white" style="background-color: #f2a6b0; border-radius: 8px;">
                Keluarkan Stok & Catat Retur
            </button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
    $(document).ready(function() {
        let rowIdx = 0;

        $(document).on('change', '.prod-select', function() {
            let cost = $(this).find(':selected').data('cost');$(this).closest('tr').find('.cost-input').val(cost || 0);
        });

        $('#btnAddRow').click(function() {
            rowIdx++;
            let firstRowOptions = $('#retPurchaseTable tbody tr:first select').html();
            let newRow = `
                <tr>
                    <td><select name="items[${rowIdx}][product_id]" class="form-select prod-select" required>${firstRowOptions}</select></td>
                    <td><input type="number" name="items[${rowIdx}][qty]" class="form-control text-center fw-bold" min="1" value="1" required></td>
                    <td><input type="number" name="items[${rowIdx}][cost_price]" class="form-control cost-input text-end" min="0" required></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove">x</button></td>
                </tr>
            `;
            $('#retPurchaseTable tbody').append(newRow);
            $('.btn-remove').prop('disabled', false);
        });

        $(document).on('click', '.btn-remove', function() {$(this).closest('tr').remove();
            if ($('#retPurchaseTable tbody tr').length === 1) {
                $('.btn-remove').prop('disabled', true);
            }
        });
    });
</script>
@endsection