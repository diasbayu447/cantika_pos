<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $transaction->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
            size: 58mm auto; /* Standar Kertas Thermal 58mm */
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 8px 6px;
            width: 58mm;
            box-sizing: border-box;
            background: #fff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            vertical-align: top;
            padding: 1px 0;
            font-size: 11px;
        }
        .reprint-badge {
            border: 1px dashed #000;
            padding: 2px 4px;
            margin: 5px 0;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 1px;
        }
        .no-print {
            margin-bottom: 12px;
            text-align: center;
            font-family: Arial, sans-serif;
        }
        .btn-action {
            padding: 6px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 11px;
            font-weight: bold;
            margin: 2px;
        }
        @media print {
            .no-print { display: none !important; }
            body { width: 100%; padding: 0; }
        }
    </style>
</head>
<body>

    <!-- Tombol bantu jika dibuka di luar iframe kasir -->
    <div class="no-print" id="standaloneToolbar">
        <button class="btn-action" style="background:#333; color:#fff;" onclick="window.print()">🖨️ Cetak</button>
        <button class="btn-action" style="background:#dc3545; color:#fff;" onclick="window.close()">✖ Tutup</button>
        <div class="divider"></div>
    </div>

    @php $store = \App\Models\StoreSetting::current(); @endphp
    <div class="text-center">
        <div class="font-bold" style="font-size: 13px;">{{ strtoupper($store->shop_name ?? 'CANTIKA POS') }}</div>
        <div>{{ $store->address ?? '' }}</div>
        <div>Layanan/Komplain: {{ $store->phone ?? '' }}</div>
    </div>

    {{-- PENANDA KHUSUS: Muncul jika dicetak ulang --}}
    @if(request('reprint') == '1' || isset($isReprint) && $isReprint)
    <div class="reprint-badge">
        *** CETAKAN ULANG ***
    </div>
    @else
    <div class="divider"></div>
    @endif

    <table>
        <tr>
            <td width="35%">No</td>
            <td>: {{ $transaction->invoice_number }}</td>
        </tr>
        <tr>
            <td>Tgl</td>
            <td>: {{ $transaction->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Kasir</td>
            <td>: {{ $transaction->user->display_name ?? $transaction->user->name }}</td>
        </tr>
        <tr>
            <td>Plg</td>
            <td>: {{ $transaction->customer->name ?? 'Umum' }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        @foreach($transaction->details as $detail)
        <tr>
            <td colspan="2" class="font-bold">{{ $detail->product->name ?? 'Produk' }}</td>
        </tr>
        @if(!empty($detail->target_number))
        <tr>
            <td colspan="2" style="font-size: 10px; color: #222;">
                &gt; No/ID: {{ $detail->target_number }}
            </td>
        </tr>
        @endif

        {{-- Khusus Produk Digital Pascabayar: Cetak Rincian Tagihan & Admin --}}
        @if(($detail->product->product_type ?? '') === 'digital_pascabayar' && $detail->cost_price > 0)
        <tr>
            <td style="font-size: 10px; color: #333;">Tagihan</td>
            <td class="text-right" style="font-size: 10px;">{{ number_format($detail->cost_price, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="font-size: 10px; color: #333;">Biaya Admin</td>
            <td class="text-right" style="font-size: 10px;">{{ number_format($detail->selling_price - $detail->cost_price, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="font-bold">Total</td>
            <td class="text-right font-bold">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
        </tr>
        @else
        <tr>
            <td>{{ $detail->qty }} x {{ number_format($detail->selling_price, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endif
        @endforeach
    </table>

    <div class="divider"></div>

    <table>
        @if($transaction->discount_amount > 0)
        <tr>
            <td>SUBTOTAL</td>
            <td class="text-right">Rp {{ number_format($transaction->total_amount + $transaction->discount_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>DISKON</td>
            <td class="text-right">-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr>
            <td class="font-bold" style="font-size: 12px;">TOTAL</td>
            <td class="text-right font-bold" style="font-size: 12px;">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>BAYAR ({{ strtoupper($transaction->payment_method) }})</td>
            <td class="text-right">
                Rp {{ number_format($transaction->payment_method == 'cash' ? $transaction->cash_amount : $transaction->non_cash_amount, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td>KEMBALI</td>
            <td class="text-right font-bold">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="text-center" style="margin-top: 6px;">
        <div>Terima Kasih Atas Kunjungan Anda!</div>
        <div style="font-size: 10px;">{{ $store->receipt_footer ?? '' }}</div>
    </div>

    <script>
        if (window.self !== window.top) {
            document.getElementById('standaloneToolbar').style.display = 'none';
        }

        window.onload = function() {
            setTimeout(function() {
                window.focus();
                window.print();
            }, 250);
        };
    </script>
</body>
</html>