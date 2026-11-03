<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label Barcode - Cantika Aksesoris</title>
    
    @php
        $isA4 = ($paperSize === 'a4_grid');
        
        $dims = [
            '33x15' => ['w' => '33mm', 'h' => '15mm', 'b_w' => 0.9, 'b_h' => 14, 'f_name' => '8px', 'f_price' => '10px', 'show_shop' => false],
            '33x19' => ['w' => '33mm', 'h' => '19mm', 'b_w' => 0.9, 'b_h' => 16, 'f_name' => '8px', 'f_price' => '10px', 'show_shop' => false],
            '40x30' => ['w' => '40mm', 'h' => '30mm', 'b_w' => 1.1, 'b_h' => 24, 'f_name' => '10px', 'f_price' => '13px', 'show_shop' => true],
            '50x30' => ['w' => '50mm', 'h' => '30mm', 'b_w' => 1.3, 'b_h' => 26, 'f_name' => '11px', 'f_price' => '14px', 'show_shop' => true],
            '55x20' => ['w' => '55mm', 'h' => '20mm', 'b_w' => 1.0, 'b_h' => 14, 'f_name' => '8.5px', 'f_price' => '10.5px', 'show_shop' => false],
            '38x60' => ['w' => '38mm', 'h' => '60mm', 'b_w' => 1.1, 'b_h' => 36, 'f_name' => '11px', 'f_price' => '15px', 'show_shop' => true],
        ];

        $current = $dims[$paperSize] ?? $dims['40x30'];
    @endphp

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f1f3f5;
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
            padding: 7px 16px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
        }

        @if($isA4)
            @page {
                size: A4 portrait;
                margin: 8mm;
            }
            .labels-container {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 4mm;
                padding: 8mm;
            }
            .label-card {
                border: 1px dashed #bbb;
                padding: 3mm 4mm;
                text-align: center;
                background: #fff;
                border-radius: 4px;
                page-break-inside: avoid;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                height: 38mm;
            }
            .shop-name { font-size: 8px; font-weight: bold; color: #d17885; text-transform: uppercase; }
            .product-name { font-size: 10px; font-weight: bold; color: #111; line-height: 1.2; margin: 1mm 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .price-tag { font-size: 13px; font-weight: bold; color: #000; }
            .wholesale-info { font-size: 8px; font-weight: bold; color: #d17885; }
        @else
            @page {
            size: {{ $current['w'] }} {{ $current['h'] }} portrait;
            margin: 0;
            }
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                width: {{ $current['w'] }};
                height: {{ $current['h'] }};
            }
            .labels-container {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                padding: 15px;
            }
            .label-card {
                width: {{ $current['w'] }};
                height: {{ $current['h'] }};
                background: #fff;
                text-align: center;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                align-items: center;
                padding: 1mm 1.5mm;
                overflow: hidden;
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                page-break-after: always;
                break-after: page;
            }
            .shop-name { font-size: 7px; font-weight: bold; color: #d17885; text-transform: uppercase; line-height: 1; }
            .product-name {
                font-size: {{ $current['f_name'] }};
                font-weight: bold;
                color: #111;
                line-height: 1.1;
                margin: 0.5mm 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                width: 100%;
            }
            .price-tag { font-size: {{ $current['f_price'] }}; font-weight: bold; color: #000; line-height: 1; }
            .wholesale-info { font-size: 7px; font-weight: bold; color: #d17885; line-height: 1; }
        @endif

        .barcode-container { width: 100%; display: flex; justify-content: center; }
        .barcode-svg { max-width: 100%; }

        @media print {
            .no-print { display: none !important; }
            body { background: transparent !important; }
            .labels-container { display: block !important; padding: 0 !important; gap: 0 !important; }
            .label-card {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <span>Format: <strong>{{ $isA4 ? 'Lembaran A4 Grid' : $paperSize . ' mm (Thermal Roll)' }}</strong> | Total: <strong>{{ count($labels) }} Stiker</strong></span>
        <div>
            <button class="btn-print" onclick="window.print()">🖨️ Cetak Sekarang</button>
            <button class="btn-print" style="background:#555;" onclick="window.close()">Tutup</button>
        </div>
    </div>

    <div class="labels-container">
        @foreach($labels as $product)
        <div class="label-card">
            @if($isA4 || $current['show_shop'])
                <div class="shop-name">CANTIKA AKSESORIS</div>
            @endif

            <div class="product-name" title="{{ $product->name }}">{{ $product->name }}</div>
            
            <div class="barcode-container">
                <svg class="barcode-svg" data-barcode="{{ $product->barcode }}"></svg>
            </div>

            <div class="price-tag">
                Rp {{ number_format($product->retail_price, 0, ',', '.') }}
            </div>
            
            @if($product->wholesale_price > 0)
            <div class="wholesale-info">
                *Grosir? Tanya Kasir
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let bWidth = {{ $isA4 ? 1.2 : $current['b_w'] }};
            let bHeight = {{ $isA4 ? 26 : $current['b_h'] }};

            document.querySelectorAll('.barcode-svg').forEach(function(el) {
                let code = el.getAttribute('data-barcode');
                if (code) {
                    try {
                        JsBarcode(el, code, {
                            format: "CODE128",
                            lineColor: "#000",
                            width: bWidth,
                            height: bHeight,
                            displayValue: true,
                            fontSize: 8,
                            margin: 1
                        });
                    } catch (e) {
                        el.parentElement.innerHTML = '<small style="color:red; font-size:8px;">Barcode Tidak Valid</small>';
                    }
                }
            });
        });
    </script>
</body>
</html>