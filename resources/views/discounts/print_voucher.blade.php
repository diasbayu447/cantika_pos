<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kupon Voucher - {{ $promo->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }
        * { box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif;
            margin: 0; 
            padding: 0; 
            background: #fff; 
            color: #222; 
        }

        .no-print {
            padding: 12px 20px;
            background: #2b2d42;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }
        .btn-print {
            background: #d17885;
            color: white;
            border: none;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }

        .voucher-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4mm;
            padding: 6mm 4mm;
            width: 100%;
        }

        .voucher-card {
            border: 1.5px dashed #d17885;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            position: relative;
            background: #fffdf8;
            page-break-inside: avoid;
            break-inside: avoid;
            height: 48mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .store-tag {
            font-size: 9px;
            font-weight: bold;
            color: #d17885;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .promo-name {
            font-size: 12px;
            font-weight: bold;
            margin: 2px 0;
            line-height: 1.2;
            color: #111;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .promo-val {
            font-size: 11px;
            font-weight: bold;
            color: #198754;
            background: #eefaf2;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            margin: 0 auto;
        }

        .code-box {
            background: #212529;
            color: #fff;
            padding: 4px 6px;
            border-radius: 4px;
            margin: 4px 0;
        }

        .code-text {
            font-family: monospace;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1.5px;
            margin: 0;
        }

        .footer-rule {
            font-size: 8.5px;
            color: #666;
            margin: 0;
            line-height: 1.2;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: transparent !important; }
            .voucher-card { 
                background: #fffdf8 !important; 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <span>Voucher: <strong>{{ $promo->name }}</strong> | <strong>{{ count($voucherCodes) }} Kupon Aktif</strong> Siap Cetak</span>
        <div>
            <button class="btn-print" onclick="window.print()">🖨️ Cetak Sekarang</button>
            <button class="btn-print" style="background:#555;" onclick="window.close()">Tutup</button>
        </div>
    </div>

    <div class="voucher-grid">
        @forelse($voucherCodes as $vc)
            <div class="voucher-card">
                <div>
                    <div class="store-tag">CANTIKA AKSESORIS</div>
                    <div class="promo-name">{{ $promo->name }}</div>
                    <div class="promo-val">
                        Diskon {{ $promo->discount_type === 'percentage' ? $promo->amount . '%' : 'Rp ' . number_format($promo->amount, 0, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="code-box">
                        <p class="code-text">{{ $vc->code }}</p>
                    </div>
                    @if($promo->min_purchase > 0)
                        <p class="footer-rule">Min. Belanja Rp {{ number_format($promo->min_purchase, 0, ',', '.') }}</p>
                    @endif
                    <p class="footer-rule">Tunjukkan kode ini saat bayar di kasir</p>
                </div>
            </div>
        @empty
            <div style="grid-column: span 3; text-align: center; padding: 40px; color: #888;">
                Tidak ada kode kupon yang aktif atau tersedia untuk dicetak.
            </div>
        @endforelse
    </div>

</body>
</html>