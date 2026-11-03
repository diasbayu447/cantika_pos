<!-- Modal Lockscreen Universal -->
<div class="modal fade" id="globalLockscreenModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" style="backdrop-filter: blur(12px); background-color: rgba(0,0,0,0.85); z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body text-center py-5">
                <div style="font-size: 4rem;">🔒</div>
                <h4 class="text-white fw-bold mt-3">Layar Terkunci</h4>
                <p class="text-white-50 mb-4">
                    Sesi aman: <strong class="text-white">{{ auth()->user()->display_name ?? auth()->user()->name }}</strong> ({{ strtoupper(auth()->user()->role) }})<br>
                    <small>Program terkunci. Masukkan PIN Anda atau PIN Supervisor untuk membuka.</small>
                </p>
                
                <div class="input-group mx-auto mb-2" style="max-width: 300px;">
                    <input type="password" 
                           id="globalUnlockPin" 
                           class="form-control form-control-lg text-center fw-bold font-monospace" 
                           placeholder="******" 
                           maxlength="6" 
                           inputmode="numeric"
                           pattern="[0-9]*"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                           autocomplete="off">
                    <button class="btn btn-danger px-4 fw-bold" id="btnGlobalUnlock">Buka</button>
                </div>
                <small id="globalUnlockMessage" class="text-danger d-block fw-bold"></small>

                <form action="{{ route('logout') }}" method="POST" class="mt-4" onsubmit="localStorage.removeItem('app_locked')">
                    @csrf
                    <button type="submit" class="btn btn-link text-white-50" style="text-decoration: none; font-size: 13px;">Bukan Anda? Logout</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        if (localStorage.getItem('app_locked') === '1') {
            document.documentElement.style.visibility = 'visible';
        }
    })();

    document.addEventListener("DOMContentLoaded", function() {
        let globalIdleTime = 0;
        const GLOBAL_IDLE_TIMEOUT = 1800; // 30 Menit (1800 detik)
        const userRole = "{{ auth()->user()->role ?? 'guest' }}";

        // Cek jika sesi dalam keadaan terkunci sebelumnya
        if (localStorage.getItem('app_locked') === '1') {
            triggerGlobalLock();
        }

        function triggerGlobalLock() {
            localStorage.setItem('app_locked', '1');
            let lockModalEl = document.getElementById('globalLockscreenModal');
            if (lockModalEl) {
                let lockModal = bootstrap.Modal.getOrCreateInstance(lockModalEl);
                lockModal.show();
                setTimeout(() => { 
                    let pinInput = document.getElementById('globalUnlockPin');
                    if(pinInput) {
                        pinInput.focus();
                        pinInput.select();
                    }
                }, 300);
            }
        }

        // TIMER IDLE OTOMATIS: Khusus berjalan untuk Kasir
        if (userRole === 'cashier') {
            setInterval(function() {
                if (localStorage.getItem('app_locked') === '1') return;

                globalIdleTime++;
                if (globalIdleTime > GLOBAL_IDLE_TIMEOUT) {
                    triggerGlobalLock();
                    globalIdleTime = 0;
                }
            }, 1000);

            ['mousemove', 'keypress', 'click', 'scroll', 'touchstart'].forEach(function(evt) {
                document.addEventListener(evt, function() {
                    if (localStorage.getItem('app_locked') !== '1') {
                        globalIdleTime = 0;
                    }
                }, { passive: true });
            });
        }

        // Eksekusi Buka Kunci (AJAX)
        function submitGlobalUnlock() {
            let pinInput = document.getElementById('globalUnlockPin');
            let pinVal = pinInput.value.trim();
            if (!pinVal) return;

            let btn = document.getElementById('btnGlobalUnlock');
            let msg = document.getElementById('globalUnlockMessage');
            btn.innerText = '...';
            btn.disabled = true;

            let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                            || '{{ csrf_token() }}';

            fetch("{{ route('screen.unlock') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({ pin: pinVal })
            })
            .then(async res => {
                let data = await res.json().catch(() => ({}));
                btn.innerText = 'Buka';
                btn.disabled = false;

                if (res.ok) {
                    localStorage.removeItem('app_locked');
                    globalIdleTime = 0;

                    let lockModalEl = document.getElementById('globalLockscreenModal');
                    let modalInstance = bootstrap.Modal.getInstance(lockModalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    pinInput.value = '';
                    msg.innerText = '';
                    
                    let barcodeInput = document.getElementById('barcodeInput');
                    if (barcodeInput) barcodeInput.focus();
                } else {
                    msg.innerText = data.error || data.message || 'PIN salah atau tidak memiliki akses otorisasi!';
                    pinInput.value = '';
                    pinInput.focus();
                }
            })
            .catch(() => {
                btn.innerText = 'Buka';
                btn.disabled = false;
                msg.innerText = 'Gagal menghubungi server.';
            });
        }

        let unlockBtn = document.getElementById('btnGlobalUnlock');
        if (unlockBtn) unlockBtn.addEventListener('click', submitGlobalUnlock);

        let pinInputEl = document.getElementById('globalUnlockPin');
        if (pinInputEl) {
            pinInputEl.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitGlobalUnlock();
                }
            });
        }
    });
</script>