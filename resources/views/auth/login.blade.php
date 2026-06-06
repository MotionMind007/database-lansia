@extends('layouts.guest')

@section('title', 'Login — Lansia Papua')

@push('styles')
<style>
    .bg-image {
        position: absolute; inset: 0;
        background-size: cover; background-position: center;
        opacity: 0; transition: opacity 1.4s ease-in-out;
    }
    .bg-image.active { opacity: 1; }
    .slide-in { opacity: 0; transform: translateX(-20px); animation: slideIn 0.5s ease forwards; }
    @keyframes slideIn { to { opacity: 1; transform: translateX(0); } }
    .d1 { animation-delay: 0.1s; }
    .d2 { animation-delay: 0.2s; }
    .d3 { animation-delay: 0.3s; }
    .d4 { animation-delay: 0.4s; }
    .d5 { animation-delay: 0.5s; }
</style>
@endpush

@section('content')
<div class="flex h-screen">

    <!-- ══════════════════════════════
         LEFT — LOGIN FORM
    ══════════════════════════════ -->
    <div class="w-[420px] min-w-[420px] bg-[var(--color-navy)] flex flex-col justify-center px-10 py-12 relative overflow-y-auto">

        <!-- Top accent line -->
        <div class="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-sky-500 via-sky-400 to-sky-700"></div>

        <!-- Logo area -->
        <div class="flex items-center gap-3 mb-10 slide-in d1">
            <div class="w-12 h-12 rounded-full bg-sky-500/10 border border-sky-500/30 flex items-center justify-center shrink-0">
                <img src="{{ asset('images/logo-papua.svg') }}" alt="Lambang Papua" class="w-8 h-8 object-contain" />
            </div>
            <div>
                <div class="font-serif text-xl font-bold text-white"><span class="text-sky-400">JALAN</span></div>
                <div class="text-[0.68rem] text-white/40 tracking-wider uppercase">Jaminan Lanjut Usia</div>
            </div>
        </div>

        <!-- Heading -->
        <h2 class="text-2xl font-bold text-white mb-1 slide-in d2">Selamat Datang</h2>
        <p class="text-sm text-white/45 mb-7 leading-relaxed slide-in d2">
            Masuk ke Sistem Database Survey<br>Lansia Provinsi Papua
        </p>

        <!-- Role Tabs -->
        <div class="flex gap-1 bg-white/5 rounded-lg p-1 mb-6 slide-in d3">
            <button type="button" class="role-tab flex-1 py-2 px-2 rounded-md text-xs font-medium transition-all bg-sky-500 text-white shadow-[0_2px_8px_rgba(14,165,233,0.4)]" onclick="setRole('administrator', this)">Administrator</button>
            <button type="button" class="role-tab flex-1 py-2 px-2 rounded-md text-xs font-medium transition-all text-white/45 hover:bg-white/8 hover:text-white/70" onclick="setRole('verifikator', this)">Verifikator</button>
            <button type="button" class="role-tab flex-1 py-2 px-2 rounded-md text-xs font-medium transition-all text-white/45 hover:bg-white/8 hover:text-white/70" onclick="setRole('surveyor', this)">Surveyor</button>
        </div>

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email -->
            <div class="mb-4 slide-in d3">
                <label class="block text-xs font-medium text-white/55 mb-1.5 tracking-wide">Email atau Username</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-white/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <input type="text" name="email" value="{{ old('email') }}" placeholder="Masukkan email atau username"
                           class="w-full bg-white/6 border border-white/10 rounded-lg pl-10 pr-4 py-3 text-sm text-white placeholder-white/20 outline-none focus:border-sky-400 focus:bg-sky-400/5 focus:ring-2 focus:ring-sky-400/15 transition-all" />
                </div>
                @error('email')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-4 slide-in d4">
                <label class="block text-xs font-medium text-white/55 mb-1.5 tracking-wide">Password</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-white/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input type="password" name="password" id="pw-input" placeholder="Masukkan password"
                           class="w-full bg-white/6 border border-white/10 rounded-lg pl-10 pr-10 py-3 text-sm text-white placeholder-white/20 outline-none focus:border-sky-400 focus:bg-sky-400/5 focus:ring-2 focus:ring-sky-400/15 transition-all" />
                    <button type="button" onclick="togglePw()" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/60 transition-colors">
                        <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Options Row -->
            <div class="flex items-center justify-between mb-6 slide-in d4">
                <label class="flex items-center gap-2 cursor-pointer text-xs text-white/45">
                    <input type="checkbox" name="remember" class="w-3.5 h-3.5 accent-sky-500 cursor-pointer" />
                    Ingat saya
                </label>
                <a href="#" class="text-xs text-sky-400 hover:text-sky-300 transition-colors">Lupa Password?</a>
            </div>

            <!-- Submit -->
            <button type="submit"
                    class="slide-in d5 w-full flex items-center justify-center gap-2 bg-gradient-to-br from-sky-500 to-sky-700 text-white text-sm font-semibold py-3 rounded-lg shadow-[0_4px_16px_rgba(14,165,233,0.4)] hover:shadow-[0_6px_24px_rgba(14,165,233,0.55)] hover:-translate-y-px transition-all cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
                Masuk ke Sistem
            </button>
        </form>

        <!-- Role Info -->
        <div class="mt-6 bg-sky-500/8 border border-sky-500/20 rounded-lg px-4 py-3 text-xs text-white/50 leading-relaxed slide-in d5" id="role-info">
            <strong class="text-sky-400 font-semibold">Administrator</strong> — Akses penuh ke seluruh fitur sistem termasuk konfigurasi dashboard, manajemen user, dan laporan.
        </div>

        <!-- Footer -->
        <div class="absolute bottom-6 left-10 right-10 text-center text-[0.68rem] text-white/20 tracking-wide">
            &copy; 2025 Dinas Sosial Provinsi Papua &nbsp;·&nbsp; Sistem Pendataan Lansia
        </div>

    </div>

    <!-- ══════════════════════════════
         RIGHT — IMAGE PANEL
    ══════════════════════════════ -->
    <div class="flex-1 relative overflow-hidden">

        <!-- Background Images -->
        <div class="bg-image active" style="background-image: url('{{ asset('images/bg-papua-1.png') }}')"></div>
        <div class="bg-image" style="background-image: url('{{ asset('images/bg-papua-2.png') }}')"></div>

        <!-- Gradient Overlay -->
        <div class="absolute inset-0 z-[2] bg-gradient-to-r from-[var(--color-navy)]/85 via-[var(--color-navy)]/30 to-[#050f1e]/15"></div>

        <!-- Floating Info Card -->
        <div class="absolute bottom-12 right-10 z-10 max-w-[280px] bg-[#0B1628]/70 backdrop-blur-xl border border-white/12 rounded-2xl p-5">
            <div class="text-[0.68rem] font-semibold tracking-widest uppercase text-sky-400 mb-2">Sistem Database</div>
            <div class="font-serif text-lg font-bold text-white leading-tight mb-2">Pendataan Lansia<br>Provinsi Papua</div>
            <p class="text-xs text-white/50 leading-relaxed mb-3">
                Platform digital untuk mengelola data hasil survei lansia secara terstruktur, aman, dan mudah diakses.
            </p>
            <div class="flex flex-wrap gap-1.5">
                <span class="bg-sky-400/12 border border-sky-400/25 rounded-full px-2.5 py-0.5 text-[0.68rem] text-sky-300">Survei Digital</span>
                <span class="bg-sky-400/12 border border-sky-400/25 rounded-full px-2.5 py-0.5 text-[0.68rem] text-sky-300">Verifikasi Data</span>
                <span class="bg-sky-400/12 border border-sky-400/25 rounded-full px-2.5 py-0.5 text-[0.68rem] text-sky-300">Dashboard</span>
            </div>
        </div>

        <!-- Slide Dots -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-10 flex gap-2">
            <button class="img-dot w-1.5 h-1.5 rounded-full bg-sky-400 scale-125 transition-all" onclick="goBg(0)"></button>
            <button class="img-dot w-1.5 h-1.5 rounded-full bg-white/30 transition-all" onclick="goBg(1)"></button>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    // Role tabs
    const roleInfo = {
        administrator: 'Akses penuh ke seluruh fitur sistem termasuk konfigurasi dashboard, manajemen user, dan laporan.',
        verifikator: 'Dapat memeriksa dan memverifikasi data survey yang telah disubmit oleh Surveyor.',
        surveyor: 'Dapat mengisi kuesioner lapangan, menyimpan draft, dan mengajukan data untuk diverifikasi.'
    };

    function setRole(role, btn) {
        document.querySelectorAll('.role-tab').forEach(t => {
            t.classList.remove('bg-sky-500', 'text-white', 'shadow-[0_2px_8px_rgba(14,165,233,0.4)]');
            t.classList.add('text-white/45');
        });
        btn.classList.add('bg-sky-500', 'text-white', 'shadow-[0_2px_8px_rgba(14,165,233,0.4)]');
        btn.classList.remove('text-white/45');
        const cap = role.charAt(0).toUpperCase() + role.slice(1);
        document.getElementById('role-info').innerHTML = `<strong class="text-sky-400 font-semibold">${cap}</strong> — ${roleInfo[role]}`;
    }

    // Password toggle
    function togglePw() {
        const inp = document.getElementById('pw-input');
        const open = document.getElementById('eye-open');
        const closed = document.getElementById('eye-closed');
        if (inp.type === 'password') {
            inp.type = 'text';
            open.classList.add('hidden');
            closed.classList.remove('hidden');
        } else {
            inp.type = 'password';
            open.classList.remove('hidden');
            closed.classList.add('hidden');
        }
    }

    // Background slideshow
    const bgs = document.querySelectorAll('.bg-image');
    const dots = document.querySelectorAll('.img-dot');
    let bgCur = 0;
    let bgTimer;

    function goBg(i) {
        bgs[bgCur].classList.remove('active');
        dots[bgCur].classList.remove('bg-sky-400', 'scale-125');
        dots[bgCur].classList.add('bg-white/30');
        bgCur = i;
        bgs[bgCur].classList.add('active');
        dots[bgCur].classList.add('bg-sky-400', 'scale-125');
        dots[bgCur].classList.remove('bg-white/30');
        clearInterval(bgTimer);
        bgTimer = setInterval(() => goBg((bgCur + 1) % bgs.length), 6000);
    }

    bgTimer = setInterval(() => goBg((bgCur + 1) % bgs.length), 6000);
</script>
@endpush
