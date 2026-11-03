@extends('layouts.app')

@section('title', 'Log Aktivitas & Audit Trail')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show fw-bold" style="border-radius: 10px;" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger fw-bold" style="border-radius: 10px;">
        <ul class="mb-0">
            @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 p-4 shadow-sm" style="border-radius: 15px; background: #fff;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold m-0" style="color: #333;">Audit Trail & Rekaman Log Sistem</h5>
            <small class="text-muted">Jejak audit operasional kasir, kulakan, otorisasi, dan riwayat pengeditan data.</small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" style="font-size: 13px;">
            <thead class="table-light">
                <tr>
                    <th width="15%">Waktu</th>
                    <th width="15%">Pengguna</th>
                    <th width="16%">Tindakan</th>
                    <th>Keterangan & Rincian Perubahan</th>
                    <th width="16%" class="text-center">Aksi Rollback</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $oldVals = is_array($log->old_values) ? $log->old_values : json_decode($log->old_values, true);
                    $newVals = is_array($log->new_values) ? $log->new_values : json_decode($log->new_values, true);
                @endphp
                <tr>
                    <td class="text-muted">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>
                        <strong>{{ $log->user->name ?? 'Sistem' }}</strong><br>
                        <small class="text-muted font-monospace">({{ strtoupper($log->user->role ?? '-') }})</small>
                    </td>
                    <td>
                        @if(str_contains(strtolower($log->action), 'rollback'))
                            <span class="badge bg-warning text-dark font-monospace">⏪ {{ $log->action }}</span>
                        @elseif(str_contains(strtolower($log->action), 'hapus') || str_contains(strtolower($log->action), 'kick'))
                            <span class="badge bg-danger font-monospace">🛑 {{ $log->action }}</span>
                        @elseif(str_contains(strtolower($log->action), 'tambah') || str_contains(strtolower($log->action), 'kulakan'))
                            <span class="badge bg-success font-monospace">✨ {{ $log->action }}</span>
                        @else
                            <span class="badge bg-secondary font-monospace">{{ $log->action }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="text-dark fw-bold">{{ $log->description ?? '-' }}</span>

                        {{-- Tampilkan detail angka harga/stok yang berubah --}}
                        @if(!empty($oldVals) && !empty($newVals))
                            <div class="mt-1 p-2 rounded bg-light border" style="font-size: 11.5px;">
                                @if(($oldVals['retail_price'] ?? null) != ($newVals['retail_price'] ?? null))
                                    <div>
                                        Harga Eceran: <del class="text-muted">Rp {{ number_format($oldVals['retail_price'] ?? 0, 0, ',', '.') }}</del> 
                                        &rarr; <strong class="text-primary">Rp {{ number_format($newVals['retail_price'] ?? 0, 0, ',', '.') }}</strong>
                                    </div>
                                @endif
                                @if(($oldVals['cost_price'] ?? null) != ($newVals['cost_price'] ?? null))
                                    <div>
                                        Harga Modal (HPP): <del class="text-muted">Rp {{ number_format($oldVals['cost_price'] ?? 0, 0, ',', '.') }}</del> 
                                        &rarr; <strong class="text-danger">Rp {{ number_format($newVals['cost_price'] ?? 0, 0, ',', '.') }}</strong>
                                    </div>
                                @endif
                                @if(($oldVals['wholesale_price'] ?? null) != ($newVals['wholesale_price'] ?? null))
                                    <div>
                                        Harga Grosir: <del class="text-muted">Rp {{ number_format($oldVals['wholesale_price'] ?? 0, 0, ',', '.') }}</del> 
                                        &rarr; <strong class="text-success">Rp {{ number_format($newVals['wholesale_price'] ?? 0, 0, ',', '.') }}</strong>
                                    </div>
                                @endif
                                @if(($oldVals['stock'] ?? null) != ($newVals['stock'] ?? null))
                                    <div>
                                        Stok: <del class="text-muted">{{ $oldVals['stock'] ?? 0 }}</del> 
                                        &rarr; <strong>{{ $newVals['stock'] ?? 0 }} pcs</strong>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="text-center text-nowrap">
                        @if(!empty($oldVals) && !$log->is_rolled_back)
                            <form action="{{ route('activity_logs.rollback', $log->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning fw-bold px-2 py-1 text-dark" 
                                        onclick="return confirm('Kembalikan data barang ini ke kondisi sebelum diedit?');"
                                        title="Kembalikan data lama" style="border-radius: 6px; font-size: 11.5px;">
                                    ⏪ Rollback Data
                                </button>
                            </form>
                        @elseif($log->is_rolled_back)
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 11px;">✅ Sudah di-Undo</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat aktivitas tercatat.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-end mt-3">
        {{ $logs->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection