<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mesin Kasir (POS) - Cantika Aksesoris</title>
    
    <!-- KUNCI KASIR: Anti-Back Browser Lapis Ganda -->
    <script>
        window.history.pushState(null, "", window.location.href);
        window.history.pushState(null, "", window.location.href);
        
        window.onpopstate = function() {
            window.history.pushState(null, "", window.location.href);
        };

        window.allowLeave = false;
        
        window.onbeforeunload = function(e) {
            if (!window.allowLeave) {
                var dialogText = 'Anda yakin ingin keluar dari layar kasir? Transaksi yang belum disimpan bisa hilang.';
                e.returnValue = dialogText;
                return dialogText;
            }
        };
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* KUNCI LAYAR UTAMA MENGGUNAKAN FLEXBOX */
        body { 
            background-color: #f7f4f5; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            display: flex;
            flex-direction: column;
            height: 100vh; /* Tinggi pas 1 layar penuh */
            overflow: hidden; /* Hilangkan scroll bar utama browser */
            margin: 0;
        }
        
        /* HEADER */
        .pos-header { 
            background-color: #ffffff; 
            border-bottom: 2px solid #f2a6b0; 
            padding: 10px 24px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); 
            flex-shrink: 0; /* Mencegah header terkompresi */
        }

        /* AREA KONTEN TENGAH (MENGISI SISA RUANG) */
        .kasir-wrapper {
            flex-grow: 1; 
            padding: 12px 24px;
            overflow: hidden; /* Mengunci area agar tidak tembus ke bawah */
        }

        /* CUSTOM COMPONENTS */
        .card-custom { background: #ffffff; border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .grand-total-box { background: linear-gradient(135deg, #d17885, #f2a6b0); color: white; border-radius: 12px; padding: 12px 15px; text-align: right; }
        .grand-total-text { font-size: 1.8rem; font-weight: 800; margin: 0; line-height: 1.1; }
        
        /* PITA BAWAH (RIBBON) */
        .shortcut-ribbon { 
            background-color: #2c2c2c; 
            color: #ffffff; 
            padding: 8px 24px; 
            font-size: 11px; 
            font-weight: 600; 
            flex-shrink: 0; /* Mencegah pita terkompresi */
        }
        .shortcut-btn { 
            background: #444; 
            padding: 6px 12px; 
            border-radius: 5px; 
            cursor: pointer;
            transition: background 0.2s;
        }
        .shortcut-btn:hover { background: #555; }

        /* SCROLLBAR CUSTOM UNTUK KERANJANG & MODAL */
        .scrollable-area::-webkit-scrollbar { width: 6px; }
        .scrollable-area::-webkit-scrollbar-thumb { background-color: #d17885; border-radius: 10px; }
    </style>
</head>
<body>

    <!-- 1. HEADER POS -->
    <div class="pos-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <h5 class="m-0 fw-bold" style="color: #d17885;">👑 CANTIKA POS</h5>
            <span class="badge bg-light text-dark border font-monospace" id="clock">00:00:00</span>
            <span class="text-muted small">Kasir: <strong>{{ strtoupper(auth()->user()->display_name ?? auth()->user()->name) }}</strong></span>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- PILIH PELANGGAN VIA MODAL -->
            <div class="d-flex align-items-center gap-2">
                <label class="text-muted small fw-bold text-nowrap">Pelanggan:</label>
                
                <!-- Hidden Input penyimpan ID & Nomor WA Pelanggan Terpilih -->
                <input type="hidden" id="customerSelect" value="" data-phone="">

                <div class="input-group input-group-sm" style="width: 310px;">
                    <input type="text" id="selectedCustomerDisplay" class="form-control bg-light fw-bold text-dark" value="Umum (Ecer)" readonly style="border-radius: 8px 0 0 8px; cursor: pointer; font-size: 12px;" data-bs-toggle="modal" data-bs-target="#searchCustomerModal" title="Klik untuk mencari pelanggan">
                    <button class="btn btn-outline-secondary fw-bold px-2" type="button" id="btnResetCustomer" style="display: none;" title="Kembalikan ke Umum (Ecer)">✕</button>
                    <button class="btn btn-primary fw-bold px-2" type="button" data-bs-toggle="modal" data-bs-target="#searchCustomerModal">🔍 Cari</button>
                    <button class="btn btn-outline-primary fw-bold px-2" type="button" data-bs-toggle="modal" data-bs-target="#addMemberModal" style="border-radius: 0 8px 8px 0;">+ Baru</button>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#pettyCashModal" style="border-radius: 8px;">💸 Kas Keluar</button>
                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#closeShiftModal" style="border-radius: 8px;">Tutup Kasir</button>
            </div>
        </div>
    </div>

    <!-- 2. AREA KONTEN (KERANJANG & PEMBAYARAN) -->
    <div class="kasir-wrapper">
        <div class="row g-3 h-100">
            
            <!-- KOLOM KIRI (75%): PENCARIAN & KERANJANG -->
            <div class="col-md-8 d-flex flex-column h-100">
                <!-- A. Kolom Pencarian -->
                <div class="card-custom p-3 mb-2 flex-shrink-0">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light border-end-0">🔍</span>
                        <input type="text" id="barcodeInput" class="form-control border-start-0" placeholder="Scan Barcode langsung di sini..." autofocus autocomplete="off">
                        <button class="btn btn-primary fw-bold px-4" id="btnOpenSearchModal">[F2] Cari Manual</button>
                    </div>
                </div>

                <div id="wholesaleAlert" class="alert alert-warning py-1 px-3 mb-2 flex-shrink-0" style="display: none; border-radius: 8px; font-size: 12px;">
                    💡 <strong>Info Grosir:</strong> Ubah pelanggan ke <strong>Member</strong> di kanan atas untuk mengaktifkan harga grosir!
                </div>

                <!-- B. Tabel Keranjang (Terkunci, hanya konten dalamnya yang bisa discroll) -->
                <div class="card-custom p-0 flex-grow-1 d-flex flex-column" style="overflow: hidden;">
                    <div class="table-responsive flex-grow-1 scrollable-area" style="overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <!-- Header Tabel Sticky (Tetap diam di atas saat discroll) -->
                            <thead class="table-light sticky-top" style="z-index: 10;">
                                <tr>
                                    <th width="5%" class="ps-3">No</th>
                                    <th width="15%">Barcode</th>
                                    <th>Nama Barang</th>
                                    <th width="10%">Qty</th>
                                    <th width="20%">Harga</th>
                                    <th width="10%">Ket</th>
                                    <th width="15%">Subtotal</th>
                                    <th width="5%" class="pe-3"></th>
                                </tr>
                            </thead>
                            <tbody id="cartTableBody">
                                <tr><td colspan="8" class="text-center text-muted py-5">Keranjang masih kosong. Scan barang untuk mulai.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN (25%): PANEL PEMBAYARAN (DIPADATKAN) -->
            <div class="col-md-4 d-flex flex-column h-100" style="padding-right: 5px;">
                
                <!-- Grand Total -->
                <div class="grand-total-box mb-2 shadow-sm flex-shrink-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold" style="font-size: 0.85rem;">GRAND TOTAL</span>
                        <span id="autoPromoBadge" class="badge bg-warning text-dark" style="display: none; font-size: 10px;">Promo Aktif</span>
                    </div>
                    <p class="grand-total-text my-1" id="grandTotalText">Rp 0</p>
                    <button class="btn btn-sm btn-light mt-1 fw-bold w-100" id="btnShowHeldCarts" style="border-radius: 6px; font-size: 11px;">
                        📋 Lihat Antrean Tertahan
                    </button>
                </div>

                <!-- Voucher -->
                <div class="card-custom px-3 py-2 mb-2 flex-shrink-0">
                    <label class="form-label fw-bold small text-muted mb-1" style="font-size: 11px;">🎟️ Kupon Voucher Diskon</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="voucherCode" class="form-control font-monospace text-uppercase" placeholder="Masukkan kode">
                        <button class="btn btn-secondary fw-bold" id="btnApplyVoucher">Pakai</button>
                        <button class="btn btn-danger fw-bold" id="btnRemoveVoucher" style="display: none;">X</button>
                    </div>
                    <small id="voucherMessage" class="fw-bold mt-1 d-block" style="font-size: 10px;"></small>
                </div>

                <!-- Pembayaran -->
                <div class="card-custom px-3 py-2 flex-grow-1 d-flex flex-column justify-content-between">
                    <div>
                        <p class="fw-bold small text-muted mb-2" style="font-size: 11px;">METODE PEMBAYARAN</p>
                        <div class="d-flex gap-2 mb-2">
                            <input type="radio" class="btn-check" name="payment_method" id="payCash" value="cash" checked>
                            <label class="btn btn-outline-success w-50 fw-bold py-1" for="payCash" style="border-radius: 8px; font-size: 13px;">💵 Tunai</label>
                            <input type="radio" class="btn-check" name="payment_method" id="payNonCash" value="non_cash">
                            <label class="btn btn-outline-info w-50 fw-bold py-1" for="payNonCash" style="border-radius: 8px; font-size: 13px;">📱 QRIS</label>
                        </div>

                        <div class="mb-2" id="cashInputGroup">
                            <label class="form-label small text-muted fw-bold mb-1" style="font-size: 11px;">Uang Tunai Diterima (Rp)</label>
                            <input type="text" id="cashReceived" class="form-control fw-bold text-end" style="font-size: 1.25rem;" placeholder="0" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>

                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded mb-2">
                            <span class="text-muted fw-bold small" style="font-size: 11px;">KEMBALIAN:</span>
                            <span class="fs-6 fw-bold text-success" id="changeAmountText">Rp 0</span>
                        </div>

                        <button class="btn w-100 fw-bold py-2 shadow-sm" id="btnCheckout" style="background-color: #f2a6b0; color: white; border-radius: 10px; font-size: 14px;">
                            PROSES BAYAR [F5]
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- 3. PITA SHORTCUT & FOOTER LOGO -->
    <div class="shortcut-ribbon d-flex justify-content-between align-items-center">
        <div class="d-flex gap-3 align-items-center">
            <span class="shortcut-btn" id="btnShortcutF2">[F2] CARI BARANG</span>
            <span class="shortcut-btn" id="btnShortcutF3">[F3] PELANGGAN</span>
            <span class="shortcut-btn text-warning" id="btnHoldShortcut">[F4] TAHAN</span>
            <span class="shortcut-btn" onclick="$('#btnCheckout').click()">[F5] BAYAR</span>
            <span class="shortcut-btn text-danger fw-bold" id="btnVoidTotal">[F6] VOID TOTAL</span>
            <span class="shortcut-btn">[KLIK X] VOID 1 ITEM</span>
            <span class="shortcut-btn text-info" id="btnReprintLast">[F8] STRUK</span>
        </div>
        
        <!-- FOOTER LOGO UT DI KASIR -->
        <div class="d-flex align-items-center rounded px-3 py-1 shadow" style="background-color: rgba(255, 255, 255, 0.95); border: 1px solid rgba(0,0,0,0.1);">
            <img src="{{ asset('images/logo-ut.svg') }}" alt="Logo UT" style="height: 24px; margin-right: 12px;">
            <div class="text-start" style="border-left: 2px solid #ccc; padding-left: 12px; line-height: 1.15;">
                <span class="fw-bold" style="color: #222; font-size: 10px;">© 2026 Cantika POS - Capstone Project</span><br>
                <span class="fw-bold" style="color: #666; font-size: 9px;">Sistem Informasi | Univ. Terbuka</span>
            </div>
        </div>
    </div>

    <!-- MODAL 1: CARI BARANG MANUAL [F2] -->
    <div class="modal fade" id="searchProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-primary">🔍 Cari Barang Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pb-4">
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-light fw-bold text-muted">Kata Kunci</span>
                        <input type="text" id="modalSearchInput" class="form-control form-control-lg" placeholder="Ketik minimal 2 huruf nama barang / merek..." autocomplete="off">
                    </div>
                    <div class="table-responsive scrollable-area" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover align-middle" style="font-size: 13px;">
                            <thead class="table-light sticky-top">
                                <tr><th>Barcode</th><th>Nama Barang</th><th class="text-center">Stok</th><th>Harga Normal</th><th class="text-center">Aksi</th></tr>
                            </thead>
                            <tbody id="modalSearchResults"><tr><td colspan="5" class="text-center text-muted py-4">Ketik nama barang di atas.</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: CARI PELANGGAN / MEMBER [F3] -->
    <div class="modal fade" id="searchCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="modal-title fw-bold text-primary">👥 Cari Data Pelanggan / Member</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#addMemberModal">+ Daftar Member Baru</button>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body pb-4">
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-light fw-bold text-muted">🔍 Cari No. HP / Nama</span>
                        <input type="text" id="modalCustomerSearchInput" class="form-control form-control-lg" placeholder="Ketik nomor telepon (misal: 0812...) atau nama pelanggan..." autocomplete="off">
                    </div>
                    <div class="table-responsive scrollable-area" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Nama Pelanggan</th>
                                    <th>No. Telepon / WA</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" width="18%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="modalCustomerResults">
                                <!-- Baris Default: Pelanggan Umum (Ecer) -->
                                <tr class="customer-row table-light" data-search="umum ecer biasa">
                                    <td class="fw-bold text-dark">Umum (Ecer)</td>
                                    <td class="text-muted font-monospace">-</td>
                                    <td class="text-center"><span class="badge bg-secondary">Umum (Ecer)</span></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-dark fw-bold px-3 btn-pilih-pelanggan" data-id="" data-name="Umum (Ecer)" data-phone="">Pilih</button>
                                    </td>
                                </tr>
                                @foreach($customers as $cust)
                                    @php
                                        $rawPhone = $cust->phone ?? '';
                                        $localPhone = str_starts_with($rawPhone, '+62') ? '0' . substr($rawPhone, 3) : $rawPhone;
                                        $statusLabel = $cust->status == 'registered' ? 'Member ⭐' : 'Umum';
                                        $displayLabel = $cust->name . ($rawPhone ? ' (' . $localPhone . ')' : '') . ' - ' . $statusLabel;
                                    @endphp
                                    <tr class="customer-row" data-search="{{ strtolower($cust->name . ' ' . $rawPhone . ' ' . $localPhone . ' ' . $statusLabel) }}">
                                        <td class="fw-bold text-dark">{{ $cust->name }}</td>
                                        <td class="font-monospace fw-bold text-primary">{{ $rawPhone ?: '-' }}</td>
                                        <td class="text-center">
                                            @if($cust->status == 'registered')
                                                <span class="badge bg-success">Member ⭐ (Grosir)</span>
                                            @else
                                                <span class="badge bg-secondary">Umum (Ecer)</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-primary fw-bold px-3 btn-pilih-pelanggan"
                                                data-id="{{ $cust->id }}"
                                                data-name="{{ $cust->name }}"
                                                data-phone="{{ $rawPhone }}">
                                                Pilih
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr id="noCustomerFoundRow" style="display: none;">
                                    <td colspan="4" class="text-center text-muted py-4">Pelanggan dengan nomor/nama tersebut tidak ditemukan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 3: DAFTAR MEMBER BARU -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold text-primary">⭐ Daftar Member Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pb-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Nama Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" id="newMemberName" class="form-control" placeholder="Contoh: Budi Santoso">
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">No. WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">+62</span>
                            <input type="text" id="newMemberPhone" class="form-control font-monospace" placeholder="812345..." oninput="this.value = this.value.replace(/[^0-9]/g, '')" maxlength="12">
                        </div>
                    </div>
                    <button type="button" id="btnSaveMember" class="btn btn-primary w-100 fw-bold" style="border-radius: 8px;">Simpan & Pilih</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 4: TRANSAKSI BERHASIL -->
    <div class="modal fade" id="checkoutSuccessModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 18px; border: none; box-shadow: 0 15px 40px rgba(0,0,0,0.15);">
                <div class="modal-body text-center p-4">
                    <div class="mb-2" style="font-size: 3.2rem;">✅</div>
                    <h4 class="fw-bold text-success mb-1">Transaksi Berhasil!</h4>
                    <p class="text-muted small mb-3">Struk sedang dicetak ke printer.</p>
                    <div class="p-3 rounded mb-3" style="background-color: #f8f9fa; border: 1px dashed #ccc;">
                        <small class="text-muted fw-bold d-block">UANG KEMBALIAN PELANGGAN</small>
                        <h2 class="fw-bold text-dark m-0 mt-1" id="modalChangeAmount">Rp 0</h2>
                    </div>
                    <div class="p-3 rounded mb-3 text-start" style="background-color: #eefaf2; border: 1px solid #25D366;">
                        <label class="form-label fw-bold small mb-1" style="color: #128C7E;">💬 Kirim Struk Digital WA</label>
                        <div class="input-group">
                            <input type="text" id="modalWaPhone" class="form-control" placeholder="0812..." oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            <button type="button" class="btn fw-bold text-white px-3" id="btnModalSendWa" style="background-color: #25D366;">Kirim WA</button>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn w-50 py-2 fw-bold text-white shadow-sm" id="btnNextTransaction" style="background-color: #f2a6b0; border-radius: 10px;">➡️ Lanjut [Enter]</button>
                        <button type="button" class="btn btn-outline-dark w-50 py-2 fw-bold" id="btnModalPrintAgain" style="border-radius: 10px;">🖨️ Cetak Lagi</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal PIN Void, Hold Cart, Petty Cash, Close Shift -->
    <div class="modal fade" id="pinAuthModal" tabindex="-1" data-bs-backdrop="static"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content" style="border-radius: 15px;"><div class="modal-header border-0 pb-0 text-center w-100 d-block"><h6 class="modal-title fw-bold text-danger">🛡️ Otorisasi Supervisor</h6></div><div class="modal-body text-center pb-4 pt-2"><input type="password" id="supervisorPin" class="form-control form-control-lg text-center fw-bold mb-3" placeholder="******" maxlength="6" autocomplete="off" oninput="this.value = this.value.replace(/[^0-9]/g, '')"><div class="d-flex gap-2"><button type="button" class="btn btn-outline-secondary w-50" data-bs-dismiss="modal">Batal</button><button type="button" id="btnVerifyPin" class="btn btn-danger w-50">Hapus Item</button></div></div></div></div></div>
    <div class="modal fade" id="heldCartsModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content" style="border-radius: 15px;"><div class="modal-header border-0"><h5 class="modal-title fw-bold text-dark">📋 Daftar Antrean Tertahan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body pb-4"><div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Waktu</th><th>Pelanggan</th><th>Total Belanja</th><th>Aksi</th></tr></thead><tbody id="heldCartsTableBody"></tbody></table></div></div></div></div></div>
    <div class="modal fade" id="pettyCashModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content" style="border-radius: 15px;"><div class="modal-header border-0"><h5 class="modal-title fw-bold">💸 Kas Keluar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body pb-4"><input type="text" id="pettyAmount" class="form-control mb-3" placeholder="Nominal (Rp)" oninput="this.value = this.value.replace(/[^0-9]/g, '')"><input type="text" id="pettyDesc" class="form-control mb-3" placeholder="Keterangan (misal beli galon)"><button type="button" id="btnSavePettyCash" class="btn w-100 fw-bold text-white" style="background-color: #f2a6b0; border-radius: 8px;">Simpan Pengeluaran</button></div></div></div></div>
    <!-- Modal Tutup Kasir Baru & Rincian -->
    <div class="modal fade" id="closeShiftModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger">Tutup Shift Kasir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('shift.close') }}" method="POST" onsubmit="window.allowLeave = true; localStorage.removeItem('app_locked')">
                    @csrf 
                    <div class="modal-body pb-4">
                        <!-- Loading State -->
                        <div id="shiftSummaryLoading" class="text-center py-4">
                            <div class="spinner-border text-danger" role="status"></div>
                            <p class="small mt-2 text-muted">Menghitung rekap mutasi laci...</p>
                        </div>
                        
                        <!-- Rincian Konten -->
                        <div id="shiftSummaryContent" style="display: none;">
                            <div class="p-3 bg-light rounded border mb-3">
                                <table class="table table-sm table-borderless mb-0" style="font-size: 13px;">
                                    <tr>
                                        <td class="text-muted">Modal Awal Laci</td>
                                        <td class="text-end fw-bold" id="sumStart">Rp 0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Penjualan Tunai (Uang Masuk)</td>
                                        <td class="text-end fw-bold text-success" id="sumSales">+ Rp 0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Refund/Retur Penjualan</td>
                                        <td class="text-end fw-bold text-danger" id="sumRefund">- Rp 0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">
                                            Kas Keluar (Petty Cash)<br>
                                            <ul id="sumPettyList" class="mb-0 ps-3 mt-1" style="font-size: 11px;"></ul>
                                        </td>
                                        <td class="text-end fw-bold text-danger" id="sumPetty">- Rp 0</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="fw-bold pt-2">TOTAL UANG SISTEM</td>
                                        <td class="text-end fw-bold text-primary fs-5 pt-2" id="sumSystem">Rp 0</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="alert alert-warning py-2 px-3 text-center mb-3" style="font-size: 12px; border-radius: 8px;">
                                💡 Hitung uang fisik di laci Anda. Jika cocok dengan Total Uang Sistem, berarti <strong>Klop/Balance</strong>.
                            </div>
                            
                            <label class="form-label small fw-bold text-muted">Masukkan Uang Fisik Aktual (Rp)</label>
                            <input type="text" name="end_cash_actual" class="form-control form-control-lg text-center fw-bold mb-3" placeholder="Contoh: 545000" required autocomplete="off" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            
                            <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold" style="border-radius: 10px; font-size: 15px;">
                                🔒 AKHIRI SHIFT & LOGOUT
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIKA KASIR -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // DEKLARASI GLOBAL
        window.grandTotalNum = 0; 
        window.discountAmountNum = 0; 
        window.itemToDelete = null; 
        window.itemToDeleteName = '';
        window.lastTransactionId = localStorage.getItem('cantika_pos_last_trx_id') || null;

        window.formatRupiah = function(angka) { 
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka); 
        };

        // FUNGSI SET PELANGGAN TERPILIH
        window.setCustomerSelection = function(id, name, phone) {
            $('#customerSelect').val(id || '').attr('data-phone', phone || '');
            $('#selectedCustomerDisplay').val(name || 'Umum (Ecer)');
            if (id) {
                $('#btnResetCustomer').show();
            } else {
                $('#btnResetCustomer').hide();
            }
            $('#customerSelect').trigger('change');
        };

        // CETAK IFRAME DINAMIS
        window.printReceiptSilent = function(trxId, isReprint = false) { 
            if(!trxId) return; 
            $('#receiptPrintFrame').remove();

            let url = '/pos/receipt/' + trxId + '?t=' + new Date().getTime();
            if (isReprint) {
                url += '&reprint=1';
            }

            let iframe = $('<iframe id="receiptPrintFrame" name="receiptPrintFrame" style="position:fixed; right:0; bottom:0; width:0; height:0; border:0;"></iframe>');
            iframe.attr('src', url);
            $('body').append(iframe);
        };

        window.calculateChange = function() {
            let finalTotalToPay = Math.max(0, window.grandTotalNum - window.discountAmountNum);
            if (!$('#payCash').is(':checked')) { 
                $('#changeAmountText').text('Rp 0 (Non-Tunai)').removeClass('text-danger').addClass('text-success'); 
                return 0; 
            }
            let change = (parseInt($('#cashReceived').val()) || 0) - finalTotalToPay;
            if(change < 0) {
                $('#changeAmountText').text('Rp 0 (Kurang ' + window.formatRupiah(Math.abs(change)) + ')').removeClass('text-success').addClass('text-danger');
            } else {
                $('#changeAmountText').text(window.formatRupiah(change)).removeClass('text-danger').addClass('text-success');
            }
            return change;
        };

        window.renderCart = function(data) {
            let tbody = ''; let no = 1; window.grandTotalNum = data.grand_total;
            if (data.auto_period_discount > 0 && $('#voucherCode').val() === '') { 
                window.discountAmountNum = data.auto_period_discount; 
                $('#autoPromoBadge').text(data.auto_period_name + ' (-' + window.formatRupiah(data.auto_period_discount) + ')').show(); 
            } else if ($('#voucherCode').val() === '') { 
                window.discountAmountNum = 0; 
                $('#autoPromoBadge').hide(); 
            }

            if(data.show_wholesale_alert) $('#wholesaleAlert').slideDown(); else $('#wholesaleAlert').slideUp();

            if(data.cart.length === 0) {
                tbody = '<tr><td colspan="8" class="text-center text-muted py-5">Keranjang masih kosong. Scan barang untuk mulai.</td></tr>';
            } else {
                data.cart.forEach(item => {
                    let badgeKet = item.is_promo_item ? `<span class="badge bg-danger">${item.promo_note}</span>` : (item.is_wholesale ? '<span class="badge bg-success">GROSIR</span>' : '-');
                    
                    // Input Nomor Tujuan (Semua varian digital)
                    let digitalInput = '';
                    if (['digital_prabayar', 'digital_pascabayar', 'digital'].includes(item.product_type)) {
                        digitalInput = `
                            <div class="mt-1">
                                <div class="input-group input-group-sm" style="max-width: 250px;">
                                    <span class="input-group-text bg-warning text-dark fw-bold py-0" style="font-size: 10px;">No. Tujuan</span>
                                    <input type="text" class="form-control font-monospace py-0" 
                                           style="font-size: 11px; font-weight: bold;" 
                                           placeholder="08xx / No. Meter PLN" 
                                           value="${item.target_number || ''}" 
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                                           onchange="window.saveTargetNumber(${item.id}, this.value)">
                                </div>
                            </div>
                        `;
                    }

                    // TAMPILAN NAMA & INPUT PASCABAYAR
                    let pascabayarExtra = '';
                    let priceColumn = window.formatRupiah(item.price);

                    if (item.product_type === 'digital_pascabayar') {
                        let billVal = item.bill_amount > 0 ? new Intl.NumberFormat('id-ID').format(item.bill_amount) : '';
                        let adminVal = new Intl.NumberFormat('id-ID').format(item.admin_fee || 3000);

                        pascabayarExtra = `
                            <div class="mt-1 p-2 rounded bg-light border" style="max-width: 280px;">
                                <div class="input-group input-group-sm mb-1">
                                    <span class="input-group-text bg-white text-muted fw-bold" style="font-size: 11px;">Tagihan</span>
                                    <input type="text" id="bill_${item.id}" class="form-control form-control-sm text-end fw-bold format-rupiah-pos" 
                                           placeholder="Masukkan Total" value="${billVal}" 
                                           onchange="window.savePascabayar(${item.id})">
                                </div>
                                <div class="d-flex justify-content-between align-items-center" style="font-size: 11px;">
                                    <span class="text-muted">Biaya admin:</span>
                                    <div class="input-group input-group-sm" style="width: 130px;">
                                        <span class="input-group-text py-0" style="font-size: 10px;">Rp</span>
                                        <input type="text" id="admin_${item.id}" class="form-control form-control-sm text-end fw-bold py-0 format-rupiah-pos" 
                                               value="${adminVal}" onchange="window.savePascabayar(${item.id})">
                                    </div>
                                </div>
                            </div>
                        `;
                        priceColumn = `<span class="fw-bold text-success">${window.formatRupiah(item.price)}</span>`;
                    } else if (item.product_type === 'service') {
                        let cleanPrice = window.formatRupiah(item.price).replace('Rp', '').trim();
                        priceColumn = `<input type="text" class="form-control form-control-sm bg-light text-dark fw-bold text-end format-rupiah-pos border-warning" style="width: 100px; display:inline-block;" value="${cleanPrice}" onchange="window.updateCartPrice(${item.id}, this.value)" placeholder="Ketik Harga">`;
                    }

                    tbody += `<tr>
                        <td class="ps-3">${no++}</td>
                        <td><span class="badge bg-secondary font-monospace">${item.barcode || '-'}</span></td>
                        <td>
                            <div class="fw-bold">${item.name}</div>
                            ${digitalInput}
                            ${pascabayarExtra}
                        </td>
                        <td><input type="number" class="form-control form-control-sm bg-dark text-white text-center fw-bold" style="width: 55px;" value="${item.qty}" min="1" ${item.product_type === 'digital_pascabayar' ? 'readonly' : ''} onchange="window.updateCartQty(${item.id}, this)"></td>
                        <td>${priceColumn}</td>
                        <td>${badgeKet}</td>
                        <td class="fw-bold">${window.formatRupiah(item.subtotal)}</td>
                        <td class="pe-3"><button class="btn btn-sm btn-danger px-2 py-0" onclick="window.promptVoidPin(${item.id}, '${item.name.replace(/'/g, "\\'")}')">x</button></td>
                    </tr>`;
                });
            }
            $('#cartTableBody').html(tbody);
            $('#grandTotalText').text(window.formatRupiah(window.grandTotalNum));
            window.calculateChange(); 
        };

        window.updateCartQty = function(id, obj) { 
            let qty = $(obj).val(); 
            if(qty < 1) { $(obj).val(1); qty = 1; } 
            $.post("{{ route('pos.update_qty') }}", { product_id: id, qty: qty, customer_id: $('#customerSelect').val() }, res => window.renderCart(res)); 
        };

        window.saveTargetNumber = function(id, val) { 
            $.post("{{ route('pos.update_target_number') }}", { product_id: id, target_number: val }); 
        };

        window.updateCartPrice = function(id, val) { 
            $.post("{{ route('pos.update_price') }}", { product_id: id, price: val, customer_id: $('#customerSelect').val() }, res => window.renderCart(res)); 
        };

        window.savePascabayar = function(productId) {
            let bill = $('#bill_' + productId).val() || '0';
            let admin = $('#admin_' + productId).val() || '3000';
            $.post("{{ route('pos.update_pascabayar') }}", {
                product_id: productId,
                bill_amount: bill,
                admin_fee: admin,
                customer_id: $('#customerSelect').val()
            }, res => window.renderCart(res));
        };

        window.promptVoidPin = function(id, name = '') { 
            window.itemToDelete = id; 
            window.itemToDeleteName = name;
            $('#supervisorPin').val(''); 
            $('#pinAuthModal').modal('show'); 
            setTimeout(() => $('#supervisorPin').focus(), 400); 
        };

        window.removeFromCart = function(id) { 
            $.post("{{ route('pos.remove_from_cart') }}", { product_id: id, customer_id: $('#customerSelect').val() }, res => window.renderCart(res)); 
        };

        window.addDirectToCart = function(id) { 
            $.post("{{ route('pos.add_to_cart') }}", { product_id: id, qty: 1, customer_id: $('#customerSelect').val() }).done(r => window.renderCart(r)).fail(err => alert(err.responseJSON?.error || 'Gagal tambah barang')); 
        };

        $(document).ready(function() {
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            setInterval(() => $('#clock').text(new Date().toLocaleTimeString('id-ID')), 1000);

            // =========================================================================
            // LOGIKA MODAL CARI PELANGGAN [F3]
            // =========================================================================
            $('#modalCustomerSearchInput').on('keyup input', function() {
                let kw = $(this).val().toLowerCase().trim();
                let visibleCount = 0;

                $('#modalCustomerResults tr.customer-row').each(function() {
                    let text = $(this).attr('data-search') || '';
                    if (kw === '' || text.indexOf(kw) > -1) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });

                if (visibleCount === 0) {
                    $('#noCustomerFoundRow').show();
                } else {
                    $('#noCustomerFoundRow').hide();
                }
            });

            // Saat tombol "Pilih" pada tabel modal pelanggan diklik
            $(document).on('click', '.btn-pilih-pelanggan', function() {
                let id = $(this).data('id');
                let name = $(this).data('name');
                let phone = $(this).data('phone');

                window.setCustomerSelection(id, name, phone);
                $('#searchCustomerModal').modal('hide');
            });

            // Tombol silang (✕) untuk reset cepat kembali ke Umum (Ecer)
            $('#btnResetCustomer').click(function() {
                window.setCustomerSelection('', 'Umum (Ecer)', '');
                $('#barcodeInput').focus();
            });

            $('#searchCustomerModal').on('shown.bs.modal', function () {
                $('#modalCustomerSearchInput').val('').trigger('input').focus();
            });
            $('#searchCustomerModal').on('hidden.bs.modal', function () {
                $('#barcodeInput').focus();
            });
            $('#btnShortcutF3').click(() => $('#searchCustomerModal').modal('show'));

            // =========================================================================
            // LOGIKA PENCARIAN BARANG [F2] & BARCODE SCANNER
            // =========================================================================
            $('#barcodeInput').keypress(function(e) {
                if(e.which == 13) {
                    e.preventDefault(); let keyword = $(this).val().trim(); if(keyword === '') return;
                    let inputEl = $(this); inputEl.prop('disabled', true);
                    $.get("{{ route('pos.search') }}", { keyword: keyword }).done(function(res) {
                         inputEl.prop('disabled', false).val('').focus();
                         if(res.length === 1) window.addDirectToCart(res[0].id);
                         else if(res.length > 1) { $('#searchProductModal').modal('show'); $('#modalSearchInput').val(keyword); window.executeSearch(keyword); } 
                         else alert('Barang tidak ditemukan!');
                    }).fail(() => inputEl.prop('disabled', false).val('').focus());
                }
            });

            let searchTimeout = null;
            $('#modalSearchInput').on('keyup', function() {
                let kw = $(this).val().trim(); clearTimeout(searchTimeout);
                if (kw.length < 2) { $('#modalSearchResults').html('<tr><td colspan="5" class="text-center text-muted py-4">Ketik minimal 2 huruf...</td></tr>'); return; }
                searchTimeout = setTimeout(() => window.executeSearch(kw), 300);
            });

            window.executeSearch = function(keyword) {
                $('#modalSearchResults').html('<tr><td colspan="5" class="text-center text-muted py-4">Mencari...</td></tr>');
                $.get("{{ route('pos.search') }}", { keyword: keyword }).done(function(res) {
                     let tbody = '';
                     if(res.length === 0) tbody = '<tr><td colspan="5" class="text-center text-muted py-4">Barang tidak ditemukan.</td></tr>';
                     else {
                         res.forEach(item => {
                             let stockBadge = ''; let btnDisabled = '';
                             if (item.product_type === 'service') { stockBadge = '<span class="badge bg-info text-dark">JASA</span>'; }
                             else if (['digital_prabayar', 'digital'].includes(item.product_type)) { stockBadge = '<span class="badge bg-primary">PRABAYAR</span>'; }
                             else if (item.product_type === 'digital_pascabayar') { stockBadge = '<span class="badge bg-warning text-dark">PASCABAYAR</span>'; }
                             else {
                                 stockBadge = item.stock > 0 ? `<span class="badge bg-success">${item.stock}</span>` : `<span class="badge bg-danger">HABIS</span>`;
                                 btnDisabled = item.stock <= 0 ? 'disabled' : '';
                             }

                             tbody += `<tr>
                                 <td class="font-monospace">${item.barcode || '-'}</td>
                                 <td class="fw-bold">${item.name}</td>
                                 <td class="text-center">${stockBadge}</td>
                                 <td class="fw-bold text-dark">${window.formatRupiah(item.retail_price)}</td>
                                 <td class="text-center"><button class="btn btn-sm btn-primary fw-bold px-3 btn-pilih-barang" data-id="${item.id}" ${btnDisabled}>Pilih</button></td>
                             </tr>`;
                         });
                     }
                     $('#modalSearchResults').html(tbody);
                 });
            };

            $(document).on('click', '.btn-pilih-barang', function() { window.addDirectToCart($(this).data('id')); $('#searchProductModal').modal('hide'); });
            $('#searchProductModal').on('shown.bs.modal', function () { $('#modalSearchInput').val('').focus(); });
            $('#searchProductModal').on('hidden.bs.modal', function () { $('#barcodeInput').focus(); });
            $('#btnOpenSearchModal, #btnShortcutF2').click(() => $('#searchProductModal').modal('show'));

            // =========================================================================
            // SIMPAN MEMBER BARU (+ BARU) & TAMBAHKAN KE TABEL MODAL
            // =========================================================================
            $('#btnSaveMember').click(function() {
                let name = $('#newMemberName').val().trim(), phone = $('#newMemberPhone').val().trim();
                if (!name) return alert('Nama pelanggan wajib diisi!');
                let btn = $(this); btn.prop('disabled', true).text('Menyimpan...');

                $.post("{{ route('pos.store_customer') }}", { name: name, phone: phone }).done(function(res) {
                     btn.prop('disabled', false).text('Simpan & Pilih');
                     
                     let rawPhone = res.customer.phone || '';
                     let localPhone = rawPhone.startsWith('+62') ? '0' + rawPhone.substring(3) : rawPhone;
                     let displayLabel = res.customer.name + (rawPhone ? ' (' + localPhone + ')' : '') + ' - Member ⭐';
                     let searchStr = (res.customer.name + ' ' + rawPhone + ' ' + localPhone + ' member ⭐').toLowerCase();

                     // Tambahkan baris baru ke dalam tabel Modal Pelanggan agar langsung terdaftar
                     let newRow = `
                        <tr class="customer-row" data-search="${searchStr}">
                            <td class="fw-bold text-dark">${res.customer.name}</td>
                            <td class="font-monospace fw-bold text-primary">${rawPhone || '-'}</td>
                            <td class="text-center"><span class="badge bg-success">Member ⭐ (Grosir)</span></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-primary fw-bold px-3 btn-pilih-pelanggan"
                                    data-id="${res.customer.id}"
                                    data-name="${res.customer.name}"
                                    data-phone="${rawPhone}">
                                    Pilih
                                </button>
                            </td>
                        </tr>
                     `;
                     $('#noCustomerFoundRow').before(newRow);

                     // Langsung pilih member baru tersebut
                     window.setCustomerSelection(res.customer.id, res.customer.name, rawPhone);

                     $('#addMemberModal').modal('hide'); 
                     $('#newMemberName').val(''); 
                     $('#newMemberPhone').val('');
                     $('#barcodeInput').focus();
                 }).fail(err => { btn.prop('disabled', false).text('Simpan & Pilih'); alert(err.responseJSON?.error || 'Gagal menyimpan!'); });
            });

            $('input[name="payment_method"]').change(function() { if ($('#payCash').is(':checked')) $('#cashInputGroup').slideDown('fast'); else { $('#cashInputGroup').slideUp('fast'); $('#cashReceived').val(''); } window.calculateChange(); });
            $('#customerSelect').change(function() { $.post("{{ route('pos.calculate') }}", { customer_id: $(this).val() }, res => window.renderCart(res)); });
            $('#cashReceived').on('input', function() { window.calculateChange(); });
            $(document).on('input', '.format-rupiah-pos', function() { let val = $(this).val().replace(/[^0-9]/g, ''); $(this).val(val ? new Intl.NumberFormat('id-ID').format(val) : ''); });

            // TOMBOL VERIFIKASI PIN SPV
            $('#btnVerifyPin').click(function() {
                let pin = $('#supervisorPin').val(); if(!pin) return;
                
                $.post("{{ route('pos.verify_pin') }}", { 
                    pin: pin,
                    item_name: window.itemToDeleteName 
                }).done(() => { 
                    $('#pinAuthModal').modal('hide'); 
                    
                    if (window.itemToDelete === 'ALL') {
                        $.post("{{ route('pos.clear_cart') }}", { customer_id: $('#customerSelect').val() })
                         .done(res => { window.renderCart(res); window.itemToDelete = null; })
                         .fail(err => alert('Gagal mengosongkan keranjang!'));
                    } else {
                        window.removeFromCart(window.itemToDelete); 
                        window.itemToDelete = null; 
                    }
                }).fail(err => alert(err.responseJSON?.error || 'Otorisasi gagal! PIN Salah.'));
            });

            // TOMBOL F6: VOID TOTAL
            $('#btnVoidTotal').click(function() {
                if($('#cartTableBody').text().includes('Keranjang masih kosong')) return alert('Keranjang sudah kosong!');
                
                if(confirm('PERINGATAN: Tindakan ini akan membatalkan SEMUA barang di keranjang! Lanjutkan?')) {
                    window.itemToDelete = 'ALL'; 
                    window.itemToDeleteName = 'Semua Barang di Keranjang (Void Total)';
                    $('#supervisorPin').val(''); 
                    $('#pinAuthModal').modal('show'); 
                    setTimeout(() => $('#supervisorPin').focus(), 400); 
                }
            });

            $('#btnApplyVoucher').click(function() {
                let code = $('#voucherCode').val().trim(); if(!code) return;
                $.post("{{ route('pos.apply_discount') }}", { code: code, grand_total: window.grandTotalNum }).done(res => {
                    window.discountAmountNum = res.discount_amount;
                    $('#voucherMessage').text(res.success + ' (-' + window.formatRupiah(window.discountAmountNum) + ')').removeClass('text-danger').addClass('text-success');
                    $('#btnApplyVoucher').hide(); $('#btnRemoveVoucher').show(); $('#voucherCode').prop('readonly', true); $('#autoPromoBadge').hide();
                    $('#grandTotalText').text(window.formatRupiah(Math.max(0, window.grandTotalNum - window.discountAmountNum))); window.calculateChange();
                }).fail(err => $('#voucherMessage').text(err.responseJSON?.error || 'Gagal menerapkan voucher!').removeClass('text-success').addClass('text-danger'));
            });
            $('#btnRemoveVoucher').click(function() { $.post("{{ route('pos.remove_discount') }}").done(() => { window.discountAmountNum = 0; $('#voucherMessage').text(''); $('#btnApplyVoucher').show(); $('#btnRemoveVoucher').hide(); $('#voucherCode').val('').prop('readonly', false); $('#customerSelect').trigger('change'); }); });

            // PROSES BAYAR
            $('#btnCheckout').click(function() {
                if(window.grandTotalNum <= 0) return alert('Keranjang kosong!');
                let payMethod = $('#payCash').is(':checked') ? 'cash' : 'non_cash';
                let finalTotalToPay = Math.max(0, window.grandTotalNum - window.discountAmountNum);
                let cashVal = parseInt($('#cashReceived').val()) || 0;
                if (payMethod === 'cash' && cashVal < finalTotalToPay) { alert('Uang tunai kurang dari tagihan!'); $('#cashReceived').focus(); return; }
                
                let hasEmptyBill = false;
                $('input[id^="bill_"]').each(function() {
                    let val = parseInt($(this).val().replace(/[^0-9]/g, '')) || 0;
                    if (val <= 0) {
                        hasEmptyBill = true;
                        $(this).focus();
                        return false;
                    }
                });

                if (hasEmptyBill) {
                    return alert('Ada transaksi pascabayar yang nominal tagihannya belum diisi!');
                }
                
                let btn = $(this); btn.prop('disabled', true).text('MEMPROSES...');
                $.post("{{ route('pos.checkout') }}", { customer_id: $('#customerSelect').val(), payment_method: payMethod, grand_total: window.grandTotalNum, cash_received: cashVal }).done(res => {
                    window.lastTransactionId = res.transaction_id;
                    localStorage.setItem('cantika_pos_last_trx_id', res.transaction_id);
                    $('#modalChangeAmount').text(window.formatRupiah(payMethod === 'cash' ? (cashVal - finalTotalToPay) : 0));
                    $('#modalWaPhone').val($('#customerSelect').attr('data-phone') || '');
                    
                    let successModal = new bootstrap.Modal(document.getElementById('checkoutSuccessModal'));
                    successModal.show();
                    
                    window.printReceiptSilent(res.transaction_id, false);
                    setTimeout(() => { $('#btnNextTransaction').focus(); }, 400);
                }).fail(err => { btn.prop('disabled', false).text('PROSES BAYAR [F5]'); alert(err.responseJSON?.error || 'Gagal memproses!'); });
            });

            $('#btnModalPrintAgain, #btnReprintLast').click(function() {
                if (!window.lastTransactionId) {
                    return alert('Belum ada riwayat transaksi yang diselesaikan pada sesi ini untuk dicetak ulang!');
                }
                window.printReceiptSilent(window.lastTransactionId, true);
            });
            $('#btnModalSendWa').click(function() {
                let phone = $('#modalWaPhone').val().trim(); if (!phone) return;
                let btn = $(this); btn.prop('disabled', true).text('...');
                $.post(`/pos/receipt/${window.lastTransactionId}/wa`, { phone: phone }).done(res => { btn.prop('disabled', false).text('Kirim WA'); if (res.url) window.open(res.url, '_blank'); }).fail(() => { btn.prop('disabled', false).text('Kirim WA'); alert('Gagal memproses WA!'); });
            });

            // Lanjut: Tutup modal & Refresh Halaman untuk Transaksi Bersih
            $('#btnNextTransaction').click(function() { 
                window.allowLeave = true; 
                $('#receiptPrintFrame').remove(); 
                window.location.replace("{{ route('pos.index') }}"); 
            });

            // KEYBOARD SHORTCUTS
            $(document).keydown(function(e) {
                if ($('#checkoutSuccessModal').hasClass('show') && e.key === 'Enter') { 
                    if (!$(e.target).is('#modalWaPhone')) { 
                        e.preventDefault(); e.stopPropagation(); $('#btnNextTransaction').click(); 
                    } 
                    return; 
                }
                if(e.key === "F2" || e.keyCode === 113) { e.preventDefault(); $('#searchProductModal').modal('show'); }
                if(e.key === "F3" || e.keyCode === 114) { e.preventDefault(); $('#searchCustomerModal').modal('show'); }
                if(e.key === "F4") { e.preventDefault(); $('#btnHoldShortcut').click(); }
                if(e.key === "F5") { e.preventDefault(); $('#btnCheckout').click(); }
                if(e.key === "F6") { e.preventDefault(); $('#btnVoidTotal').click(); }
                if(e.key === "F8") { 
                    e.preventDefault(); 
                    if (!window.lastTransactionId) {
                        alert('Belum ada riwayat transaksi untuk dicetak ulang!');
                    } else {
                        window.printReceiptSilent(window.lastTransactionId, true); 
                    }
                }
            });

            $('#btnHoldShortcut').click(function() {
                if($('#cartTableBody').text().includes('Keranjang masih kosong')) return alert('Keranjang kosong!');
                if(confirm('Tahan antrean ini?')) {
                    $.post("{{ route('pos.hold_cart') }}", { customer_id: $('#customerSelect').val(), grand_total: window.grandTotalNum })
                    .done(res => { alert(res.success); window.allowLeave = true; window.location.replace("{{ route('pos.index') }}"); })
                    .fail(err => alert(err.responseJSON?.error || 'Gagal!'));
                }
            });

            $('#btnShowHeldCarts').click(function(e) {
                e.preventDefault();
                $.get("{{ route('pos.get_held_carts') }}").done(res => {
                    let tbody = '';
                    if(res.length === 0) tbody = '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada antrean tertahan.</td></tr>';
                    else {
                        res.forEach(cart => {
                            tbody += `<tr><td class="fw-bold">${cart.time}</td><td>${cart.customer_name}</td><td><span class="fw-bold">${window.formatRupiah(cart.grand_total)}</span></td><td><button class="btn btn-sm btn-success fw-bold px-3 btn-recall" data-id="${cart.id}" data-cust="${cart.customer_id || ''}">Panggil</button></td></tr>`;
                        });
                    }
                    $('#heldCartsTableBody').html(tbody); $('#heldCartsModal').modal('show');
                });
            });

            $(document).on('click', '.btn-recall', function(e) { 
                e.preventDefault(); 
                let custId = $(this).data('cust');
                $.post("{{ route('pos.recall_cart') }}", { hold_id: $(this).data('id') }).done(res => { 
                    $('#heldCartsModal').modal('hide'); 
                    if (custId) {
                        let btnCust = $(`.btn-pilih-pelanggan[data-id="${custId}"]`);
                        if (btnCust.length) {
                            window.setCustomerSelection(custId, btnCust.data('name'), btnCust.data('phone'));
                        } else {
                            window.setCustomerSelection(custId, 'Pelanggan Member', '');
                        }
                    } else {
                        window.setCustomerSelection('', 'Umum (Ecer)', '');
                    }
                    window.renderCart(res); 
                }); 
            });

            $('#closeShiftModal').on('show.bs.modal', function () {
                $('#shiftSummaryContent').hide();
                $('#shiftSummaryLoading').show();
                
                $.get("{{ route('shift.summary') }}", function(res) {
                    $('#sumStart').text(window.formatRupiah(res.start_cash));
                    $('#sumSales').text('+ ' + window.formatRupiah(res.cash_sales));
                    $('#sumRefund').text('- ' + window.formatRupiah(res.refunds));
                    $('#sumPetty').text('- ' + window.formatRupiah(res.petty_total));
                    $('#sumSystem').text(window.formatRupiah(res.system_cash));
                    
                    let pList = '';
                    if (res.petty_details.length > 0) {
                        res.petty_details.forEach(p => {
                            pList += `<li>${p.description}: Rp ${new Intl.NumberFormat('id-ID').format(p.amount)}</li>`;
                        });
                    } else {
                        pList = '<li class="text-muted">Tidak ada kas keluar</li>';
                    }
                    $('#sumPettyList').html(pList);

                    $('#shiftSummaryLoading').hide();
                    $('#shiftSummaryContent').slideDown();
                }).fail(function() {
                    alert('Gagal mengambil data rekap shift dari server!');
                    $('#closeShiftModal').modal('hide');
                });
            });

            $('#btnSavePettyCash').click(function(e) {
                e.preventDefault(); let amount = $('#pettyAmount').val(), desc = $('#pettyDesc').val();
                if(!amount || !desc) return alert('Isi nominal & ket!');
                let btn = $(this), ori = btn.text(); btn.text('Simpan...').prop('disabled', true);
                $.post("{{ route('shift.petty_cash') }}", { amount: amount, description: desc }).done(res => { alert(res.success); $('#pettyAmount').val(''); $('#pettyDesc').val(''); $('#pettyCashModal').modal('hide'); btn.text(ori).prop('disabled', false); }).fail(err => { alert(err.responseJSON?.message || 'Gagal!'); btn.text(ori).prop('disabled', false); });
            });

            $('#customerSelect').trigger('change');
        });
    </script>
    @include('components.lockscreen')
</body>
</html>