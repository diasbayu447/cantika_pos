@extends('layouts.app')

@section('title', 'Analitik Bisnis Toko')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<!-- BAGIAN 1: JAM SIBUK OPERASIONAL TOKO (PEAK HOURS) -->
<div class="card border-0 p-4 shadow-sm mb-4" style="border-radius: 15px; background: #fff;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold m-0" style="color: #333;">⏰ Analisis Jam Sibuk Transaksi (Heatmap)</h5>
            <small class="text-muted">Grafik frekuensi transaksi kasir per jam operasional untuk penjadwalan kasir.</small>
        </div>
        
        <form action="{{ route('analytics.index') }}" method="GET" class="d-flex align-items-center gap-2">
            <input type="hidden" name="dead_stock_days" value="{{ $daysThreshold }}">
            <span class="small text-muted fw-bold text-nowrap">Periode:</span>
            <select name="peak_period_days" class="form-select form-select-sm" onchange="this.form.submit()" style="border-radius: 8px;">
                <option value="7" {{ $hourlyPeriodDays == 7 ? 'selected' : '' }}>7 Hari Terakhir</option>
                <option value="30" {{ $hourlyPeriodDays == 30 ? 'selected' : '' }}>30 Hari Terakhir</option>
                <option value="90" {{ $hourlyPeriodDays == 90 ? 'selected' : '' }}>3 Bulan Terakhir</option>
            </select>
        </form>
    </div>

    <!-- Kotak Sorotan Jam Tersibuk -->
    <div class="p-3 rounded mb-3 d-flex align-items-center justify-content-between" style="background-color: #f0f7ff; border-left: 5px solid #0d6efd;">
        <div>
            <span class="text-muted small fw-bold d-block">JAM TERSIBUK (PEAK HOUR):</span>
            <h5 class="fw-bold text-primary m-0 mt-1">{{ $peakHourLabel }}</h5>
        </div>
        <span class="badge bg-primary px-3 py-2 fs-6">Saran: Pastikan kasir standby penuh</span>
    </div>

    <div style="height: 250px;">
        <canvas id="peakHoursChart"></canvas>
    </div>
</div>

<!-- BAGIAN 2: BARANG MATI (DEAD STOCK & MODAL MACET) -->
<div class="card border-0 p-4 shadow-sm" style="border-radius: 15px; background: #fff;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold m-0 text-danger">📦 Analisis Barang Mati (Dead Stock)</h5>
            <small class="text-muted">Daftar produk yang mengendap di etalase/rak tanpa ada transaksi penjualan.</small>
        </div>

        <form action="{{ route('analytics.index') }}" method="GET" class="d-flex align-items-center gap-2">
            <input type="hidden" name="peak_period_days" value="{{ $hourlyPeriodDays }}">
            <span class="small text-muted fw-bold text-nowrap">Batas Tidak Laku:</span>
            <select name="dead_stock_days" class="form-select form-select-sm" onchange="this.form.submit()" style="border-radius: 8px;">
                <option value="30" {{ $daysThreshold == 30 ? 'selected' : '' }}>&gt; 30 Hari</option>
                <option value="60" {{ $daysThreshold == 60 ? 'selected' : '' }}>&gt; 60 Hari (2 Bulan)</option>
                <option value="90" {{ $daysThreshold == 90 ? 'selected' : '' }}>&gt; 90 Hari (3 Bulan)</option>
            </select>
        </form>
    </div>

    <!-- Kartu Total Modal Mandek -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-3 rounded bg-light border-0 shadow-sm" style="border-left: 4px solid #dc3545 !important;">
                <small class="text-muted fw-bold d-block">TOTAL MODAL MANDUK (UANG MACET)</small>
                <h4 class="fw-bold mt-1 text-danger">Rp {{ number_format($totalDeadCapital, 0, ',', '.') }}</h4>
                <small class="text-muted">{{ count($deadStockList) }} Item barang belum laku</small>
            </div>
        </div>
        <div class="col-md-8 d-flex align-items-center">
            <div class="alert alert-warning m-0 w-100 py-2 px-3" style="font-size: 13px; border-radius: 10px;">
                💡 <strong>Solusi Ritel:</strong> Barang yang mengendap terlalu lama berisiko rusak, kemasan kusam, atau modelnya ketinggalan zaman. Gunakan tombol <strong>"Buat Promo Cuci Gudang"</strong> untuk segera mengobral barang tersebut agar modal toko lekas berputar kembali!
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="deadStockTable" class="table table-hover align-middle" style="font-size: 13px; width: 100%;">
            <thead class="table-light">
                <tr>
                    <th width="4%">No</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Stok di Rak</th>
                    <th>Harga Modal (HPP)</th>
                    <th>Total Modal Macet</th>
                    <th>Hari Tanpa Penjualan</th>
                    <th>Terakhir Terjual</th>
                    <th width="15%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deadStockList as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td class="fw-bold text-dark">
                        {{ $item['product']->name }}
                        @if($item['product']->brand)
                            <small class="text-muted d-block font-monospace">Merek: {{ $item['product']->brand }}</small>
                        @endif
                    </td>
                    <td>{{ $item['product']->category->name ?? '-' }}</td>
                    <td><span class="badge bg-secondary fs-6">{{ $item['stock'] }} pcs</span></td>
                    <td>Rp {{ number_format($item['cost_price'], 0, ',', '.') }}</td>
                    <td class="fw-bold text-danger">Rp {{ number_format($item['tied_capital'], 0, ',', '.') }}</td>
                    <td>
                        <span class="badge bg-danger">
                            &ge; {{ $item['days_idle'] }} Hari
                        </span>
                    </td>
                    <td><small class="text-muted">{{ $item['last_sold_date'] }}</small></td>
                    <td class="text-center">
                        <a href="{{ route('discounts.index') }}" class="btn btn-sm btn-outline-danger fw-bold" style="border-radius: 8px;">
                            🏷️ Cuci Gudang
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- SCRIPTS: DATATABLES & CHART.JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() {
        $('#deadStockTable').DataTable({
            order: [[5, 'desc']], // Urutkan dari modal macet terbesar
            language: { search: "Cari Barang Mati:", lengthMenu: "Tampil _MENU_", info: "_START_ - _END_ dari _TOTAL_ produk" }
        });

        // RENDER DIAGRAM BATANG JAM SIBUK 24 JAM
        const ctx = document.getElementById('peakHoursChart').getContext('2d');
        const labels = @json($hoursLabels);
        const dataTrx = @json($trxPerHours);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: dataTrx,
                    backgroundColor: 'rgba(13, 110, 253, 0.45)',
                    borderColor: '#0d6efd',
                    borderWidth: 1.5,
                    borderRadius: 5,
                    hoverBackgroundColor: '#d17885'
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
                                return 'Transaksi: ' + context.raw + ' Faktur';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11 },
                            maxRotation: 0,
                            autoSkip: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    });
</script>
@endsection