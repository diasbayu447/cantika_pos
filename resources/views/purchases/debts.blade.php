@extends('layouts.app')

@section('title', 'Manajemen Hutang Kulakan (Tempo)')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 10px;" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Kartu Ringkasan Hutang Berjalan -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 p-3 h-100 shadow-sm" style="border-radius: 14px; background: #fff; border-left: 4px solid #dc3545 !important;">
            <small class="text-muted fw-bold">TOTAL HUTANG BERJALAN</small>
            <h4 class="fw-bold mt-2 text-danger">Rp {{ number_format($totalUnpaidDebt, 0, ',', '.') }}</h4>
            <small class="text-muted">{{ count($unpaidPurchases) }} Faktur Belum Lunas</small>
        </div>
    </div>
</div>

<div class="card border-0 p-4 shadow-sm" style="border-radius: 15px; background: #fff;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold m-0" style="color: #333;">Daftar Tagihan Tempo Supplier</h5>
            <small class="text-muted">Pantau sisa jatuh tempo dan lunasi faktur kulakan.</small>
        </div>
        <a href="{{ route('purchases.create') }}" class="btn btn-sm fw-bold px-3 text-white" style="background-color: #f2a6b0; border-radius: 8px;">
            + Input Faktur Baru
        </a>
    </div>

    <ul class="nav nav-pills mb-3" id="debtTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active btn-sm" id="unpaid-tab-btn" data-bs-toggle="pill" data-bs-target="#unpaid-tab" type="button">
                ⏳ Belum Lunas ({{ count($unpaidPurchases) }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link btn-sm" id="paid-tab-btn" data-bs-toggle="pill" data-bs-target="#paid-tab" type="button">
                ✅ Riwayat Lunas
            </button>
        </li>
    </ul>

    <div class="tab-content" id="debtTabContent">
        <!-- Tab 1: Faktur Belum Lunas -->
        <div class="tab-pane fade show active" id="unpaid-tab">
            <div class="table-responsive">
                <table class="table table-hover align-middle debtTable" style="font-size: 13px; width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th>No. Faktur</th>
                            <th>Supplier</th>
                            <th>Tgl Kulakan</th>
                            <th>Jatuh Tempo</th>
                            <th>Sisa Waktu</th>
                            <th>Total Tagihan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unpaidPurchases as $p)
                        @php
                            $today = \Carbon\Carbon::today();
                            $dueDate = \Carbon\Carbon::parse($p->due_date);
                            $daysLeft = $today->diffInDays($dueDate, false);
                        @endphp
                        <tr>
                            <td class="fw-bold font-monospace text-dark">{{ $p->invoice_number }}</td>
                            <td>
                                <strong>{{ $p->supplier->name ?? '-' }}</strong><br>
                                <small class="text-muted">{{ $p->supplier->phone ?? '' }}</small>
                            </td>
                            <td>{{ $p->purchase_date ? $p->purchase_date->format('d/m/Y') : '-' }}</td>
                            <td class="fw-bold text-danger">{{ $dueDate->format('d/m/Y') }}</td>
                            <td>
                                @if($daysLeft < 0)
                                    <span class="badge bg-danger">Lewat {{ abs($daysLeft) }} Hari</span>
                                @elseif($daysLeft == 0)
                                    <span class="badge bg-warning text-dark">Jatuh Tempo Hari Ini</span>
                                @elseif($daysLeft <= 3)
                                    <span class="badge bg-warning text-dark">Sisa {{ $daysLeft }} Hari</span>
                                @else
                                    <span class="badge bg-secondary">Sisa {{ $daysLeft }} Hari</span>
                                @endif
                            </td>
                            <td class="fw-bold text-dark">Rp {{ number_format($p->total_amount, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <form action="{{ route('purchases.pay_debt', $p->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success fw-bold px-3 py-1" onclick="return confirm('Tandai faktur ini sudah lunas dibayar?');" style="border-radius: 6px;">
                                        💵 Tandai Lunas
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 2: Faktur Sudah Lunas -->
        <div class="tab-pane fade" id="paid-tab">
            <div class="table-responsive">
                <table class="table table-hover align-middle debtTable" style="font-size: 13px; width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th>No. Faktur</th>
                            <th>Supplier</th>
                            <th>Tgl Kulakan</th>
                            <th>Tgl Dilunasi</th>
                            <th>Total Tagihan</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paidPurchases as $p)
                        <tr>
                            <td class="fw-bold font-monospace text-dark">{{ $p->invoice_number }}</td>
                            <td>{{ $p->supplier->name ?? '-' }}</td>
                            <td>{{ $p->purchase_date ? $p->purchase_date->format('d/m/Y') : '-' }}</td>
                            <td>{{ $p->paid_at ? $p->paid_at->format('d/m/Y H:i') : '-' }}</td>
                            <td class="fw-bold">Rp {{ number_format($p->total_amount, 0, ',', '.') }}</td>
                            <td class="text-center"><span class="badge bg-success">LUNAS</span></td>
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
        $('.debtTable').DataTable({
            language: { search: "Cari Faktur:", lengthMenu: "Tampil _MENU_", info: "_START_ - _END_ dari _TOTAL_" }
        });
    });
</script>
@endsection