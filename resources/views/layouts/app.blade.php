<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cantika POS') - Cantika Aksesoris</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f7f4f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #ffffff;
            border-right: 1px solid #f0e6e8;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }
        .sidebar-brand {
            padding: 18px 20px;
            font-size: 1.15rem;
            font-weight: 800;
            color: #d17885;
            letter-spacing: 0.8px;
            border-bottom: 1px solid #fdf2f4;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sidebar-menu {
            padding: 12px 14px;
            flex-grow: 1;
            overflow-y: auto;
        }
        .sidebar-menu::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-menu::-webkit-scrollbar-thumb {
            background-color: #f2cdd2;
            border-radius: 4px;
        }
        .menu-category-btn {
            width: 100%;
            text-align: left;
            background: transparent;
            border: none;
            color: #444;
            padding: 9px 12px;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px;
            margin-top: 4px;
            transition: all 0.2s;
        }
        .menu-category-btn:hover {
            background-color: #fdf2f4;
            color: #d17885;
        }
        .menu-category-btn.active-group {
            color: #d17885;
            background-color: #fff6f7;
        }
        .menu-category-btn .arrow-icon {
            font-size: 11px;
            transition: transform 0.2s ease;
        }
        .menu-category-btn:not(.collapsed) .arrow-icon {
            transform: rotate(90deg);
            color: #d17885;
        }
        .submenu-container {
            padding-left: 12px;
            margin: 2px 0 6px 0;
            border-left: 2px solid #f9e2e5;
        }
        .submenu-link {
            display: block;
            color: #666;
            text-decoration: none;
            padding: 6px 14px;
            font-size: 12.5px;
            font-weight: 500;
            border-radius: 6px;
            margin-bottom: 2px;
            transition: all 0.15s;
        }
        .submenu-link:hover {
            color: #d17885;
            background-color: #fdf2f4;
            padding-left: 18px;
        }
        .submenu-link.active {
            color: #fff;
            background-color: #d17885;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(209, 120, 133, 0.3);
        }
        .nav-single-link {
            display: flex;
            align-items: center;
            color: #444;
            text-decoration: none;
            padding: 9px 12px;
            border-radius: 8px;
            margin-bottom: 4px;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.2s;
        }
        .nav-single-link:hover {
            background-color: #fdf2f4;
            color: #d17885;
        }
        .nav-single-link.active {
            background-color: #d17885;
            color: white;
            box-shadow: 0 2px 8px rgba(209, 120, 133, 0.3);
        }
        .main-content {
            margin-left: 250px;
            padding: 22px 28px 50px 28px;
            min-height: 100vh;
        }
        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        @php $store = \App\Models\StoreSetting::current(); @endphp
        <div class="sidebar-brand">
            <span>👑</span> {{ strtoupper($store->shop_name ?? 'CANTIKA POS') }}
        </div>
        
        <nav class="sidebar-menu">
            <!-- 1. DASHBOARD RINGKASAN -->
            <a href="{{ route('dashboard') }}" class="nav-single-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="me-2">📊</span> Dashboard
            </a>

            @if(in_array(auth()->user()->role, ['owner', 'admin', 'supervisor']))
                <!-- 2. GRUP KATALOG & PRODUK -->
                @php
                    $isCatalogActive = request()->routeIs('products.*') || request()->routeIs('barcodes.*') || request()->routeIs('discounts.*') || request()->routeIs('suppliers.*') || request()->routeIs('customers.*');
                @endphp
                <button class="menu-category-btn {{ $isCatalogActive ? 'active-group' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#groupKatalog">
                    <span><span class="me-2">📦</span> Katalog & Stok</span>
                    <span class="arrow-icon">▶</span>
                </button>
                <div class="collapse {{ $isCatalogActive ? 'show' : '' }}" id="groupKatalog">
                    <div class="submenu-container">
                        <a href="{{ route('products.index') }}" class="submenu-link {{ request()->routeIs('products.*') ? 'active' : '' }}">Data Barang</a>
                        <a href="{{ route('barcodes.index') }}" class="submenu-link {{ request()->routeIs('barcodes.*') ? 'active' : '' }}">Cetak Barcode Rak</a>
                        <a href="{{ route('discounts.index') }}" class="submenu-link {{ request()->routeIs('discounts.*') ? 'active' : '' }}">Manajemen Diskon</a>
                        <a href="{{ route('customers.index') }}" class="submenu-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">Data Pelanggan</a>
                        <a href="{{ route('suppliers.index') }}" class="submenu-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">Data Supplier</a>
                    </div>
                </div>

                <!-- 3. GRUP PENGADAAN & GUDANG -->
                @php
                    $isProcureActive = request()->routeIs('purchases.*') || request()->routeIs('returns.*') || request()->routeIs('stock-adjustments.*');
                @endphp
                <button class="menu-category-btn {{ $isProcureActive ? 'active-group' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#groupPengadaan">
                    <span><span class="me-2">📥</span> Pengadaan & Retur</span>
                    <span class="arrow-icon">▶</span>
                </button>
                <div class="collapse {{ $isProcureActive ? 'show' : '' }}" id="groupPengadaan">
                    <div class="submenu-container">
                        <a href="{{ route('purchases.index') }}" class="submenu-link {{ request()->routeIs('purchases.index') || request()->routeIs('purchases.create') ? 'active' : '' }}">Kulakan (Masuk)</a>
                        <a href="{{ route('purchases.debts') }}" class="submenu-link {{ request()->routeIs('purchases.debts') ? 'active' : '' }}">Hutang Tempo</a>
                        <a href="{{ route('returns.index') }}" class="submenu-link {{ request()->routeIs('returns.*') ? 'active' : '' }}">Retur Barang</a>
                        <a href="{{ route('stock-adjustments.index') }}" class="submenu-link {{ request()->routeIs('stock-adjustments.*') ? 'active' : '' }}">Stock Opname</a>
                    </div>
                </div>
            @endif

            @if(in_array(auth()->user()->role, ['owner', 'supervisor']))
                <!-- 4. GRUP KEUANGAN & MANAJEMEN -->
                @php
                    $isFinanceActive = request()->routeIs('expenses.*') || request()->routeIs('reports.*') || request()->routeIs('analytics.*') || request()->routeIs('activity_logs.*') || request()->routeIs('users.*');
                @endphp
                <button class="menu-category-btn {{ $isFinanceActive ? 'active-group' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#groupKeuangan">
                    <span><span class="me-2">💼</span> Laporan & Akses</span>
                    <span class="arrow-icon">▶</span>
                </button>
                <div class="collapse {{ $isFinanceActive ? 'show' : '' }}" id="groupKeuangan">
                    <div class="submenu-container">
                        <a href="{{ route('expenses.index') }}" class="submenu-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">Biaya Operasional</a>
                        <a href="{{ route('reports.index') }}" class="submenu-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">Laporan Keuangan</a>
                        <a href="{{ route('analytics.index') }}" class="submenu-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}">Analitik Toko</a>
                        <a href="{{ route('activity_logs.index') }}" class="submenu-link {{ request()->routeIs('activity_logs.*') ? 'active' : '' }}">Log Aktivitas</a>
                        <a href="{{ route('users.index') }}" class="submenu-link {{ request()->routeIs('users.*') ? 'active' : '' }}">Kelola Staf</a>
                    </div>
                </div>
            @endif
        </nav>

        <!-- FOOTER LOGO UT DI SIDEBAR -->
        <div class="mt-auto p-3" style="border-top: 1px solid #fdf2f4; background-color: #fffafb;">
            <div class="d-flex align-items-center justify-content-center">
                <img src="{{ asset('images/logo-ut.svg') }}" alt="Logo UT" style="height: 38px; margin-right: 10px;">
                <div class="text-start" style="border-left: 2px solid #d17885; padding-left: 10px; line-height: 1.2;">
                    <span class="fw-bold" style="color: #333; font-size: 10px;">© 2026 Cantika POS</span><br>
                    <span class="text-muted" style="font-size: 9px;">Capstone Project</span><br>
                    <span class="text-muted fw-bold" style="font-size: 9px;">Universitas Terbuka</span>
                </div>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-navbar">
            <h4 class="fw-bold m-0" style="color: #333;">@yield('title')</h4>

            <div class="d-flex align-items-center gap-2">
                @if(auth()->user()->role === 'owner')
                <button type="button" 
                        class="btn btn-sm btn-outline-dark px-3 py-2 fw-bold shadow-sm" 
                        data-bs-toggle="modal" 
                        data-bs-target="#storeModal" 
                        style="border-radius: 20px; font-size: 13px;">
                    🏪 Profil Toko
                </button>
                @endif

                <button type="button" 
                        class="btn btn-sm btn-light border px-3 py-2 d-flex align-items-center gap-2 shadow-sm" 
                        data-bs-toggle="modal" 
                        data-bs-target="#profileModal" 
                        style="border-radius: 20px; font-size: 13px;"
                        title="Klik untuk ubah Password & PIN">
                    <span>Halo, <strong class="text-dark">{{ auth()->user()->display_name ?? auth()->user()->name }}</strong> ({{ strtoupper(auth()->user()->role) }})</span>
                    <span class="badge" style="background-color: #f2a6b0; color: white; border-radius: 50%;">⚙️</span>
                </button>

                <form action="{{ route('logout') }}" method="POST" class="m-0" onsubmit="localStorage.removeItem('app_locked')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger px-3 py-2 fw-bold" style="border-radius: 20px; font-size: 13px;">
                        Logout 🚪
                    </button>
                </form>
            </div>
        </header>

        @yield('content')
    </main>

    <!-- MODAL EDIT PROFIL MANDIRI -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: #d17885;">⚙️ Pengaturan Profil & Keamanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    <div class="modal-body pb-3">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Username Login</label>
                            <input type="text" class="form-control bg-light" value="{{ auth()->user()->username }}" readonly>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ auth()->user()->name }}" required autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Nama Alias</label>
                                <input type="text" name="display_name" class="form-control" value="{{ auth()->user()->display_name }}" autocomplete="off">
                            </div>
                        </div>

                        <div class="p-3 rounded mb-3" style="background-color: #fdf2f4; border: 1px dashed #d17885;">
                            <div class="mb-3">
                                <label class="form-label text-dark fw-bold small">Ganti Password Login</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="myPass" class="form-control" placeholder="Kosongkan jika tidak diganti" autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" onclick="toggleVisibility('myPass', this)">👁️</button>
                                </div>
                            </div>
                            <div>
                                <label class="form-label text-dark fw-bold small">PIN Otorisasi / Kunci Layar (6 Digit Unik)</label>
                                <div class="input-group">
                                    <input type="password" name="pin" id="inputPin" class="form-control font-monospace fw-bold" maxlength="6" value="{{ auth()->user()->pin ?? '' }}" placeholder="******" oninput="this.value = this.value.replace(/[^0-9]/g, '')" autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" onclick="toggleVisibility('inputPin', this)">👁️</button>
                                </div>
                                <small class="text-muted" style="font-size: 11px;">Hanya angka, tepat 6 digit dan wajib unik.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm fw-bold px-4 text-white" style="background-color: #f2a6b0; border-radius: 8px;">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL PENGATURAN IDENTITAS TOKO (KHUSUS OWNER) -->
    @if(auth()->user()->role === 'owner')
    <div class="modal fade" id="storeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: #d17885;">🏪 Pengaturan Identitas Toko</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('store_settings.update') }}" method="POST">
                    @csrf
                    <div class="modal-body pb-3">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Nama Toko <span class="text-danger">*</span></label>
                            <input type="text" name="shop_name" class="form-control fw-bold" value="{{ $store->shop_name ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Alamat Lengkap Toko <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="2" required>{{ $store->address ?? '' }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">No. Telepon / WA Layanan Komplain <span class="text-danger">*</span></label>
                            @php
                                $rawPhone = $store->phone ?? '';
                                $cleanDigits = preg_replace('/[^0-9]/', '', $rawPhone);
                                if (str_starts_with($cleanDigits, '62')) $cleanDigits = substr($cleanDigits, 2);
                                elseif (str_starts_with($cleanDigits, '0')) $cleanDigits = substr($cleanDigits, 1);
                            @endphp
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold text-muted">+62</span>
                                <input type="text" 
                                       name="phone" 
                                       id="storePhoneInput" 
                                       class="form-control font-monospace fw-bold" 
                                       value="{{ $cleanDigits }}" 
                                       maxlength="12" 
                                       placeholder="81234567890" 
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12)" 
                                       required>
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Maksimal 12 digit setelah +62 (Hanya angka, contoh: 81299998888).</small>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted small fw-bold">Catatan Kaki Struk (Footer)</label>
                            <textarea name="receipt_footer" class="form-control" rows="2">{{ $store->receipt_footer ?? '' }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm fw-bold px-4 text-white" style="background-color: #f2a6b0; border-radius: 8px;">Simpan Identitas Toko</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @include('components.lockscreen')

    <script>
        function toggleVisibility(inputId, btn) {
            let input = document.getElementById(inputId);
            if (!input) return;
            if (input.type === "password") {
                input.type = "text";
                btn.innerText = "🙈";
            } else {
                input.type = "password";
                btn.innerText = "👁️";
            }
        }
    </script>
</body>
</html>