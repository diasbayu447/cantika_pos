@extends('layouts.app')

@section('title', 'Cetak Label Barcode & Tag Rak')

@section('content')
<div class="card border-0 p-4 shadow-sm" style="border-radius: 15px; background: #fff;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold m-0" style="color: #333;">🏷️ Generator Label Barcode & Tag Rak</h5>
            <small class="text-muted">Pilih produk, jumlah cetak, dan ukuran kertas label sesuai printer Anda.</small>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" style="border-radius: 10px;">
            <ul class="mb-0">
                @foreach($errors->all() as $error) <li>{{$error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('barcodes.print') }}" method="POST" target="_blank">
        @csrf

        <div class="table-responsive mb-3" style="min-height: 220px;">
            <table class="table table-bordered align-middle" id="barcodeItemsTable">
                <thead class="table-light">
                    <tr>
                        <th width="50%">Pilih Barang</th>
                        <th width="20%">Barcode Terdaftar</th>
                        <th width="20%">Jumlah Label (Qty)</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="items[0][product_id]" class="form-select product-select" required>
                                <option value="">-- Pilih Barang --</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" data-barcode="{{ $p->barcode }}">
                                        {{ $p->name }} {{ $p->brand ? "({$p->brand})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control bg-light font-monospace barcode-preview" readonly placeholder="-">
                        </td>
                        <td>
                            <input type="number" name="items[0][qty]" class="form-control text-center fw-bold" min="1" max="100" value="5" required>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" disabled>x</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <button type="button" id="btnAddRow" class="btn btn-outline-secondary btn-sm mb-4" style="border-radius: 8px;">
            + Tambah Baris Barang Lain
        </button>

        <div class="p-3 rounded mb-4" style="background-color: #f8f9fa; border: 1px dashed #d17885;">
            <label class="form-label fw-bold small text-dark mb-1">Pilih Format & Ukuran Kertas Label</label>
            <select name="paper_size" class="form-select font-monospace fw-bold">
                <optgroup label="Printer Thermal Label Roll (1 Baris Kontinu)">
                    <option value="40x30" selected>40 x 30 mm (Standar Tag Rak Aksesoris)</option>
                    <option value="50x30">50 x 30 mm (Tag Rak Lebar)</option>
                    <option value="33x15">33 x 15 mm (Label Mini / Kabel)</option>
                    <option value="33x19">33 x 19 mm (Label Kecil)</option>
                    <option value="55x20">55 x 20 mm (Label Persegi Panjang / Case)</option>
                    <option value="38x60">38 x 60 mm (Tag Rak Vertikal)</option>
                </optgroup>
                <optgroup label="Printer Biasa / Kertas Lembaran">
                    <option value="a4_grid">Kertas HVS A4 (Grid 3 Kolom - Siap Gunting)</option>
                </optgroup>
            </select>
            <small class="text-muted d-block mt-1">Ukuran <strong>40x30 mm</strong> dan <strong>50x30 mm</strong> adalah standar paling umum untuk etalase dan rak fisik.</small>
        </div>

        <div class="text-end">
            <button type="submit" class="btn fw-bold px-4 py-2" style="background-color: #f2a6b0; color: white; border-radius: 8px;">
                🖨️ Pratinjau & Cetak Label
            </button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
    $(document).ready(function() {
        let rowIdx = 0;
        let products = @json($products);

        $(document).on('change', '.product-select', function() {
            let barcode = $(this).find(':selected').data('barcode');$(this).closest('tr').find('.barcode-preview').val(barcode || '-');
        });

        $('#btnAddRow').click(function() {
            rowIdx++;
            let options = '<option value="">-- Pilih Barang --</option>';
            products.forEach(p => {
                options += `<option value="${p.id}" data-barcode="${p.barcode}">${p.name} ${p.brand ? '('+p.brand+')' : ''}</option>`;
            });

            let newRow = `
                <tr>
                    <td>
                        <select name="items[${rowIdx}][product_id]" class="form-select product-select" required>
                            ${options}
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control bg-light font-monospace barcode-preview" readonly placeholder="-">
                    </td>
                    <td>
                        <input type="number" name="items[${rowIdx}][qty]" class="form-control text-center fw-bold" min="1" max="100" value="5" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">x</button>
                    </td>
                </tr>
            `;
            $('#barcodeItemsTable tbody').append(newRow);
            $('.btn-remove-row').prop('disabled', false);
        });

        $(document).on('click', '.btn-remove-row', function() {$(this).closest('tr').remove();
            if ($('#barcodeItemsTable tbody tr').length === 1) {
                $('.btn-remove-row').prop('disabled', true);
            }
        });
    });
</script>
@endsection