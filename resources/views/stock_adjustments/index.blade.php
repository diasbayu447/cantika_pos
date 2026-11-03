@extends('layouts.app')

@section('title', 'Riwayat Stock Opname')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="card border-0 p-4" style="border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="m-0" style="color: #333; font-weight: 600;">Riwayat Penyesuaian Stok</h5>
        <a href="{{ route('stock-adjustments.create') }}" class="btn" style="background-color: #f2a6b0; color: white; border-radius: 8px;">+ Penyesuaian Baru</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="border-radius: 10px;">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive">
        <table id="opnameTable" class="table table-hover align-middle" style="font-size: 14px;">
            <thead class="table-light">
                <tr>
                    <th>Waktu Kejadian</th>
                    <th>Nama Barang</th>
                    <th class="text-center">Stok Sistem</th>
                    <th class="text-center">Stok Fisik</th>
                    <th class="text-center">Selisih</th>
                    <th>Alasan</th>
                    <th>Otorisasi Oleh</th>
                </tr>
            </thead>
            <tbody>
                @foreach($adjustments as $adj)
                <tr>
                    <td>{{ $adj->created_at->format('d/m/Y H:i') }}</td>
                    <td class="fw-bold text-dark">{{ $adj->product->name ?? '-' }}</td>
                    <td class="text-center">{{ $adj->system_stock }}</td>
                    <td class="text-center fw-bold">{{ $adj->actual_stock }}</td>
                    <td class="text-center">
                        @if($adj->difference > 0)
                            <span class="badge bg-success">+{{ $adj->difference }}</span>
                        @elseif($adj->difference < 0)
                            <span class="badge bg-danger">{{ $adj->difference }}</span>
                        @else
                            <span class="badge bg-secondary">0</span>
                        @endif
                    </td>
                    <td class="text-muted">{{ $adj->reason }}</td>
                    <td><span class="badge bg-light text-dark border">{{ $adj->supervisor->name ?? '-' }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#opnameTable').DataTable({ 
            order: [[0, 'desc']],
            language: {
                search: "Cari Riwayat:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ riwayat"
            }
        });
    });
</script>
@endsection