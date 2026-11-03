<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cantika Aksesoris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    {{-- JEBAKAN 1: Jika server mendeteksi user masih login saat merender halaman ini --}}
    @auth
        <script>
            window.location.replace("{{ auth()->user()->role === 'cashier' ? route('pos.index') : route('dashboard') }}");
        </script>
    @endauth

    {{-- JEBAKAN 2: Deteksi BFCache / Navigasi Tombol Back Modern --}}
    <script>
        window.addEventListener('pageshow', function (event) {
            const navEntries = performance.getEntriesByType('navigation');
            const isBackForward = navEntries.length > 0 && navEntries[0].type === 'back_forward';
            
            // Jika halaman ini muncul karena tombol Back peramban ditekan, langsung pantulkan ke POS
            if (event.persisted || isBackForward) {
                window.location.replace("{{ route('pos.index') }}");
            }
        });
    </script>
</head>

<body class="login-page">

<div class="login-card text-center">
    <div class="brand-logo">👑🎀</div>
    <h4 class="brand-title">CANTIKA AKSESORIS</h4>
    <p class="text-muted-custom mb-4">Silakan masuk ke akun Anda</p>

    @if($errors->any())
        <div class="alert alert-danger" style="border-radius: 10px; font-size: 13px; text-align: left;">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('alert_kicked'))
        <div class="alert alert-danger text-center fw-bold small mb-3" style="border-radius: 10px;">
            {{ session('alert_kicked') }}
        </div>
    @endif

    <form action="{{ route('login') }}" method="POST">
        @csrf
        
        {{-- MUNCUL OTOMATIS JIKA TERDETEKSI ZOMBIE SESSION --}}
        @if(session('needs_spv_pin'))
            <div class="alert alert-warning text-start mb-4" style="border-radius: 10px; font-size: 13px; border: 1px solid #ffc107;">
                <strong>⚠️ Akses Terkunci (Sesi Aktif)</strong><br>
                Akun ini belum logout dari perangkat lain. Minta Supervisor atau Owner untuk memasukkan PIN di bawah ini guna memutus sesi lama.
            </div>
            
            <div class="mb-4 text-start p-3 rounded" style="background-color: #fff5f5; border: 1px dashed #dc3545;">
                <label class="fw-bold small text-danger mb-1">PIN Supervisor / Owner (6 Digit)</label>
                <input type="password" name="spv_pin" class="form-control border-danger font-monospace fw-bold text-center fs-5 shadow-sm" placeholder="******" maxlength="6" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" autofocus autocomplete="off">
            </div>
        @endif

        <div class="mb-3 text-start">
            <input type="text" name="username" class="form-control" placeholder="Username" value="{{ old('username') }}" required {{ !session('needs_spv_pin') ? 'autofocus' : '' }} autocomplete="off">
        </div>
        
        <div class="mb-4 text-start">
            <input type="password" name="password" class="form-control" placeholder="Password Akun Anda" required>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-login">MASUK</button>
        </div>
    </form>

    <div class="mt-3 text-end">
        <a href="#" style="color: #d17885; text-decoration: none; font-size: 13px; border-bottom: 1px solid #d17885;">Lupa password</a>
    </div>
    
    <div class="d-flex justify-content-center align-items-center mt-4 mb-2">
    <img src="{{ asset('images/logo-ut.svg') }}" alt="Logo UT" style="height: 45px; margin-right: 15px;">
    <div class="text-start" style="border-left: 2px solid #d17885; padding-left: 15px; line-height: 1.2;">
        <span class="fw-bold" style="color: #333; font-size: 13px;">© 2026 Cantika POS - Capstone Project</span><br>
        <span class="text-muted" style="font-size: 12px;">Program Studi Sistem Informasi</span><br>
        <span class="text-muted fw-bold" style="font-size: 12px;">Universitas Terbuka</span>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>