@extends('layouts.app')

@section('title', 'Manajemen Retur Barang')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 10px;" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 p-4" style="border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="m-0 fw-bold" style="color: #333;">Riwayat Retur Barang</h5>
            <small class="text-muted">Pencatatan barang cacat/tukar dari pelanggan dan pengembalian ke supplier.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('returns.purchase.create') }}" class="btn btn-sm btn-outline-danger" style="border-radius: 8px;">
                📤 Retur ke Supplier
            </a>
            <a href="{{ route('returns.sale.create') }}" class="btn btn-sm fw-bold text-white px-3" style="background-color: #f2a6b0; border-radius: 8px;">
                📥 Retur dari Pelanggan
            </a>
        </div>
    </div>

    <!-- Tab Pilihan: Retur Jual vs Retur Beli -->
    <ul class="nav nav-pills mb-3" id="returnTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active btn-sm" id="sale-tab-btn" data-bs-toggle="pill" data-bs-target="#sale-tab" type="button">
                🛍️ Retur Penjualan (Pelanggan)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link btn-sm" id="purchase-tab-btn" data-bs-toggle="pill" data-bs-target="#purchase-tab" type="button">
                🏭 Retur Pembelian (Supplier)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="returnTabContent">
        <!-- Tab 1: Retur Penjualan -->
        <div class="tab-pane fade show active" id="sale-tab">
            <div class="table-responsive">
                <table class="table table-hover align-middle returnTable" style="font-size: 13px; width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th>No. Retur</th>
                            <th>No. Faktur Penjualan</th>
                            <th>Waktu</th>
                            <th>Penyelesaian</th>
                            <th>Total Refund</th>
                            <th>Alasan</th>
                            <th>Staf</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($saleReturns as $sr)
                        <tr>
                            <td class="fw-bold font-monospace text-danger">{{ $sr->return_number }}</td>
                            <td class="font-monospace">{{ $sr->transaction->invoice_number ?? '-' }}</td>
                            <td>{{ $sr->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($sr->action_type === 'refund')
                                    <span class="badge bg-danger">Uang Kembali (Refund)</span>
                                @else
                                    <span class="badge bg-success">Tukar Barang</span>
                                @endif
                            </td>
                            <td class="fw-bold">
                                {{ $sr->total_refund_amount > 0 ? 'Rp ' . number_format($sr->total_refund_amount, 0, ',', '.') : '-' }}
                            </td>
                            <td><small class="text-muted">{{ $sr->reason }}</small></td>
                            <td>{{ $sr->user->name ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 2: Retur Pembelian -->
        <div class="tab-pane fade" id="purchase-tab">
            <div class="table-responsive">
                <table class="table table-hover align-middle returnTable" style="font-size: 13px; width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th>No. Retur</th>
                            <th>Supplier</th>
                            <th>Waktu</th>
                            <th>Nilai Barang Retur</th>
                            <th>Alasan</th>
                            <th>Staf</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseReturns as $pr)
                        <tr>
                            <td class="fw-bold font-monospace text-primary">{{ $pr->return_number }}</td>
                            <td class="fw-bold text-dark">{{ $pr->supplier->name ?? '-' }}</td>
                            <td>{{ $pr->created_at->format('d/m/Y H:i') }}</td>
                            <td class="fw-bold text-danger">Rp {{ number_format($pr->total_amount, 0, ',', '.') }}</td>
                            <td><small class="text-muted">{{ $pr->reason }}</small></td>
                            <td>{{ $pr->user->name ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('.returnTable').DataTable({
            order: [[2, 'desc']],
            language: { search: "Cari Retur:", lengthMenu: "Tampil _MENU_", info: "_START_ - _END_ dari _TOTAL_" }
        });
    });
</script>
@endsection