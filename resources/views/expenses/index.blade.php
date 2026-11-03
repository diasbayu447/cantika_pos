@extends('layouts.app')

@section('title', 'Biaya Operasional Toko')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 10px;" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Ringkasan Total Pengeluaran -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 p-3 h-100 shadow-sm" style="border-radius: 14px; background: #fff; border-left: 4px solid #dc3545 !important;">
            <small class="text-muted fw-bold">TOTAL BIAYA OPERASIONAL TERCATAT</small>
            <h4 class="fw-bold mt-2 text-danger">Rp {{ number_format($totalExpense, 0, ',', '.') }}</h4>
            <small class="text-muted">Biaya di luar kulakan barang</small>
        </div>
    </div>
</div>

<div class="card border-0 p-4 shadow-sm" style="border-radius: 15px; background: #fff;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold m-0" style="color: #333;">Buku Kas Pengeluaran Toko</h5>
            <small class="text-muted">Catat tagihan listrik, internet, sewa, perlengkapan, dan gaji karyawan.</small>
        </div>
        <button class="btn btn-sm fw-bold px-3 text-white" data-bs-toggle="modal" data-bs-target="#expenseModal" style="background-color: #f2a6b0; border-radius: 8px;">
            + Catat Pengeluaran Baru
        </button>
    </div>

    <div class="table-responsive">
        <table id="expensesTable" class="table table-hover align-middle" style="font-size: 13px; width: 100%;">
            <thead class="table-light">
                <tr>
                    <th width="5%">No</th>
                    <th>Tanggal</th>
                    <th>Kategori Biaya</th>
                    <th>Keterangan</th>
                    <th>Nominal Pengeluaran</th>
                    <th>Dicatat Oleh</th>
                    <th width="10%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $index => $e)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="fw-bold">{{ $e->expense_date->format('d/m/Y') }}</td>
                    <td>
                        @if($e->category === 'Gaji Karyawan')
                            <span class="badge bg-primary">💼 Gaji Karyawan</span>
                        @elseif($e->category === 'Listrik & Air')
                            <span class="badge bg-warning text-dark">⚡ Listrik & Air</span>
                        @elseif($e->category === 'Internet / Wi-Fi')
                            <span class="badge bg-info text-dark">🌐 Internet / Wi-Fi</span>
                        @elseif($e->category === 'Sewa Tempat')
                            <span class="badge bg-secondary">🏢 Sewa Tempat</span>
                        @else
                            <span class="badge bg-light text-dark border">📦 {{ $e->category }}</span>
                        @endif
                    </td>
                    <td><small class="text-muted">{{ $e->description ?? '-' }}</small></td>
                    <td class="fw-bold text-danger">Rp {{ number_format($e->amount, 0, ',', '.') }}</td>
                    <td>{{ $e->user->name ?? '-' }}</td>
                    <td class="text-center">
                        <form action="{{ route('expenses.destroy', $e->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" onclick="return confirm('Hapus catatan pengeluaran ini?');">
                                🗑️
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Tambah Biaya Operasional -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: #d17885;">Catat Biaya Operasional</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('expenses.store') }}" method="POST">
                @csrf
                <div class="modal-body pb-3">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Tanggal Pengeluaran <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Kategori Biaya <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="Gaji Karyawan">💼 Gaji Karyawan (Total/Global)</option>
                            <option value="Listrik & Air">⚡ Tagihan Listrik & Air (PLN/PDAM)</option>
                            <option value="Internet / Wi-Fi">🌐 Internet / Wi-Fi Toko</option>
                            <option value="Sewa Tempat">🏢 Sewa Tempat / Toko</option>
                            <option value="Perlengkapan Toko">📦 Perlengkapan Toko (Plastik, Thermal, Lakban)</option>
                            <option value="Operasional Lainnya">🛠️ Operasional Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nominal Biaya (Rp) <span class="text-danger">*</span></label>
                        <input type="text" 
                            name="amount" 
                            id="expenseAmount" 
                            class="form-control format-rupiah" 
                            placeholder="0" 
                            required 
                            autocomplete="off"
                            oninput="let v = this.value.replace(/[^0-9]/g, ''); this.value = v ? new Intl.NumberFormat('id-ID').format(v) : '';">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Keterangan / Catatan Tambahan</label>
                        <input type="text" name="description" class="form-control" placeholder="Contoh: Gaji bulan Oktober untuk 3 staf / Pembayaran WiFi Indihome">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm fw-bold px-4 text-white" style="background-color: #f2a6b0; border-radius: 8px;">Simpan Biaya</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
   
    $(document).ready(function() {
        $('#expensesTable').DataTable({
            order: [[1, 'desc']],
            language: { search: "Cari Biaya:", lengthMenu: "Tampil _MENU_", info: "_START_ - _END_ dari _TOTAL_" }
        });

        // Pastikan format titik dibersihkan sebelum data dikirim ke server
        $('form').submit(function() {
            let amountInput = $(this).find('#expenseAmount');
            if (amountInput.length) {
                amountInput.val(amountInput.val().replace(/[^0-9]/g, ''));
            }
        });
    });

</script>
@endsection