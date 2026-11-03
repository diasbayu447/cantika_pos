@extends('layouts.app')

@section('title', 'Data Master Supplier')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 10px;" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger" style="border-radius: 10px;">
        <ul class="mb-0">
            @foreach($errors->all() as $error) <li>{{$error }}</li> @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 p-4" style="border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="m-0 fw-bold" style="color: #333;">Daftar Supplier / Distributor</h5>
        <button class="btn btn-sm fw-bold px-3" data-bs-toggle="modal" data-bs-target="#supplierModal" id="btnAddSupplier" style="background-color: #f2a6b0; color: white; border-radius: 8px;">
            + Tambah Supplier Baru
        </button>
    </div>

    <div class="table-responsive">
        <table id="suppliersTable" class="table table-hover align-middle" style="font-size: 13px; width: 100%;">
            <thead class="table-light">
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Supplier / Toko</th>
                    <th>Kontak PIC (Sales)</th>
                    <th>No. WhatsApp</th>
                    <th>Alamat</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Total Kulakan</th>
                    <th width="14%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($suppliers as $index => $supplier)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="fw-bold text-dark">{{ $supplier->name }}</td>
                    <td>{{ $supplier->pic_name ?? '-' }}</td>
                    <td>
                        @if($supplier->phone)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $supplier->phone) }}" target="_blank" class="text-decoration-none text-success fw-bold font-monospace">
                                💬 {{ $supplier->phone }}
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td><small class="text-muted">{{ $supplier->address ?? '-' }}</small></td>
                    <td class="text-center">
                        @if($supplier->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">{{ $supplier->purchases_count }} Faktur</span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm btn-outline-secondary btn-edit px-2 py-1" 
                                    data-supplier="{{ json_encode($supplier) }}"
                                    title="Edit Data">
                                ✏️
                            </button>

                            <!-- Tombol Sakelar Aktif/Nonaktif Sales -->
                            <form action="{{ route('suppliers.toggle', $supplier->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $supplier->is_active ? 'btn-outline-warning text-dark' : 'btn-outline-success' }} px-2 py-1" 
                                        title="{{ $supplier->is_active ? 'Nonaktifkan Sales/Supplier' : 'Aktifkan Kembali' }}">
                                    {{ $supplier->is_active ? '⏸️' : '▶️' }}
                                </button>
                            </form>

                            <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" 
                                        onclick="return confirm('Yakin ingin menghapus supplier ini?');"
                                        {{ $supplier->purchases_count > 0 ? 'disabled title="Tidak bisa dihapus karena sudah ada riwayat faktur"' : '' }}>
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
<!-- MODAL FORM TAMBAH / EDIT SUPPLIER             -->
<!-- ============================================== -->
<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="supplierModalTitle" style="color: #d17885;">Tambah Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="supplierForm" method="POST" action="{{ route('suppliers.store') }}">
                @csrf
                <div id="methodSpoof"></div>
                <div class="modal-body pb-3">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Nama Supplier / PT / Toko <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="s_name" class="form-control" required placeholder="Contoh: PT. Sumber Aksesoris Jaya" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Nama PIC / Sales</label>
                        <input type="text" name="pic_name" id="s_pic" class="form-control" placeholder="Contoh: Budi (Sales Cirebon)" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Nomor WhatsApp (Angka saja, maks 13 digit)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-muted">+62</span>
                            <input type="text" 
                                   name="phone" 
                                   id="s_phone" 
                                   class="form-control font-monospace" 
                                   maxlength="12" 
                                   placeholder="81234567890" 
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                                   autocomplete="off">
                        </div>
                        <small class="text-muted" style="font-size: 11px;">Ketik langsung mulai dari angka 8 (tanpa 0 atau +62).</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Alamat Lengkap</label>
                        <textarea name="address" id="s_address" class="form-control" rows="3" placeholder="Alamat gudang / kantor supplier"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn fw-bold px-4" style="background-color: #f2a6b0; color: white; border-radius: 8px;">Simpan Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {$('#suppliersTable').DataTable({
            language: {
                search: "Cari Supplier:",
                lengthMenu: "Tampil _MENU_",
                info: "Menampilkan _START_ - _END_ dari _TOTAL_ supplier"
            }
        });

        // Buka Modal Tambah
        $('#btnAddSupplier').click(function() {
            $('#supplierModalTitle').text('Tambah Supplier Baru');
            $('#supplierForm').attr('action', "{{ route('suppliers.store') }}");
            $('#methodSpoof').html('');
            $('#supplierForm')[0].reset();
        });

        // Buka Modal Edit
        $(document).on('click', '.btn-edit', function() {
            let s = $(this).data('supplier');$('#supplierModalTitle').text('Edit Supplier: ' + s.name);
            $('#supplierForm').attr('action', `/suppliers/${s.id}`);
            $('#methodSpoof').html('@method("PUT")');

            $('#s_name').val(s.name);
            $('#s_pic').val(s.pic_name);
            
            // Bersihkan awalan +62 agar pas masuk ke form input group
            let cleanPhone = s.phone ? s.phone.replace('+62', '') : '';
            $('#s_phone').val(cleanPhone);
            
            $('#s_address').val(s.address);

            $('#supplierModal').modal('show');
        });
    });
</script>
@endsection