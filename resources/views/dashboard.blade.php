@extends('layouts.app')

@section('title', 'Dashboard Ringkasan Toko')

@section('content')

<!-- BARIS 1: JUDUL SEKSI & TOMBOL BACKUP DATABASE -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold m-0" style="color: #333;">Ringkasan Operasional Toko</h5>
        <small class="text-muted">Pantauan data transaksi, stok kritis, dan tren omzet terkini.</small>
    </div>

    @if(auth()->user()->role === 'owner')
        <a href="{{ route('backup.download') }}" class="btn btn-sm btn-outline-danger fw-bold px-3 py-2" style="border-radius: 8px;">
            💾 Backup Database (.sql)
        </a>
    @endif
</div>

<!-- BARIS 2: SPANDUK PERINGATAN JATUH TEMPO (MEMBENTANG PENUH 100%) -->
@if(isset($overduePurchases) && $overduePurchases->count() > 0)
<div class="alert alert-danger border-0 p-3 mb-4 shadow-sm d-flex justify-content-between align-items-center" style="border-radius: 12px; background-color: #fdf2f2; border-left: 5px solid #dc3545 !important;">
    <div class="d-flex align-items-center gap-3">
        <span style="font-size: 1.8rem;">🚨</span>
        <div>
            <strong class="text-danger d-block">Peringatan: Ada {{ $overduePurchases->count() }} Tagihan Kulakan yang Jatuh Tempo / Menunggak!</strong>
            <small class="text-muted">
                Faktur tertua: <strong>{{ $overduePurchases->first()->invoice_number }}</strong> ke 
                <strong>{{ $overduePurchases->first()->supplier->name ?? 'Supplier' }}</strong> 
                (Rp {{ number_format($overduePurchases->first()->total_amount, 0, ',', '.') }}).
            </small>
        </div>
    </div>
    <a href="{{ route('purchases.debts') }}" class="btn btn-sm btn-danger fw-bold px-3 py-2 text-nowrap" style="border-radius: 8px;">
        Lihat & Lunasi Tagihan &rarr;
    </a>
</div>
@endif

@if(session('error'))
    <div class="alert alert-danger" style="border-radius: 10px;">{{ session('error') }}</div>
@endif

<!-- BARIS 3: KARTU METRIK HARI INI (4 KARTU BERDAMPINGAN) -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 p-3 h-100 shadow-sm" style="border-radius: 14px; background: #fff; border-left: 4px solid #d17885 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted fw-bold">OMZET HARI INI</small>
                <span class="badge bg-light text-dark">Hari Ini</span>
            </div>
            <h4 class="fw-bold mt-2" style="color: #d17885;">Rp {{ number_format($todaySales, 0, ',', '.') }}</h4>
            <small class="text-muted">Penerimaan kas & QRIS</small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 p-3 h-100 shadow-sm" style="border-radius: 14px; background: #fff; border-left: 4px solid #0d6efd !important;">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted fw-bold">TOTAL TRANSAKSI</small>
                <span class="badge bg-light text-primary">Struk</span>
            </div>
            <h4 class="fw-bold mt-2 text-primary">{{ $todayTrxCount }} Faktur</h4>
            <small class="text-muted">Pelanggan terlayani</small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 p-3 h-100 shadow-sm" style="border-radius: 14px; background: #fff; border-left: 4px solid #198754 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted fw-bold">LABA KOTOR HARI INI</small>
                <span class="badge bg-light text-success">Margin</span>
            </div>
            <h4 class="fw-bold mt-2 text-success">Rp {{ number_format($todayGrossProfit, 0, ',', '.') }}</h4>
            <small class="text-muted">Omzet dikurangi HPP modal</small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 p-3 h-100 shadow-sm" style="border-radius: 14px; background: #fff; border-left: 4px solid #dc3545 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted fw-bold">STOK MENIPIS / HABIS</small>
                <span class="badge {{ $lowStockCount > 0 ? 'bg-danger' : 'bg-success' }}">
                    {{ $lowStockCount > 0 ? 'Perlu Kulakan' : 'Aman' }}
                </span>
            </div>
            <h4 class="fw-bold mt-2 text-danger">{{ $lowStockCount }} Produk</h4>
            <small class="text-muted">&le; batas minimum rak</small>
        </div>
    </div>
</div>

<!-- BARIS 4: GRAFIK TREN PENJUALAN 7 HARI & WIDGET BARANG KRITIS -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card border-0 p-4 shadow-sm h-100" style="border-radius: 15px; background: #fff;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0" style="color: #333;">📈 Tren Penjualan 7 Hari Terakhir</h6>
                <small class="text-muted">Total omzet harian</small>
            </div>
            <div style="height: 260px;">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 p-4 shadow-sm h-100" style="border-radius: 15px; background: #fff;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0 text-danger">⚠️ Stok Menipis</h6>
                <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-outline-danger" style="font-size: 11px; border-radius: 6px;">+ Kulakan</a>
            </div>

            @if($lowStockProducts->isEmpty())
                <div class="text-center py-4 text-muted">
                    <p class="fs-4 m-0">🎉</p>
                    <small>Semua stok fisik di rak aman.</small>
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach($lowStockProducts as $p)
                    <div class="list-group-item px-0 py-2 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold d-block text-dark" style="font-size: 13px;">{{ $p->name }}</span>
                            <small class="text-muted">Lokasi: {{ $p->rack_location ?? '-' }}</small>
                        </div>
                        <span class="badge bg-danger fs-6">{{ $p->stock }} pcs</span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- BARIS 5: TOP 5 PRODUK TERLARIS & TRANSAKSI KASIR TERBARU -->
<div class="row g-4">
    <!-- Top 5 Produk Terlaris -->
    <div class="col-md-6">
        <div class="card border-0 p-4 shadow-sm h-100" style="border-radius: 15px; background: #fff;">
            <h6 class="fw-bold mb-3" style="color: #333;">🏆 5 Produk Paling Laris</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Barang</th>
                            <th class="text-center">Terjual</th>
                            <th class="text-end">Total Omzet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $top)
                        <tr>
                            <td class="fw-bold text-dark">{{ $top->product->name ?? 'Barang Terhapus' }}</td>
                            <td class="text-center">
                                <span class="badge bg-success">{{ $top->total_qty }} pcs</span>
                            </td>
                            <td class="text-end fw-bold text-primary">Rp {{ number_format($top->total_sales, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-3 text-muted">Belum ada data penjualan produk.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 5 Transaksi Kasir Terbaru -->
    <div class="col-md-6">
        <div class="card border-0 p-4 shadow-sm h-100" style="border-radius: 15px; background: #fff;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0" style="color: #333;">🧾 Transaksi Kasir Terbaru</h6>
                <a href="{{ route('reports.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted" style="font-size: 12px;">Lihat Semua &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th>Faktur</th>
                            <th>Kasir</th>
                            <th>Metode</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestTransactions as $trx)
                        <tr>
                            <td class="fw-bold font-monospace text-dark">{{ $trx->invoice_number }}</td>
                            <td>{{ $trx->user->display_name ?? $trx->user->name }}</td>
                            <td>
                                <span class="badge {{ $trx->payment_method === 'cash' ? 'bg-success' : 'bg-info text-dark' }}">
                                    {{ strtoupper($trx->payment_method) }}
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-3 text-muted">Belum ada transaksi hari ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT CHART.JS TREN PENJUALAN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const formatRupiah = (angka) => {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
        };

        const ctx = document.getElementById('salesTrendChart').getContext('2d');
        const labels = @json($chartLabels);
        const data = @json($chartData);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Omzet Harian',
                    data: data,
                    borderColor: '#d17885',
                    backgroundColor: 'rgba(209, 120, 133, 0.15)',
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#d17885',
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Omzet: ' + formatRupiah(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) {
                                return 'Rp ' + val.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection