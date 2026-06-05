@extends('layouts.guest')

@section('title', 'Dashboard — Lansia Papua')

@section('content')
<div class="min-h-screen bg-[#0B1628] flex items-center justify-center px-6">
    <div class="max-w-md w-full text-center">

        <!-- Logo -->
        <div class="w-20 h-20 rounded-full bg-sky-500/10 border border-sky-500/30 flex items-center justify-center mx-auto mb-6">
            <img src="{{ asset('images/logo-papua.svg') }}" alt="Logo" class="w-12 h-12 object-contain" />
        </div>

        <!-- Welcome -->
        <h1 class="font-serif text-3xl font-bold text-white mb-2">
            Selamat Datang, <span class="text-sky-400">{{ auth()->user()->name }}</span>
        </h1>
        <p class="text-white/50 text-sm mb-2">
            Role: <span class="text-sky-300 font-semibold capitalize">{{ auth()->user()->getRoleNames()->first() ?? '-' }}</span>
        </p>
        <p class="text-white/30 text-xs mb-10">
            Login terakhir: {{ auth()->user()->last_login_at?->diffForHumans() ?? 'Baru saja' }}
        </p>

        <!-- Info card -->
        <div class="bg-white/5 border border-white/10 rounded-2xl p-6 mb-8 text-left">
            <div class="text-xs font-semibold text-sky-400 uppercase tracking-wider mb-3">Status Sistem</div>
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-white/50">Database</span>
                    <span class="text-green-400 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span> PostgreSQL 16
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-white/50">Laravel</span>
                    <span class="text-white/70">{{ app()->version() }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-white/50">Filament</span>
                    <span class="text-white/70">v{{ \Composer\InstalledVersions::getVersion('filament/filament') ?? '5.x' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-white/50">Sprint</span>
                    <span class="text-yellow-400">1 — Fondasi (In Progress)</span>
                </div>
            </div>
        </div>

        <!-- Logout -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/15 border border-white/15 text-white/70 text-sm px-6 py-2.5 rounded-full transition-all cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Logout
            </button>
        </form>

    </div>
</div>
@endsection
