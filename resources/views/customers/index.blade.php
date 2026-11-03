@extends('layouts.app')

@section('title', 'Data & Riwayat Pelanggan')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show fw-bold" style="border-radius: 10px;">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger fw-bold" style="border-radius: 10px;">
        {{ $errors->first() }}
    </div>
@endif

<div class="card border-0 p-4 shadow-sm" style="border-radius: 15px; background: #fff;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold m-0" style="color: #333;">👥 Buku Data Pelanggan & Member</h5>
            <small class="text-muted">Pantau loyalitas belanja, total transaksi, dan buat program promo khusus.</small>
        </div>
        <button class="btn btn-sm fw-bold px-3 text-white" data-bs-toggle="modal" data-bs-target="#customerModal" id="btnAddCustomer" style="background-color: #f2a6b0; border-radius: 8px;">
            + Tambah Pelanggan
        </button>
    </div>

    <div class="table-responsive">
        <table id="customerTable" class="table table-hover align-middle" style="font-size: 13.5px; width: 100%;">
            <thead class="table-light">
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Pelanggan</th>
                    <th>No. WhatsApp</th>
                    <th>Tipe Akun</th>
                    <th class="text-center">Total Kunjungan</th>
                    <th>Total Belanja</th>
                    <th width="18%" class="text-center">Aksi & Analisis</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $idx => $c)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td class="fw-bold text-dark">{{ $c->name }}</td>
                    <td class="font-monospace text-muted">{{ $c->phone ?? '-' }}</td>
                    <td>
                        @if($c->status === 'registered')
                            <span class="badge bg-warning text-dark fw-bold">⭐ Member</span>
                        @else
                            <span class="badge bg-secondary">Umum</span>
                        @endif
                    </td>
                    <td class="text-center fw-bold">{{ $c->transactions_count }} Transaksi</td>
                    <td class="fw-bold text-success">Rp {{ number_format($c->total_spent ?? 0, 0, ',', '.') }}</td>
                    <td class="text-center text-nowrap">
                        <!-- Tombol Buka Riwayat & Histogram -->
                        <a href="{{ route('customers.show', $c->id) }}" class="btn btn-sm btn-outline-info fw-bold px-2 py-1" title="Lihat Riwayat & Histogram Belanja">
                            📊 Riwayat
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary fw-bold px-2 py-1 btn-edit" data-customer="{{ json_encode($c) }}">
                            ✏️ Edit
                        </button>
                        <form action="{{ route('customers.destroy', $c->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pelanggan ini?');">
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

<!-- Modal Tambah/Edit Pelanggan -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="customerModalTitle" style="color: #d17885;">Tambah Pelanggan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="customerForm" method="POST" action="{{ route('customers.store') }}">
                @csrf
                <div id="methodSpoof"></div>
                <div class="modal-body pb-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nama Lengkap *</label>
                        <input type="text" name="name" id="c_name" class="form-control" required placeholder="Contoh: Budi Santoso">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">No. WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">+62</span>
                            <input type="text" name="phone" id="c_phone" class="form-control font-monospace" placeholder="8123456789" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Status Pelanggan</label>
                        <select name="status" id="c_status" class="form-select">
                            <option value="registered">Member (Berhak Harga Grosir)</option>
                            <option value="general">Umum (Ecer Biasa)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm fw-bold px-4 text-white" style="background-color: #f2a6b0; border-radius: 8px;">Simpan Pelanggan</button>
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
        $('#customerTable').DataTable({
            language: { search: "Cari Pelanggan:", lengthMenu: "Tampil _MENU_", info: "_START_ - _END_ dari _TOTAL_" }
        });

        $('#btnAddCustomer').click(function() {
            $('#customerModalTitle').text('Tambah Pelanggan Baru');
            $('#customerForm').attr('action', "{{ route('customers.store') }}");
            $('#methodSpoof').html('');
            $('#customerForm')[0].reset();
        });

        $(document).on('click', '.btn-edit', function() {
            let c = $(this).data('customer');
            $('#customerModalTitle').text('Edit Pelanggan: ' + c.name);
            $('#customerForm').attr('action', '/customers/' + c.id);
            $('#methodSpoof').html('@method("PUT")');

            $('#c_name').val(c.name);
            let phone = c.phone ? c.phone.replace(/^\+?62|^0/, '') : '';
            $('#c_phone').val(phone);
            $('#c_status').val(c.status);

            $('#customerModal').modal('show');
        });
    });
</script>
@endsection