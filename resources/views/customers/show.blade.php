@extends('layouts.app')

@section('title', 'Riwayat Belanja Pelanggan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark m-0">👤 Riwayat Belanja: {{ $customer->name }}</h4>
        <span class="text-muted small">No. WA: {{ $customer->phone ?? 'Tidak ada' }} | Tipe: <strong>{{ strtoupper($customer->status) }}</strong></span>
    </div>
    <a href="{{ route('customers.index') }}" class="btn btn-sm btn-secondary fw-bold px-3" style="border-radius: 8px;">
        Kembali ke Daftar
    </a>
</div>

<!-- 3 KARTU METRIK RINGKASAN -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 p-3 h-100 shadow-sm" style="border-radius: 12px; background: #fff; border-left: 4px solid #198754 !important;">
            <small class="text-muted fw-bold">TOTAL AKUMULASI BELANJA</small>
            <h3 class="fw-bold mt-2 text-success">Rp {{ number_format($totalSpent, 0, ',', '.') }}</h3>
            <small class="text-muted">Dari seluruh transaksi yang tercatat</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 p-3 h-100 shadow-sm" style="border-radius: 12px; background: #fff; border-left: 4px solid #0d6efd !important;">
            <small class="text-muted fw-bold">TOTAL FREKUENSI KUNJUNGAN</small>
            <h3 class="fw-bold mt-2 text-primary">{{ $totalTrx }} Kali</h3>
            <small class="text-muted">Total nota/faktur transaksi di kasir</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 p-3 h-100 shadow-sm" style="border-radius: 12px; background: #fff; border-left: 4px solid #d17885 !important;">
            <small class="text-muted fw-bold">RATA-RATA BELANJA PER NOTA</small>
            <h3 class="fw-bold mt-2 text-dark">
                Rp {{ number_format($totalTrx > 0 ? ($totalSpent / $totalTrx) : 0, 0, ',', '.') }}
            </h3>
            <small class="text-muted">Basket size rata-rata pelanggan</small>
        </div>
    </div>
</div>

<!-- HISTOGRAM TREN BELANJA (CHART.JS) -->
<div class="card border-0 p-4 mb-4 shadow-sm" style="border-radius: 15px; background: #fff;">
    <h6 class="fw-bold text-dark mb-1">📊 Histogram Tren Pengeluaran Belanja (12 Bulan Terakhir)</h6>
    <small class="text-muted mb-3 d-block">Analisis grafik ini berguna bagi Owner untuk merancang program diskon atau paket grosir khusus.</small>
    <div style="height: 220px;">
        <canvas id="customerSpendChart"></canvas>
    </div>
</div>

<!-- TABEL RINCIAN TRANSAKSI & DETAIL BARANG -->
<div class="card border-0 p-4 shadow-sm" style="border-radius: 15px; background: #fff;">
    <h6 class="fw-bold text-dark mb-3">📋 Rincian Transaksi & Barang yang Pernah Dibeli</h6>
    
    <div class="table-responsive">
        <table class="table table-bordered align-middle" style="font-size: 13px;">
            <thead class="table-light">
                <tr>
                    <th width="15%">No. Faktur</th>
                    <th width="15%">Tanggal</th>
                    <th width="10%">Metode</th>
                    <th>Rincian Barang yang Dibeli</th>
                    <th width="15%" class="text-end">Total Belanja</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                <tr>
                    <td class="fw-bold font-monospace text-primary">{{ $trx->invoice_number }}</td>
                    <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                    <td><span class="badge bg-light text-dark border">{{ strtoupper($trx->payment_method) }}</span></td>
                    <td>
                        <ul class="mb-0 ps-3">
                            @foreach($trx->details as $d)
                                <li>
                                    <strong>{{ $d->product->name ?? 'Barang' }}</strong> 
                                    — {{ $d->qty }} pcs x Rp {{ number_format($d->selling_price, 0, ',', '.') }}
                                    <span class="text-muted">(Rp {{ number_format($d->subtotal, 0, ',', '.') }})</span>
                                </li>
                            @endforeach
                        </ul>
                    </td>
                    <td class="text-end fw-bold text-dark fs-6">
                        Rp {{ number_format($trx->total_amount, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">Pelanggan ini belum pernah melakukan transaksi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const labels = @json($chartLabels);
        const dataValues = @json($chartTotals);

        const ctx = document.getElementById('customerSpendChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels.length ? labels : ['Belum Ada Data'],
                datasets: [{
                    label: 'Total Belanja (Rp)',
                    data: dataValues.length ? dataValues : [0],
                    backgroundColor: 'rgba(209, 120, 133, 0.6)',
                    borderColor: '#d17885',
                    borderWidth: 2,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) { return 'Rp ' + val.toLocaleString('id-ID'); }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection