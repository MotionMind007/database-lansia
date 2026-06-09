<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Lansia Papua')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-papua.svg') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media (max-width: 1023px) {
            #sidebar { transform: translateX(-100%); position: fixed; inset: 0; z-index: 50; width: 260px; }
            #sidebar.open { transform: translateX(0); }
            #sidebar-overlay.open { display: block; }
        }
        @media (min-width: 1024px) {
            #sidebar { transform: none; position: relative; z-index: auto; flex-shrink: 0; }
            #sidebar-overlay { display: none !important; }
            .hamburger-btn { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased bg-gray-50 font-sans text-gray-800">

<!-- Mobile overlay -->
<div class="fixed inset-0 bg-black/50 z-40 hidden" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<div class="flex h-screen overflow-x-hidden">

    <!-- ═══════════════════════════════
         SIDEBAR
    ═══════════════════════════════ -->
    <aside class="w-[260px] bg-[#0F1A2E] flex flex-col overflow-y-auto transition-transform duration-200 ease-in-out" id="sidebar">

        <!-- Logo area -->
        <div class="px-5 py-5 flex items-center justify-between border-b border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-sky-500/15 border border-sky-500/30 flex items-center justify-center shrink-0">
                    <img src="{{ asset('images/logo-papua.svg') }}" alt="Logo" class="w-6 h-6 object-contain" />
                </div>
                <div>
                    <div class="text-white font-bold text-sm tracking-wide">JALAN</div>
                    <div class="text-[0.65rem] text-white/40 tracking-wider uppercase">Jaminan Lanjut Usia</div>
                </div>
            </div>
            <!-- Close button (mobile only) -->
            <button class="lg:hidden text-white/50 hover:text-white p-1" onclick="toggleSidebar()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-4 space-y-1">
            @php
                $user = auth()->user();
                $isAdminUser = $user->hasAnyRole(['administrator', 'super admin', 'super_admin']);
                $isSurveyor = $user->hasRole('surveyor');
                $isVerifikator = $user->hasRole('verifikator');
                $displayRole = $user->getRoleNames()->first() ?? 'User';
            @endphp

            {{-- Menu berdasarkan role --}}
            @if($isAdminUser)
                <x-sidebar-link href="{{ route('app.dashboard') }}" icon="home" :active="request()->routeIs('app.dashboard')">
                    Dashboard
                </x-sidebar-link>
                <x-sidebar-link href="{{ route('app.lansia.index') }}" icon="users" :active="request()->routeIs('app.lansia.*')">
                    Data Lansia
                </x-sidebar-link>
                <x-sidebar-link href="{{ route('app.survey.create') }}" icon="clipboard" :active="request()->routeIs('app.survey.*')">
                    Input Survey
                </x-sidebar-link>
                <x-sidebar-link href="{{ route('app.verification.index') }}" icon="check" :active="request()->routeIs('app.verification.*')">
                    Verifikasi Survey
                </x-sidebar-link>
                <x-sidebar-link href="{{ route('app.export', ['format' => 'csv']) }}" icon="chart" :active="false">
                    Export Data
                </x-sidebar-link>
                <div class="pt-3 mt-3 border-t border-white/10"></div>
                <x-sidebar-link href="{{ route('app.activity-logs.index') }}" icon="log" :active="request()->routeIs('app.activity-logs.*')">
                    Log Aktivitas
                </x-sidebar-link>
                <x-sidebar-link href="{{ url('/admin') }}" icon="settings" :active="request()->is('admin*')">
                    Setting
                </x-sidebar-link>
            @elseif($isSurveyor)
                <x-sidebar-link href="{{ route('app.dashboard') }}" icon="home" :active="request()->routeIs('app.dashboard')">
                    Beranda
                </x-sidebar-link>
                <x-sidebar-link href="{{ route('app.survey.create') }}" icon="clipboard" :active="request()->routeIs('app.survey.*')">
                    Input Survey
                </x-sidebar-link>
                <x-sidebar-link href="{{ route('app.lansia.index') }}" icon="users" :active="request()->routeIs('app.lansia.*')">
                    Data Survey Saya
                </x-sidebar-link>
                <x-sidebar-link href="{{ route('app.export', ['format' => 'csv']) }}" icon="chart" :active="false">
                    Export Data
                </x-sidebar-link>
            @elseif($isVerifikator)
                <x-sidebar-link href="{{ route('app.dashboard') }}" icon="home" :active="request()->routeIs('app.dashboard')">
                    Beranda
                </x-sidebar-link>
                <x-sidebar-link href="{{ route('app.verification.index') }}" icon="check" :active="request()->routeIs('app.verification.*')">
                    Data Masuk Verifikasi
                </x-sidebar-link>
                <x-sidebar-link href="{{ route('app.lansia.index') }}" icon="users" :active="request()->routeIs('app.lansia.*')">
                    Data Lansia
                </x-sidebar-link>
            @endif
        </nav>

        <!-- User info & logout -->
        <div class="px-3 pb-4 border-t border-white/10 pt-5">
            <div class="px-3 pb-4 mb-3 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-sky-500/20 flex items-center justify-center text-sky-400 text-xs font-bold shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-white text-xs font-semibold truncate">{{ auth()->user()->name }}</div>
                        <div class="text-white/40 text-[0.65rem] capitalize truncate">{{ $displayRole }}</div>
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 text-xs font-medium text-white/40 hover:text-white/70 hover:bg-white/5 rounded-lg transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <!-- ═══════════════════════════════
         MAIN CONTENT AREA
    ═══════════════════════════════ -->
    <main class="flex-1 flex flex-col overflow-hidden">

        <!-- Top bar -->
        <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-6 shrink-0">
            <div class="flex items-center gap-3">
                <!-- Hamburger (mobile only) -->
                <button class="hamburger-btn text-gray-600 hover:text-gray-900 p-1 -ml-1" onclick="toggleSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-sm font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-xs text-gray-400 hidden sm:inline">{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
        </header>

        <!-- Page content -->
        <div class="flex-1 overflow-y-auto overflow-x-hidden p-4 lg:p-6 pb-20 lg:pb-6">
            @yield('content')
        </div>
    </main>

</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebar-overlay').classList.toggle('open');
    }
</script>
@stack('scripts')
</body>
</html>
