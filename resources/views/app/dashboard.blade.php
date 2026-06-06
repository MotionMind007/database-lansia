@extends('layouts.app')

@section('title', 'Dashboard Analitik - Lansia Papua')
@section('page-title', 'Dashboard Analitik')

@push('styles')
<style>
    .dashboard-chart-frame {
        height: 150px;
        max-height: 150px;
        min-height: 0;
        position: relative;
    }

    .dashboard-chart-frame canvas {
        display: block;
        height: 150px !important;
        max-height: 150px !important;
        width: 100% !important;
    }

    .dashboard-metric-card {
        padding: 16px 18px;
    }

    .dashboard-metric-label {
        display: block;
        margin-bottom: 8px;
        line-height: 1.1;
    }

    .dashboard-metric-value {
        line-height: 1;
        margin-bottom: 8px;
    }

    .dashboard-filter-card,
    .dashboard-quality-card {
        padding: 18px 20px;
    }

    .dashboard-filter-label {
        display: block;
        line-height: 1.1;
        margin-bottom: 8px;
    }

    .dashboard-filter-select {
        min-height: 38px;
    }

    .dashboard-analytics-grid {
        column-count: 1;
        column-gap: 12px;
    }

    .dashboard-analytics-card {
        break-inside: avoid;
        display: inline-block;
        margin: 0 0 12px;
        vertical-align: top;
        width: 100%;
    }

    .dashboard-card-body {
        padding: 16px 18px;
    }

    .dashboard-table th,
    .dashboard-table td {
        padding-left: 18px !important;
        padding-right: 18px !important;
    }

    .dashboard-table-empty {
        padding: 18px !important;
    }

    @media (min-width: 768px) {
        .dashboard-chart-frame,
        .dashboard-chart-frame canvas {
            height: 160px !important;
            max-height: 160px !important;
        }
    }

    @media (min-width: 1180px) {
        .dashboard-analytics-grid {
            column-count: 3;
        }
    }

    @media (min-width: 820px) and (max-width: 1179px) {
        .dashboard-analytics-grid {
            column-count: 2;
        }
    }
</style>
@endpush

@section('content')
<div class="max-w-[1600px] space-y-4">
    @php
        $chartItems = collect($questionAnalytics)
            ->filter(fn ($item) => in_array($item['display'] ?? 'table', ['pie', 'bar', 'grouped_bar'], true))
            ->map(function ($item) {
                if (($item['display'] ?? null) === 'grouped_bar') {
                    return [
                        'key' => $item['key'],
                        'type' => 'bar',
                        'labels' => $item['chart_labels'] ?? [],
                        'datasets' => $item['datasets'] ?? [],
                        'stacked' => false,
                    ];
                }

                return [
                    'key' => $item['key'],
                    'type' => ($item['display'] ?? null) === 'pie' ? 'doughnut' : 'bar',
                    'labels' => collect($item['rows'])->pluck('label')->values(),
                    'data' => collect($item['rows'])->pluck('count')->values(),
                    'stacked' => false,
                ];
            })
            ->values();
    @endphp

    <div class="dashboard-filter-card bg-white rounded-xl border border-gray-100 shadow-sm">
        <form method="GET" action="{{ route('app.dashboard') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[190px]">
                <label class="dashboard-filter-label text-[0.65rem] text-gray-500 font-semibold uppercase tracking-wider">Kabupaten/Kota</label>
                <select name="city_id" class="dashboard-filter-select w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none" onchange="this.form.submit()">
                    <option value="">Semua Kab/Kota</option>
                    @foreach($cities as $city)
                    <option value="{{ $city->id }}" {{ $cityId == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[190px]">
                <label class="dashboard-filter-label text-[0.65rem] text-gray-500 font-semibold uppercase tracking-wider">Distrik/Kecamatan</label>
                <select name="district_id" class="dashboard-filter-select w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none" {{ $districts->isEmpty() ? 'disabled' : '' }} onchange="this.form.submit()">
                    <option value="">Semua Distrik</option>
                    @foreach($districts as $d)
                    <option value="{{ $d->id }}" {{ $districtId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[190px]">
                <label class="dashboard-filter-label text-[0.65rem] text-gray-500 font-semibold uppercase tracking-wider">Kelurahan/Kampung</label>
                <select name="village_id" class="dashboard-filter-select w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none" {{ $villages->isEmpty() ? 'disabled' : '' }} onchange="this.form.submit()">
                    <option value="">Semua Kelurahan</option>
                    @foreach($villages as $v)
                    <option value="{{ $v->id }}" {{ $villageId == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[170px]">
                <label class="dashboard-filter-label text-[0.65rem] text-gray-500 font-semibold uppercase tracking-wider">Jenis Kelamin</label>
                <select name="gender" class="dashboard-filter-select w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none" onchange="this.form.submit()">
                    <option value="">Semua Gender</option>
                    <option value="male" {{ $gender === 'male' ? 'selected' : '' }}>Laki-laki</option>
                    <option value="female" {{ $gender === 'female' ? 'selected' : '' }}>Perempuan</option>
                </select>
            </div>
            <div class="min-w-[230px]">
                <label class="dashboard-filter-label text-[0.65rem] text-gray-500 font-semibold uppercase tracking-wider">Kategori Pertanyaan</label>
                <select name="category" class="dashboard-filter-select w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    @foreach($categoryOptions as $option)
                    @php $optionLabel = is_array($option) ? ($option['label'] ?? $option['group'] ?? '') : (string) $option; @endphp
                    @if($optionLabel !== '')
                    <option value="{{ $optionLabel }}" {{ $category === $optionLabel ? 'selected' : '' }}>{{ $optionLabel }}</option>
                    @endif
                    @endforeach
                </select>
            </div>
            @if($cityId || $districtId || $villageId || $gender || $category)
            <a href="{{ route('app.dashboard') }}" class="text-xs text-gray-400 hover:text-sky-500 font-medium px-3 py-2">Reset filter</a>
            @endif
        </form>
    </div>

    @php
        $cards = [
            ['label' => 'Total Responden', 'value' => $stats['total'], 'color' => 'text-slate-800', 'hint' => 'data dalam cakupan filter'],
            ['label' => 'Bulan Ini', 'value' => $stats['this_month'], 'color' => 'text-blue-600', 'hint' => 'input terbaru'],
            ['label' => 'Terverifikasi', 'value' => $stats['verified'], 'color' => 'text-emerald-600', 'hint' => 'siap dipakai analisis'],
            ['label' => 'Perlu Revisi', 'value' => $stats['need_revision'], 'color' => 'text-amber-600', 'hint' => 'butuh koreksi'],
        ];
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        @foreach($cards as $card)
        <div class="dashboard-metric-card bg-white rounded-xl border border-gray-100 shadow-sm">
            <span class="dashboard-metric-label text-[0.65rem] text-gray-400 font-semibold uppercase tracking-wider">{{ $card['label'] }}</span>
            <div class="dashboard-metric-value text-2xl font-bold {{ $card['color'] }}">{{ number_format($card['value']) }}</div>
            <div class="text-[0.65rem] text-gray-400 leading-snug">{{ $card['hint'] }}</div>
        </div>
        @endforeach
    </div>

    <div class="dashboard-quality-card bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">Kualitas Dataset</h3>
                <p class="text-xs text-gray-400 leading-relaxed">Membantu membaca seberapa lengkap bahan analisisnya pada filter yang aktif.</p>
            </div>
            <span class="text-xs font-semibold text-sky-600 bg-sky-50 px-2.5 py-1 rounded-full">{{ $dashboardSummary['completion_pct'] }}%</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <div class="rounded-lg bg-gray-50 p-4">
                <div class="text-lg font-bold text-gray-800 leading-none mb-2">{{ $dashboardSummary['questions_total'] }}</div>
                <div class="text-[0.68rem] text-gray-500 leading-snug">pertanyaan dianalisis</div>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <div class="text-lg font-bold text-emerald-600 leading-none mb-2">{{ $dashboardSummary['questions_with_data'] }}</div>
                <div class="text-[0.68rem] text-gray-500 leading-snug">punya jawaban</div>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <div class="text-lg font-bold text-blue-600 leading-none mb-2">{{ $dashboardSummary['response_count'] }}</div>
                <div class="text-[0.68rem] text-gray-500 leading-snug">responden terbaca</div>
            </div>
        </div>
        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
            <div class="h-full bg-sky-500 rounded-full" style="width: {{ $dashboardSummary['completion_pct'] }}%"></div>
        </div>
    </div>

    @if(count($questionAnalytics) > 0)
        @php $renderedGroups = []; @endphp
        <div class="dashboard-analytics-grid">
            @foreach($questionAnalytics as $qa)
            @php
                $groupSlug = \Illuminate\Support\Str::slug($qa['group']);
                $isFirstGroupCard = ! in_array($qa['group'], $renderedGroups, true);
                if ($isFirstGroupCard) {
                    $renderedGroups[] = $qa['group'];
                }
            @endphp
                <article @if($isFirstGroupCard) id="group-{{ $groupSlug }}" @endif class="dashboard-analytics-card bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden scroll-mt-6">
                    <div class="dashboard-card-body border-b border-gray-100">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                <span class="text-[0.65rem] font-bold text-white bg-slate-700 rounded px-1.5 py-0.5">{{ $qa['number'] }}</span>
                                <span class="text-[0.62rem] font-semibold text-sky-600 bg-sky-50 rounded px-1.5 py-0.5">{{ $qa['group'] }}</span>
                                <span class="text-[0.65rem] text-gray-400">{{ $qa['answered'] }} dari {{ $qa['base_total'] }} {{ $qa['denominator_label'] }}</span>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 leading-snug">{{ $qa['label'] }}</h3>
                        </div>
                        <p class="text-[0.7rem] text-gray-500 mt-2 leading-snug">{{ $qa['insight'] }}</p>
                    </div>

                    @if(in_array($qa['display'] ?? 'table', ['pie', 'bar', 'grouped_bar'], true))
                    <div class="dashboard-card-body">
                        <div class="dashboard-chart-frame">
                            <canvas id="chart-{{ $qa['key'] }}" class="w-full h-full"></canvas>
                        </div>
                    </div>

                    @if(($qa['display'] ?? null) === 'grouped_bar' && $qa['key'] === 'frekuensi_pangan_pokok')
                    <div class="overflow-x-auto border-t border-gray-100">
                        <table class="dashboard-table w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="text-left font-semibold px-3 py-2">Pangan pokok</th>
                                    @foreach($qa['chart_labels'] as $label)
                                    <th class="text-right font-semibold px-3 py-2">{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($qa['rows'] as $row)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700">{{ $row['label'] }}</td>
                                    @foreach($qa['chart_labels'] as $label)
                                    <td class="px-3 py-2 text-right font-semibold text-gray-700">{{ number_format($row['counts'][$label] ?? 0) }}</td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @elseif(($qa['display'] ?? null) === 'grouped_bar' && $qa['key'] === 'layanan_kes')
                    <div class="overflow-x-auto border-t border-gray-100">
                        <table class="dashboard-table w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="text-left font-semibold px-3 py-2">Layanan</th>
                                    <th class="text-right font-semibold px-3 py-2">Medis</th>
                                    <th class="text-right font-semibold px-3 py-2">Rutin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($qa['rows'] as $row)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700">{{ $row['label'] }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-700">{{ number_format($row['medis']) }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-700">{{ number_format($row['rutin']) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="overflow-x-auto border-t border-gray-100">
                        <table class="dashboard-table w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="text-left font-semibold px-3 py-2">Jawaban</th>
                                    <th class="text-right font-semibold px-3 py-2">Jumlah</th>
                                    <th class="text-right font-semibold px-3 py-2">% Terjawab</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($qa['rows'] as $row)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700">{{ $row['label'] }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-700">{{ number_format($row['count']) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-500">{{ $row['pct'] }}%</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="dashboard-table-empty text-center text-gray-400">Belum ada pilihan yang terisi.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @endif
                    @elseif($qa['kind'] === 'matrix')
                    <div class="overflow-x-auto">
                        <table class="dashboard-table w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="text-left font-semibold px-3 py-2">Layanan</th>
                                    <th class="text-right font-semibold px-3 py-2">Medis</th>
                                    <th class="text-right font-semibold px-3 py-2">Rutin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($qa['rows'] as $row)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700">{{ $row['label'] }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-700">{{ number_format($row['medis']) }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-700">{{ number_format($row['rutin']) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @elseif($qa['kind'] === 'money_table')
                    <div class="overflow-x-auto">
                        <table class="dashboard-table w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="text-left font-semibold px-3 py-2">Item</th>
                                    <th class="text-right font-semibold px-3 py-2">Terisi</th>
                                    <th class="text-right font-semibold px-3 py-2">Rata-rata</th>
                                    <th class="text-right font-semibold px-3 py-2">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($qa['rows'] as $row)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700">{{ $row['label'] }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-700">{{ number_format($row['count']) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-700">Rp {{ number_format($row['avg'], 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-gray-700">Rp {{ number_format($row['sum'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="overflow-x-auto">
                        <table class="dashboard-table w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="text-left font-semibold px-3 py-2">Jawaban</th>
                                    <th class="text-right font-semibold px-3 py-2">Jumlah</th>
                                    <th class="text-right font-semibold px-3 py-2">% Terjawab</th>
                                    <th class="text-left font-semibold px-3 py-2 w-[34%]">Distribusi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($qa['rows'] as $row)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700">{{ $row['label'] }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-700">{{ number_format($row['count']) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-500">{{ $row['pct'] }}%</td>
                                    <td class="px-3 py-2">
                                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-sky-500 rounded-full" style="width: {{ $row['pct'] }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="dashboard-table-empty text-center text-gray-400">Belum ada pilihan yang terisi.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @endif
                </article>
            @endforeach
        </div>
    @else
    <div class="bg-white rounded-xl p-10 border border-gray-100 shadow-sm text-center">
        <p class="text-sm font-medium text-gray-400">Belum ada data untuk ditampilkan</p>
        <p class="text-xs text-gray-300 mt-1">Input survey untuk melihat tabulasi analitik per pertanyaan.</p>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.initSearchableSelect !== 'function') return;

    const inputClass = 'dashboard-filter-select w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none';

    window.initSearchableSelect('select[name="city_id"]', {
        inputClass,
        placeholder: 'Ketik kab/kota...',
    });
    window.initSearchableSelect('select[name="district_id"]', {
        inputClass,
        placeholder: 'Ketik distrik...',
    });
    window.initSearchableSelect('select[name="village_id"]', {
        inputClass,
        placeholder: 'Ketik kelurahan...',
    });
});
</script>

@if($chartItems->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const charts = @json($chartItems);
    const palette = ['#0EA5E9', '#10B981', '#F59E0B', '#EF4444', '#64748B', '#14B8A6', '#F97316', '#84CC16', '#06B6D4'];

    charts.forEach(function (item) {
        const canvas = document.getElementById('chart-' + item.key);
        if (!canvas || typeof Chart === 'undefined') return;

        const isDoughnut = item.type === 'doughnut';
        const datasets = item.datasets
            ? item.datasets.map(function (dataset, index) {
                return {
                    label: dataset.label,
                    data: dataset.data,
                    backgroundColor: palette[index % palette.length],
                    borderRadius: 5,
                    borderWidth: 0,
                    maxBarThickness: 34
                };
            })
            : [{
                label: 'Jumlah',
                data: item.data,
                backgroundColor: isDoughnut ? palette.slice(0, item.labels.length) : '#0EA5E9',
                borderRadius: isDoughnut ? 0 : 5,
                borderWidth: 0,
                maxBarThickness: 34
            }];

        new Chart(canvas, {
            type: item.type,
            data: {
                labels: item.labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: isDoughnut ? '62%' : undefined,
                plugins: {
                    legend: {
                        display: isDoughnut || Boolean(item.datasets),
                        position: 'bottom',
                        labels: {
                            boxWidth: 7,
                            color: '#64748B',
                            font: { size: 9 },
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 8
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const values = context.dataset.data || [];
                                const total = values.reduce((sum, value) => sum + Number(value || 0), 0);
                                const value = Number(context.raw || 0);
                                const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return ' ' + context.dataset.label + ': ' + value + ' (' + pct + '%)';
                            }
                        }
                    }
                },
                scales: isDoughnut ? {} : {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748B', font: { size: 9 }, maxRotation: 25, minRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#F1F5F9' },
                        ticks: { color: '#94A3B8', precision: 0, font: { size: 9 } }
                    }
                }
            }
        });
    });
});
</script>
@endif
@endpush
