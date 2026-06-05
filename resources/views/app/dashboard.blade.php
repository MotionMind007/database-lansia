@extends('layouts.app')

@section('title', 'Dashboard — Lansia Papua')
@section('page-title', 'Dashboard')

@section('content')
<div class="max-w-[1400px]">

    <!-- Filter Bar -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
        <form method="GET" action="{{ route('app.dashboard') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[180px]">
                <label class="block text-[0.65rem] text-gray-500 font-medium mb-1 uppercase tracking-wider">Kabupaten/Kota</label>
                <select name="city_id" id="filter-city" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none" onchange="this.form.submit()">
                    <option value="">Semua Kab/Kota</option>
                    @foreach($cities as $city)
                    <option value="{{ $city->id }}" {{ $cityId == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[180px]">
                <label class="block text-[0.65rem] text-gray-500 font-medium mb-1 uppercase tracking-wider">Distrik/Kecamatan</label>
                <select name="district_id" id="filter-district" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none" {{ $districts->isEmpty() ? 'disabled' : '' }} onchange="this.form.submit()">
                    <option value="">Semua Distrik</option>
                    @foreach($districts as $d)
                    <option value="{{ $d->id }}" {{ $districtId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[180px]">
                <label class="block text-[0.65rem] text-gray-500 font-medium mb-1 uppercase tracking-wider">Kelurahan/Kampung</label>
                <select name="village_id" id="filter-village" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none" {{ $villages->isEmpty() ? 'disabled' : '' }} onchange="this.form.submit()">
                    <option value="">Semua Kelurahan</option>
                    @foreach($villages as $v)
                    <option value="{{ $v->id }}" {{ $villageId == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            @if($cityId || $districtId || $villageId)
            <a href="{{ route('app.dashboard') }}" class="text-xs text-gray-400 hover:text-sky-500 font-medium px-3 py-2">Reset</a>
            @endif
        </form>
    </div>

    <!-- Row 1: Stat cards -->
    <div class="grid grid-cols-4 gap-3 mb-4">
        @php
            $cards = [
                ['label' => 'Total Responden', 'value' => $stats['total'], 'color' => 'text-slate-700'],
                ['label' => 'Bulan Ini', 'value' => $stats['this_month'], 'color' => 'text-blue-600'],
                ['label' => 'Terverifikasi', 'value' => $stats['verified'], 'color' => 'text-emerald-600'],
                ['label' => 'Perlu Revisi', 'value' => $stats['need_revision'], 'color' => 'text-amber-600'],
            ];
        @endphp
        @foreach($cards as $card)
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <div class="text-[0.65rem] text-gray-400 font-medium uppercase tracking-wider mb-1">{{ $card['label'] }}</div>
            <div class="text-2xl font-bold {{ $card['color'] }}">{{ $card['value'] }}</div>
        </div>
        @endforeach
    </div>

    <!-- Row 2: Status overview + mini stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm md:col-span-2">
            <h3 class="text-xs font-semibold text-gray-600 mb-3">Status Data</h3>
            <div class="grid grid-cols-5 gap-2">
                @php
                    $statusItems = [
                        ['label' => 'Draft', 'value' => $stats['draft'], 'dot' => 'bg-gray-300'],
                        ['label' => 'Submitted', 'value' => $stats['submitted'], 'dot' => 'bg-blue-400'],
                        ['label' => 'Verified', 'value' => $stats['verified'], 'dot' => 'bg-emerald-500'],
                        ['label' => 'Revisi', 'value' => $stats['need_revision'], 'dot' => 'bg-amber-400'],
                        ['label' => 'Ditolak', 'value' => $stats['rejected'], 'dot' => 'bg-red-400'],
                    ];
                @endphp
                @foreach($statusItems as $s)
                <div class="text-center">
                    <div class="w-2 h-2 rounded-full {{ $s['dot'] }} mx-auto mb-1"></div>
                    <div class="text-lg font-bold text-gray-700">{{ $s['value'] }}</div>
                    <div class="text-[0.6rem] text-gray-400">{{ $s['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex flex-col justify-center items-center">
            <div class="text-[0.65rem] text-gray-400 uppercase tracking-wider mb-1">Submitted</div>
            <div class="text-3xl font-bold text-blue-500">{{ $stats['submitted'] }}</div>
            <div class="text-[0.6rem] text-gray-400 mt-1">menunggu verifikasi</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex flex-col justify-center items-center">
            <div class="text-[0.65rem] text-gray-400 uppercase tracking-wider mb-1">Ditolak</div>
            <div class="text-3xl font-bold text-red-400">{{ $stats['rejected'] }}</div>
            <div class="text-[0.6rem] text-gray-400 mt-1">survey ulang</div>
        </div>
    </div>

    <!-- Row 3: Analytics per question (grouped) -->
    @if(count($questionAnalytics) > 0)
    <div class="grid grid-cols-3 gap-3">
        @foreach($questionAnalytics as $qa)
        @php $chartId = 'chart_' . $qa['key']; @endphp
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm min-h-[180px] flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-xs font-semibold text-gray-600">{{ $qa['label'] }}</h4>
                <span class="text-[0.55rem] text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded">{{ $qa['group'] }}</span>
            </div>

            @if($qa['type'] === 'text')
            <div class="space-y-2 flex-1 flex flex-col justify-center">
                @foreach($qa['data'] as $option => $count)
                @php $pct = $qa['total'] > 0 ? round(($count / $qa['total']) * 100, 1) : 0; @endphp
                <div>
                    <div class="flex items-center justify-between text-[0.7rem] mb-0.5">
                        <span class="text-gray-600">{{ $option }}</span>
                        <span class="font-semibold text-gray-700">{{ $pct }}%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-blue-300/70" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
                <div class="text-[0.55rem] text-gray-400 pt-1">{{ $qa['total'] }} responden</div>
            </div>
            @else
            <div class="flex-1 flex items-center justify-center">
                <canvas id="{{ $chartId }}" class="w-full max-h-[140px]"></canvas>
            </div>
            <div class="text-[0.55rem] text-gray-400 text-right">{{ $qa['total'] }} responden</div>
            @endif
        </div>
        @endforeach
    </div>
    @else
    <div class="bg-white rounded-xl p-10 border border-gray-100 shadow-sm text-center">
        <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
        </svg>
        <p class="text-sm font-medium text-gray-400">Belum ada data untuk ditampilkan</p>
        <p class="text-xs text-gray-300 mt-1">Input survey untuk melihat analisis</p>
    </div>
    @endif

</div>
@endsection

@push('scripts')
@if(count($questionAnalytics) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Soft professional palette (like the reference image - blue/orange tones)
    const palette = ['#5B8DEF','#F5A623','#7EC8E3','#B8D4E3','#FFD093','#A3C4DC'];

    @foreach($questionAnalytics as $qa)
    @if($qa['type'] !== 'text')
    @php $chartId = 'chart_' . $qa['key']; @endphp
    (function() {
        const el = document.getElementById('{{ $chartId }}');
        if (!el) return;
        new Chart(el, {
            type: '{{ $qa["type"] }}',
            data: {
                labels: {!! json_encode(array_keys($qa['data'])) !!},
                datasets: [{
                    data: {!! json_encode(array_values($qa['data'])) !!},
                    @if($qa['type'] === 'doughnut')
                    backgroundColor: palette.slice(0, {{ count($qa['data']) }}),
                    borderWidth: 0,
                    hoverOffset: 4,
                    @else
                    backgroundColor: '#5B8DEF',
                    borderRadius: 4,
                    maxBarThickness: 14,
                    barPercentage: 0.5,
                    @endif
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '{{ $qa["type"] === "doughnut" ? "70%" : "0" }}',
                plugins: {
                    legend: {
                        display: {{ $qa['type'] === 'doughnut' ? 'true' : 'false' }},
                        position: 'right',
                        labels: { font: { size: 10 }, padding: 8, boxWidth: 8, color: '#6B7280', usePointStyle: true, pointStyle: 'circle' }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = ((ctx.raw / total) * 100).toFixed(1);
                                return ' ' + ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                            }
                        }
                    }
                },
                @if($qa['type'] === 'bar')
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 9 }, color: '#9CA3AF' } },
                    y: { beginAtZero: true, grid: { color: '#F1F5F9', drawBorder: false }, ticks: { font: { size: 9 }, color: '#9CA3AF', stepSize: 1 } }
                }
                @endif
            }
        });
    })();
    @endif
    @endforeach
});
</script>
@endif
@endpush
