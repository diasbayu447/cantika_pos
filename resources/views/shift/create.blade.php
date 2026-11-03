<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buka Shift Kasir - Cantika POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    {{-- PENCEGAT TOMBOL BACK MODERN --}}
    <script>
        window.addEventListener('pageshow', function (event) {
            const navEntries = performance.getEntriesByType('navigation');
            const isBackForward = navEntries.length > 0 && navEntries[0].type === 'back_forward';
            
            if (event.persisted || isBackForward) {
                window.location.replace("{{ route('pos.index') }}");
            }
        });
    </script>

    <style>
        body {
            background-color: #f7f4f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .shift-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 420px;
            padding: 35px 30px;
        }
    </style>
</head>
<body>

    <div class="shift-card text-center">
        <h4 class="fw-bold mb-2" style="color: #d17885;">BUKA SHIFT KASIR</h4>
        <p class="text-muted small mb-4">
            Halo, <strong>{{ strtoupper(auth()->user()->display_name ?? auth()->user()->name) }}</strong>.<br>
            Silakan masukkan modal awal uang kembalian di laci Anda.
        </p>

        <div class="alert alert-warning py-2 px-3 small mb-4" style="border-radius: 10px; font-size: 12px;">
            ⚠️ Anda harus memasukkan Modal Awal (Buka Shift) sebelum melayani pelanggan.
        </div>

        <form id="openShiftForm" action="{{ route('shift.store') }}" method="POST" class="text-start">
            @csrf
            <div class="mb-4">
                <label class="form-label small fw-bold text-dark">Uang Modal Awal (Rp)</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light fw-bold text-muted">Rp</span>
                    <input type="text" 
                           name="start_cash" 
                           id="startCashInput" 
                           class="form-control fw-bold font-monospace" 
                           placeholder="0" 
                           required 
                           autofocus 
                           autocomplete="off"
                           oninput="formatInputRupiah(this)">
                </div>
                <small id="rupiahPreview" class="text-success fw-bold d-block mt-1">Rp 0</small>
            </div>

            <button type="submit" id="btnSubmitShift" class="btn btn-lg w-100 fw-bold text-white mb-3" style="background-color: #f2a6b0; border-radius: 10px; font-size: 15px;">
                MULAI JUALAN [Enter]
            </button>
        </form>

        <form action="{{ route('logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn btn-link text-muted small text-decoration-none">Batalkan & Logout</button>
        </form>
    </div>

    <script>
        function formatInputRupiah(el) {
            let cleanVal = el.value.replace(/[^0-9]/g, '');
            el.value = cleanVal;
            
            let num = parseInt(cleanVal) || 0;
            document.getElementById('rupiahPreview').innerText = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(num);
        }

        document.getElementById('openShiftForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let form = this;
            let btn = document.getElementById('btnSubmitShift');
            btn.disabled = true;
            btn.innerText = 'MEMBUKA LACI...';

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(() => {
                window.location.replace("{{ route('pos.index') }}");
            }).catch(() => {
                form.submit();
            });
        });
    </script>
</body>
</html>