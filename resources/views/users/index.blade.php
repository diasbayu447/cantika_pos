@extends('layouts.app')

@section('title', 'Manajemen Staf & Keamanan')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">👥 Manajemen Staf & Keamanan</h3>
            <p class="text-muted small m-0">Kelola akun kasir, admin, supervisor, status aktif, serta PIN otorisasi unik.</p>
        </div>
        <button class="btn fw-bold text-white px-4 py-2 shadow-sm" style="background-color: #d17885; border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#addStaffModal">
            + Tambah Staf Baru
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show fw-bold" style="border-radius: 10px;">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show fw-bold" style="border-radius: 10px;">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm" style="border-radius: 15px; min-height: 65vh;">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="ps-3">No</th>
                            <th width="22%">Nama Lengkap</th>
                            <th width="15%">Username</th>
                            <th width="13%">Role / Peran</th>
                            <th width="12%">Status Akun</th>
                            <th width="13%">Sesi Login</th>
                            <th width="20%" class="text-center">Aksi Kontrol</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $idx => $u)
                        <tr>
                            <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                            <td class="fw-bold text-dark">{{ $u->name }}</td>
                            <td class="font-monospace text-muted">{{ $u->username }}</td>
                            <td>
                                @if($u->role === 'owner') <span class="badge bg-danger">Owner</span>
                                @elseif($u->role === 'supervisor') <span class="badge bg-primary">Supervisor</span>
                                @elseif($u->role === 'admin') <span class="badge bg-info text-dark">Admin Gudang</span>
                                @else <span class="badge bg-success">Kasir</span>
                                @endif
                            </td>
                            <td>
                                @if($u->is_active ?? true)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                @if(in_array($u->id, $activeSessions))
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">🟢 Online</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">⚪ Offline</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                <div class="d-inline-flex gap-1 align-items-center justify-content-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-2 py-1" onclick="editStaff({{ json_encode($u) }})" data-bs-toggle="modal" data-bs-target="#editStaffModal">
                                        ✏️ Edit
                                    </button>

                                    @php
                                        $canKick = false;
                                        if (in_array($u->id, $activeSessions) && $u->id !== auth()->id()) {
                                            if (auth()->user()->role === 'owner' && $u->role !== 'owner') $canKick = true;
                                            elseif (auth()->user()->role === 'supervisor' && in_array($u->role, ['cashier', 'admin'])) $canKick = true;
                                        }
                                    @endphp

                                    @if($canKick)
                                    <form action="{{ route('users.kick', $u->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning fw-bold px-2 py-1 text-dark" onclick="return confirm('Paksa keluar {{ $u->name }} dari sesi aktif?');">
                                            🛑 Kick
                                        </button>
                                    </form>
                                    @endif

                                    @if($u->role !== 'owner' && $u->id !== auth()->id())
                                    <form action="{{ route('users.destroy', $u->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger fw-bold px-2 py-1" onclick="return confirm('Hapus akun {{ $u->name }} secara permanen?');">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL TAMBAH STAF BARU                     -->
<!-- ========================================== -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold text-dark">Tambah Staf Baru</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" onclick="autoGenerateAll()">⚡ Buat Semua Otomatis</button>
            </div>
            <form action="{{ route('users.store') }}" method="POST" id="addStaffForm">
                @csrf
                <div class="modal-body pb-4 pt-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="addName" class="form-control" required placeholder="Contoh: Budi Santoso">
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold small mb-0">Username Login <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link p-0 text-decoration-none small fw-bold" onclick="generateUsername('addUsername', 'addRole')">⚡ Buat Username Angka</button>
                        </div>
                        <input type="text" name="username" id="addUsername" class="form-control font-monospace" required placeholder="Contoh: staff_cashier_0299">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Pilih Peran (Role) <span class="text-danger">*</span></label>
                        <select name="role" id="addRole" class="form-select" required onchange="if(document.getElementById('addUsername').value.startsWith('staff_')) generateUsername('addUsername', 'addRole');">
                            <option value="cashier">Kasir</option>
                            <option value="admin">Admin Gudang</option>
                            <option value="supervisor">Supervisor</option>
                        </select>
                    </div>

                    <div class="p-3 rounded mt-3" style="background-color: #fdf2f4; border: 1px dashed #d17885;">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small mb-0">Password Login <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link p-0 text-decoration-none small fw-bold text-danger" onclick="generatePassword('addUsername', 'addRole', 'addPass', 'addPassBarContainer', 'addPassBar', 'addPassText')">⚡ Buat Password Kuat</button>
                            </div>
                            <div class="input-group">
                                <input type="password" name="password" id="addPass" class="form-control font-monospace" required placeholder="Contoh: @Staff_cashier_0299" autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleVis('addPass', this)">👁️</button>
                            </div>
                            <!-- Bar Kekuatan Sandi -->
                            <div class="progress mt-2" style="height: 5px; display: none;" id="addPassBarContainer">
                                <div class="progress-bar" id="addPassBar" role="progressbar" style="width: 0%;"></div>
                            </div>
                            <small id="addPassText" class="fw-bold d-block mt-1" style="font-size: 11px;"></small>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">Saran: Gunakan kombinasi simbol (@), huruf besar, huruf kecil, dan angka.</small>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small mb-0">PIN Kunci Layar (6 Digit Unik)</label>
                                <button type="button" class="btn btn-link p-0 text-decoration-none small fw-bold text-danger" onclick="generatePin('addPin')">⚡ Buat PIN Unik</button>
                            </div>
                            <div class="input-group">
                                <input type="password" name="pin" id="addPin" class="form-control font-monospace text-center fw-bold fs-5" maxlength="6" inputmode="numeric" placeholder="******" oninput="this.value = this.value.replace(/[^0-9]/g, '')" autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleVis('addPin', this)">👁️</button>
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Hanya angka 6 digit dan wajib unik antarpengguna.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn fw-bold text-white w-100 py-2" style="background-color: #d17885; border-radius: 8px;">Simpan Staf</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL EDIT STAF & UBAH PIN                 -->
<!-- ========================================== -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark">Edit Data Staf</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editStaffForm" method="POST" action="">
                @csrf @method('PUT')
                <div class="modal-body pb-4 pt-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold small mb-0">Username Login <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link p-0 text-decoration-none small fw-bold" onclick="generateUsername('editUsername', 'editRole')">⚡ Buat Username Angka</button>
                        </div>
                        <input type="text" name="username" id="editUsername" class="form-control font-monospace" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small">Peran (Role)</label>
                            <select name="role" id="editRole" class="form-select" required>
                                <option value="cashier">Kasir</option>
                                <option value="admin">Admin Gudang</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="owner">Owner</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small">Status Akun</label>
                            <select name="is_active" id="editIsActive" class="form-select" required>
                                <option value="1">Aktif (Bisa Login)</option>
                                <option value="0">Nonaktif (Diblokir)</option>
                            </select>
                        </div>
                    </div>

                    <div class="p-3 rounded mt-3" style="background-color: #fdf2f4; border: 1px dashed #d17885;">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small mb-0">Ganti Password (Opsional)</label>
                                <button type="button" class="btn btn-link p-0 text-decoration-none small fw-bold text-danger" onclick="generatePassword('editUsername', 'editRole', 'editPass', 'editPassBarContainer', 'editPassBar', 'editPassText')">⚡ Buat Password Kuat</button>
                            </div>
                            <div class="input-group">
                                <input type="password" name="password" id="editPass" class="form-control font-monospace" placeholder="Kosongkan jika tidak diganti" autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleVis('editPass', this)">👁️</button>
                            </div>
                            <!-- Bar Kekuatan Sandi Edit -->
                            <div class="progress mt-2" style="height: 5px; display: none;" id="editPassBarContainer">
                                <div class="progress-bar" id="editPassBar" role="progressbar" style="width: 0%;"></div>
                            </div>
                            <small id="editPassText" class="fw-bold d-block mt-1" style="font-size: 11px;"></small>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">Saran: Gunakan kombinasi simbol (@), huruf besar, huruf kecil, dan angka.</small>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small mb-0">PIN Kunci Layar (6 Digit Unik)</label>
                                <button type="button" class="btn btn-link p-0 text-decoration-none small fw-bold text-danger" onclick="generatePin('editPin')">⚡ Buat PIN Unik</button>
                            </div>
                            <div class="input-group">
                                <input type="password" name="pin" id="editPin" class="form-control font-monospace text-center fw-bold fs-5" maxlength="6" inputmode="numeric" placeholder="******" oninput="this.value = this.value.replace(/[^0-9]/g, '')" autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleVis('editPin', this)">👁️</button>
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Hanya angka 6 digit dan wajib unik.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn fw-bold text-white w-100 py-2" style="background-color: #d17885; border-radius: 8px;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const existingPins = {!! json_encode($users->pluck('pin')->filter()->values()) !!};
    const existingUsernames = {!! json_encode($users->pluck('username')->values()) !!};

    function generatePin(targetInputId) {
        let pin = '';
        let loop = 0;
        do {
            pin = Math.floor(100000 + Math.random() * 900000).toString();
            loop++;
        } while (existingPins.includes(pin) && loop < 100);

        let input = document.getElementById(targetInputId);
        input.value = pin;
        input.type = 'text';
    }

    // Format Sandi: @ + Username dengan huruf pertama kapital (contoh: @Staff_cashier_0776)
    function generatePassword(usernameId, roleId, targetInputId, barContainerId, barId, textId) {
        let username = document.getElementById(usernameId).value.trim();
        if (!username) {
            username = generateUsername(usernameId, roleId);
        }

        // Ubah huruf pertama username menjadi kapital
        let capitalized = username.charAt(0).toUpperCase() + username.slice(1);
        let pass = '@' + capitalized;

        let input = document.getElementById(targetInputId);
        input.value = pass;
        input.type = 'text';
        input.dispatchEvent(new Event('input')); // Memicu pengecekan bar kekuatan
    }

    // Format Username: staff_[role]_[4angka] (contoh: staff_cashier_0776)
    function generateUsername(targetId, roleId) {
        let role = document.getElementById(roleId).value;
        let randNum = String(Math.floor(1000 + Math.random() * 9000));
        let username = 'staff_' + role + '_' + randNum;

        while (existingUsernames.includes(username)) {
            randNum = String(Math.floor(1000 + Math.random() * 9000));
            username = 'staff_' + role + '_' + randNum;
        }
        document.getElementById(targetId).value = username;
        return username;
    }

    function autoGenerateAll() {
        let role = document.getElementById('addRole').value;
        let roleTitle = role.charAt(0).toUpperCase() + role.slice(1);

        if (!document.getElementById('addName').value) {
            document.getElementById('addName').value = 'Staff ' + roleTitle;
        }

        generateUsername('addUsername', 'addRole');
        generatePassword('addUsername', 'addRole', 'addPass', 'addPassBarContainer', 'addPassBar', 'addPassText');
        generatePin('addPin');
    }

    function editStaff(user) {
        document.getElementById('editStaffForm').action = '/users/' + user.id;
        document.getElementById('editName').value = user.name;
        document.getElementById('editUsername').value = user.username;
        document.getElementById('editRole').value = user.role;
        document.getElementById('editPin').value = user.pin || '';
        document.getElementById('editIsActive').value = (user.is_active === undefined || user.is_active === null) ? 1 : (user.is_active ? 1 : 0);

        document.getElementById('editPass').value = '';
        document.getElementById('editPassBarContainer').style.display = 'none';
        document.getElementById('editPassText').innerText = '';
    }

    function toggleVis(inputId, btn) {
        let input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text'; btn.innerText = '🙈';
        } else {
            input.type = 'password'; btn.innerText = '👁️';
        }
    }

    function bindPasswordStrength(inputId, containerId, barId, textId) {
        let inputEl = document.getElementById(inputId);
        if (!inputEl) return;

        inputEl.addEventListener('input', function () {
            let val = this.value;
            let barContainer = document.getElementById(containerId);
            let bar = document.getElementById(barId);
            let text = document.getElementById(textId);

            if (!val) {
                barContainer.style.display = 'none';
                text.innerText = '';
                return;
            }

            barContainer.style.display = 'flex';
            let score = 0;
            if (val.length >= 6) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            if (score <= 2) {
                bar.style.width = '30%';
                bar.className = 'progress-bar bg-danger';
                text.innerText = 'Kekuatan: Lemah';
                text.className = 'fw-bold d-block mt-1 text-danger';
            } else if (score <= 4) {
                bar.style.width = '70%';
                bar.className = 'progress-bar bg-warning';
                text.innerText = 'Kekuatan: Cukup Baik';
                text.className = 'fw-bold d-block mt-1 text-warning';
            } else {
                bar.style.width = '100%';
                bar.className = 'progress-bar bg-success';
                text.innerText = 'Kekuatan: Sangat Kuat ✅';
                text.className = 'fw-bold d-block mt-1 text-success';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        bindPasswordStrength('addPass', 'addPassBarContainer', 'addPassBar', 'addPassText');
        bindPasswordStrength('editPass', 'editPassBarContainer', 'editPassBar', 'editPassText');
    });
</script>
@endsection