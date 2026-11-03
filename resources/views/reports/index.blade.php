@extends('layouts.app')

@section('title', 'Laporan Penjualan & Laba')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        body { background-color: #fff !important; }
    }
</style>

<!-- Filter Periode & Tombol Cetak -->
<div class="card border-0 p-4 mb-4 no-print" style="border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
    <form action="{{ route('reports.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label text-muted small fw-bold">Dari Tanggal</label>
            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted small fw-bold">Sampai Tanggal</label>
            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn fw-bold px-4 w-100" style="background-color: #f2a6b0; color: white; border-radius: 8px;">
                🔍 Terapkan Filter
            </button>
        </div>
        <div class="col-md-3 text-end">
            <button type="button" class="btn btn-outline-secondary px-3" onclick="window.print()" style="border-radius: 8px;">
                🖨️ Cetak Rekap (PDF)
            </button>
        </div>
    </form>
</div>

<!-- Kartu Ringkasan Metrik Finansial & Laba Bersih -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 p-3 h-100 shadow-sm" style="border-radius: 12px; background: #fff; border-left: 4px solid #0d6efd !important;">
            <small class="text-muted fw-bold">TOTAL OMZET BERSIH</small>
            <h4 class="fw-bold mt-2 text-primary">Rp {{ number_format($totalNetSales, 0, ',', '.') }}</h4>
            <small class="text-muted">{{ count($transactions) }} Transaksi Berhasil</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 p-3 h-100 shadow-sm" style="border-radius: 12px; background: #fff; border-left: 4px solid #198754 !important;">
            <small class="text-muted fw-bold">LABA KOTOR (GROSS PROFIT)</small>
            <h4 class="fw-bold mt-2 text-success">Rp {{ number_format($grossProfit, 0, ',', '.') }}</h4>
            <small class="text-muted">Omzet dikurangi Modal HPP</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 p-3 h-100 shadow-sm" style="border-radius: 12px; background: #fff; border-left: 4px solid #17a2b8 !important;">
            <small class="text-muted fw-bold">LABA BERSIH RIIL (NET PROFIT)</small>
            <h4 class="fw-bold mt-2 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                Rp {{ number_format($netProfit, 0, ',', '.') }}
            </h4>
            <small class="text-muted">Laba kotor dikurangi seluruh beban operasional</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 p-3 shadow-sm" style="border-radius: 12px; background: #fff; border-left: 4px solid #dc3545 !important;">
            <small class="text-muted fw-bold">TOTAL KAS KELUAR KASIR (PETTY CASH)</small>
            <h5 class="fw-bold text-danger mt-1">Rp {{ number_format($totalPettyCash, 0, ',', '.') }}</h5>
            <small class="text-muted">Pengeluaran harian meja kasir</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 p-3 shadow-sm" style="border-radius: 12px; background: #fff; border-left: 4px solid #6c757d !important;">
            <small class="text-muted fw-bold">BIAYA OPERASIONAL TOKO (LISTRIK, GAJI, DLL)</small>
            <h5 class="fw-bold text-dark mt-1">Rp {{ number_format($totalOperationalExpense, 0, ',', '.') }}</h5>
            <small class="text-muted">Beban operasional umum periode ini</small>
        </div>
    </div>
</div>

<!-- Rincian Aliran Kas & Potongan Diskon -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 p-3" style="border-radius: 12px; background-color: #fff;">
            <span class="text-muted small fw-bold d-block">PENERIMAAN KAS (TUNAI)</span>
            <h5 class="fw-bold text-dark mt-1">Rp {{ number_format($totalCash, 0, ',', '.') }}</h5>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 p-3" style="border-radius: 12px; background-color: #fff;">
            <span class="text-muted small fw-bold d-block">PENERIMAAN NON-TUNAI (QRIS/TRF)</span>
            <h5 class="fw-bold text-info mt-1">Rp {{ number_format($totalNonCash, 0, ',', '.') }}</h5>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 p-3" style="border-radius: 12px; background-color: #fff;">
            <span class="text-muted small fw-bold d-block">TOTAL POTONGAN DISKON/VOUCHER</span>
            <h5 class="fw-bold text-danger mt-1">-Rp {{ number_format($totalDiscount, 0, ',', '.') }}</h5>
        </div>
    </div>
</div>

<!-- Tab Navigasi: Rincian Transaksi vs Rekap Shift -->
<div class="card border-0 p-4" style="border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
    <ul class="nav nav-pills mb-3 no-print" id="reportTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active btn-sm" id="trans-tab-btn" data-bs-toggle="pill" data-bs-target="#trans-tab" type="button">
                🧾 Rincian Transaksi Penjualan
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link btn-sm" id="shift-tab-btn" data-bs-toggle="pill" data-bs-target="#shift-tab" type="button">
                ⚖️ Rekap Shift & Selisih Kasir
            </button>
        </li>
    </ul>

    <div class="tab-content" id="reportTabContent">
        
        <!-- ============================================== -->
        <!-- TAB 1: TABEL TRANSAKSI (Hanya Rincian Transaksi) -->
        <!-- ============================================== -->
        <div class="tab-pane fade show active" id="trans-tab">
            <div class="table-responsive">
                <table id="transactionsTable" class="table table-hover align-middle" style="font-size: 13px; width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu</th>
                            <th>No. Faktur</th>
                            <th>Kasir</th>
                            <th>Pelanggan</th>
                            <th>Metode</th>
                            <th>Diskon</th>
                            <th>Total Belanja</th>
                            <th class="no-print text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $trx)
                        <tr>
                            <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                            <td class="fw-bold font-monospace">{{ $trx->invoice_number }}</td>
                            <td>{{ $trx->user->name ?? '-' }}</td>
                            <td>{{ $trx->customer->name ?? 'Pelanggan Umum' }}</td>
                            <td>
                                <span class="badge {{ $trx->payment_method === 'cash' ? 'bg-success' : 'bg-info text-dark' }}">
                                    {{ strtoupper($trx->payment_method) }}
                                </span>
                            </td>
                            <td>
                                @if($trx->discount_amount > 0)
                                    <span class="text-danger">-Rp {{ number_format($trx->discount_amount, 0, ',', '.') }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="fw-bold text-dark">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                            <td class="no-print text-center">
                                <a href="{{ route('pos.receipt', $trx->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary px-2 py-1" title="Lihat Struk">
                                    🖨️ Struk
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- TAB 2: TABEL SHIFT KASIR & SELISIH LACI -->
        <!-- ============================================== -->
        <div class="tab-pane fade" id="shift-tab">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" style="font-size: 13px; width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th>Kasir Bertugas</th>
                            <th>Buka Shift</th>
                            <th>Tutup Shift</th>
                            <th>Modal Awal</th>
                            <th>Uang Sistem</th>
                            <th>Uang Fisik Dihitung</th>
                            <th class="text-center">Selisih</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Rincian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shifts as $shift)
                        <tr>
                            <td class="fw-bold text-dark">{{ $shift->user->name ?? '-' }}</td>
                            <td>{{ $shift->opened_at ? $shift->opened_at->format('d/m H:i') : '-' }}</td>
                            <td>{{ $shift->closed_at ? $shift->closed_at->format('d/m H:i') : 'Masih Buka' }}</td>
                            <td>Rp {{ number_format($shift->start_cash, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($shift->end_cash_system, 0, ',', '.') }}</td>
                            <td class="fw-bold">
                                {{ $shift->status === 'closed' ? 'Rp ' . number_format($shift->end_cash_actual, 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-center">
                                @if($shift->status === 'closed')
                                    @if($shift->difference === 0)
                                        <span class="badge bg-success">Pas (Rp 0)</span>
                                    @elseif($shift->difference < 0)
                                        <span class="badge bg-danger">Kurang Rp {{ number_format(abs($shift->difference), 0, ',', '.') }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Lebih +Rp {{ number_format($shift->difference, 0, ',', '.') }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $shift->status === 'open' ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ strtoupper($shift->status) }}
                                </span>
                            </td>
                            
                            <!-- TOMBOL DETAIL DI TAB 2 -->
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-info fw-bold" data-bs-toggle="modal" data-bs-target="#detailShift{{ $shift->id }}">👁️ Cek Detail</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-3 text-muted">Tidak ada rekaman shift pada rentang tanggal ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- MODAL DETAIL SHIFT (Dipindah ke luar dari tag <table>) -->
            @foreach($shifts as $shift)
            <div class="modal fade text-start" id="detailShift{{ $shift->id }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius: 15px;">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold text-dark">Rincian Shift Kasir</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body pb-4 pt-3">
                            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                <span class="text-muted">Kasir: <strong class="text-dark">{{ $shift->user->name ?? '-' }}</strong></span>
                                <span class="text-muted">{{ $shift->opened_at ? $shift->opened_at->format('d/m/Y') : '-' }}</span>
                            </div>
                            
                            <table class="table table-sm table-borderless mb-0" style="font-size: 13px;">
                                <tr>
                                    <td class="text-muted">Modal Awal Laci</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($shift->start_cash, 0, ',', '.') }}</td>
                                </tr>
                                @php
                                    // Tarik list petty cash khusus shift ini (otomatis diload via relasi Eloquent)
                                    $shiftPetty = \App\Models\PettyCash::where('cashier_shift_id', $shift->id)->get();
                                    $pettyAmount = $shiftPetty->sum('amount');
                                    
                                    // Asumsi kas masuk kotor
                                    $cashMasuk = $shift->end_cash_system - $shift->start_cash + $pettyAmount; 
                                @endphp
                                <tr>
                                    <td class="text-muted">Estimasi Penerimaan Tunai</td>
                                    <td class="text-end fw-bold text-success">+ Rp {{ number_format(max(0, $cashMasuk), 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">
                                        Pengeluaran Kasir (Petty Cash)<br>
                                        <ul class="mb-0 ps-3 mt-1" style="font-size: 11px;">
                                            @forelse($shiftPetty as $pc)
                                                <li>{{ $pc->description }}: Rp {{ number_format($pc->amount, 0, ',', '.') }}</li>
                                            @empty
                                                <li class="text-muted">Tidak ada kas keluar</li>
                                            @endforelse
                                        </ul>
                                    </td>
                                    <td class="text-end fw-bold text-danger">- Rp {{ number_format($pettyAmount, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td class="fw-bold pt-2">TOTAL UANG SISTEM</td>
                                    <td class="text-end fw-bold text-primary fs-5 pt-2">Rp {{ number_format($shift->end_cash_system, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-muted">Uang Fisik Aktual (Laci)</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($shift->end_cash_actual, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                            
                            <div class="mt-3 text-center p-2 rounded" style="background-color: #f8f9fa; border: 1px dashed #ccc;">
                                <span class="text-muted small d-block mb-1">Status Keseimbangan (Selisih):</span>
                                @if($shift->status === 'closed')
                                    @if($shift->difference === 0)
                                        <span class="badge bg-success fs-6">Klop / Balance 100%</span>
                                    @elseif($shift->difference < 0)
                                        <span class="badge bg-danger fs-6">Minus / Kurang Rp {{ number_format(abs($shift->difference), 0, ',', '.') }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark fs-6">Lebih +Rp {{ number_format($shift->difference, 0, ',', '.') }}</span>
                                    @endif
                                @else
                                    <span class="badge bg-primary">Shift Masih Berjalan (Belum Ditutup)</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            <!-- Akhir Modal -->
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#transactionsTable').DataTable({
            order: [[0, 'desc']],
            language: {
                search: "Cari Faktur/Kasir:",
                lengthMenu: "Tampil _MENU_",
                info: "Menampilkan _START_ - _END_ dari _TOTAL_ transaksi"
            }
        });
    });
</script>
@endsection