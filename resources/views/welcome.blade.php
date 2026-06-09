@extends('layouts.guest')

@section('title', 'Lansia Papua — Sistem Database Survey Lansia Provinsi Papua')

@push('styles')
<style nonce="{{ Vite::cspNonce() }}">
    .slideshow { position: fixed; inset: 0; z-index: 0; }
    .slide {
        position: absolute; inset: 0;
        background-size: cover; background-position: center;
        opacity: 0; transition: opacity 1.2s ease-in-out;
    }
    .slide.active { opacity: 1; }
    .slide::after {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(135deg, rgba(5,30,60,0.82) 0%, rgba(10,50,90,0.65) 50%, rgba(5,20,40,0.75) 100%);
    }
    .logo-ring {
        width: 120px; height: 120px; border-radius: 50%;
        background: rgba(255,255,255,0.08); border: 1.5px solid rgba(255,255,255,0.25);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 0 0 8px rgba(255,255,255,0.04), 0 0 40px rgba(100,180,255,0.2);
        animation: pulse-ring 3s ease-in-out infinite;
    }
    @keyframes pulse-ring {
        0%, 100% { box-shadow: 0 0 0 8px rgba(255,255,255,0.04), 0 0 40px rgba(100,180,255,0.2); }
        50% { box-shadow: 0 0 0 14px rgba(255,255,255,0.06), 0 0 60px rgba(100,180,255,0.35); }
    }
    .animate-up { opacity: 0; transform: translateY(24px); animation: fadeUp 0.7s ease forwards; }
    @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
    .delay-1 { animation-delay: 0.15s; }
    .delay-2 { animation-delay: 0.3s; }
    .delay-3 { animation-delay: 0.45s; }
    .delay-4 { animation-delay: 0.6s; }
    .delay-5 { animation-delay: 0.75s; }
    .delay-6 { animation-delay: 0.9s; }
    @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
</style>
@endpush

@section('content')
<!-- Background Slideshow -->
<div class="slideshow">
    <div class="slide active" style="background-image: url('{{ asset('images/bg-papua-1.png') }}')"></div>
    <div class="slide" style="background-image: url('{{ asset('images/bg-papua-2.png') }}')"></div>
</div>

<!-- Main Content -->
<div class="relative z-10 h-screen flex flex-col items-center justify-center px-6 text-center">

    <!-- Logo -->
    <div class="logo-ring mb-8 animate-up delay-1">
        <img src="{{ asset('images/logo-papua.svg') }}" alt="Lambang Provinsi Papua" class="w-20 h-20 object-contain drop-shadow-lg" />
    </div>

    <!-- Eyebrow Badge -->
    <div class="animate-up delay-2 inline-flex items-center gap-2 bg-white/10 border border-white/20 rounded-full px-4 py-1.5 backdrop-blur-sm mb-5">
        <span class="w-1.5 h-1.5 rounded-full bg-green-400 shadow-[0_0_6px_#4ADE80]" style="animation: blink 2s ease-in-out infinite"></span>
        <span class="text-xs font-semibold tracking-wider uppercase text-white/85">Dinas Sosial, kependudukan dan Catatan Sipil Provinsi Papua</span>
    </div>

    <!-- Main Heading -->
    <h1 class="animate-up delay-3 font-serif text-5xl md:text-6xl font-bold text-white mb-2" style="text-shadow: 0 2px 20px rgba(0,0,0,0.4);">
        <span class="bg-gradient-to-r from-sky-300 via-sky-400 to-sky-500 bg-clip-text text-transparent">Jaminan Lanjut Usia (JALAN)</span>
    </h1>

    <!-- Subtitle -->
    <p class="animate-up delay-3 text-base text-white/70 tracking-wide mb-1">
        Sistem Database Survey Lansia
    </p>
    <p class="animate-up delay-3 text-sm font-semibold text-white/50 tracking-widest uppercase mb-8">
        Provinsi Papua
    </p>

    <!-- Divider -->
    <div class="animate-up delay-4 w-16 h-0.5 bg-gradient-to-r from-sky-400 to-sky-600 rounded-full mb-8"></div>

    <!-- Stats -->
    <div class="animate-up delay-4 flex items-center gap-8 mb-10 flex-wrap justify-center">
        <div class="text-center">
            <div class="text-2xl font-bold text-sky-300">{{ number_format($lansia_count) }}</div>
            <div class="text-[0.68rem] text-white/50 uppercase tracking-wider mt-1">Lansia Terdata</div>
        </div>
        <div class="w-px h-10 bg-white/15"></div>
        <div class="text-center">
            <div class="text-2xl font-bold text-sky-300">{{ number_format($village_count) }}</div>
            <div class="text-[0.68rem] text-white/50 uppercase tracking-wider mt-1">Kampung Tercakup</div>
        </div>
        <div class="w-px h-10 bg-white/15"></div>
        <div class="text-center">
            <div class="text-2xl font-bold text-sky-300">{{ $surveyor_count }}</div>
            <div class="text-[0.68rem] text-white/50 uppercase tracking-wider mt-1">Surveyor Aktif</div>
        </div>
    </div>

    <!-- CTA Button -->
    <a href="{{ route('login') }}"
       class="animate-up delay-5 inline-flex items-center gap-3 bg-gradient-to-br from-sky-500 to-sky-700 text-white text-sm font-semibold px-8 py-3.5 rounded-full shadow-[0_4px_20px_rgba(14,165,233,0.45)] hover:shadow-[0_8px_30px_rgba(14,165,233,0.6)] hover:-translate-y-0.5 transition-all duration-200 group cursor-pointer">
        Masuk ke Sistem
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14M12 5l7 7-7 7"/>
        </svg>
    </a>

    <!-- Footer -->
    <p class="animate-up delay-6 text-[0.7rem] text-white/30 mt-6 tracking-wide">
        Dinas Sosial Provinsi Papua &nbsp;·&nbsp; Sistem Pendataan Lansia Digital
    </p>

</div>

<!-- Slide Indicators -->
<div class="fixed bottom-8 left-1/2 -translate-x-1/2 z-20 flex gap-2">
    <button class="indicator w-8 h-0.5 rounded-full bg-sky-400 transition-all" onclick="goSlide(0)"></button>
    <button class="indicator w-7 h-0.5 rounded-full bg-white/25 transition-all" onclick="goSlide(1)"></button>
</div>
@endsection

@push('scripts')
<script nonce="{{ Vite::cspNonce() }}">
    const slides = document.querySelectorAll('.slide');
    const indicators = document.querySelectorAll('.indicator');
    let current = 0;
    let timer;

    function goSlide(i) {
        slides[current].classList.remove('active');
        indicators[current].classList.replace('bg-sky-400', 'bg-white/25');
        indicators[current].classList.replace('w-8', 'w-7');
        current = i;
        slides[current].classList.add('active');
        indicators[current].classList.replace('bg-white/25', 'bg-sky-400');
        indicators[current].classList.replace('w-7', 'w-8');
        clearInterval(timer);
        startTimer();
    }

    function startTimer() {
        timer = setInterval(() => goSlide((current + 1) % slides.length), 5000);
    }
    startTimer();
</script>
@endpush
