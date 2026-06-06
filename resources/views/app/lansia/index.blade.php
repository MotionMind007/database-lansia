@extends('layouts.app')

@section('title', 'Data Lansia — Lansia Papua')
@section('page-title', 'Data Lansia')

@section('content')
<div class="max-w-full">

    <!-- Header row -->
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Data Lansia</h2>
            <p class="text-xs text-gray-400 mt-0.5">Total: {{ $responses->total() }} responden</p>
        </div>
        <a href="{{ route('app.survey.create') }}" class="inline-flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white text-xs font-semibold px-4 py-2.5 rounded-lg shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Input Survey
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 text-xs px-4 py-3 rounded-xl">
        <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    <!-- Filters -->
    <form method="GET" action="{{ route('app.lansia.index') }}">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-[0.68rem] text-gray-500 font-medium mb-1">Wilayah</label>
                    <div class="relative" data-region-search data-search-url="{{ route('app.wilayah.villages.search') }}">
                        <input type="hidden" name="region_id" value="{{ $selectedRegion?->id }}" data-region-value>
                        <input type="text"
                               value="{{ $selectedRegion ? collect([$selectedRegion->name, $selectedRegion->parent?->name, $selectedRegion->parent?->parent?->name])->filter()->join(' / ') : '' }}"
                               placeholder="Ketik nama kelurahan, distrik, atau kab/kota"
                               autocomplete="off"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none"
                               data-region-input>
                        <div class="searchable-select-menu left-0 right-0 top-full mt-1" data-region-results></div>
                    </div>
                </div>

                @if(auth()->user()->hasAnyRole(['administrator', 'super admin', 'super_admin']))
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-[0.68rem] text-gray-500 font-medium mb-1">Surveyor</label>
                    <select name="surveyor_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none">
                        <option value="">Semua Surveyor</option>
                        @foreach($surveyors as $s)
                        <option value="{{ $s->id }}" {{ request('surveyor_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="flex-1 min-w-[140px]">
                    <label class="block text-[0.68rem] text-gray-500 font-medium mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none">
                        <option value="">Semua Status</option>
                        @foreach(\App\Models\SurveyResponse::statusLabels() as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-[160px]">
                    <label class="block text-[0.68rem] text-gray-500 font-medium mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama atau No. Kuesioner"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none" />
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white text-xs font-medium px-4 py-2 rounded-lg transition-colors cursor-pointer">Filter</button>
                    <a href="{{ route('app.lansia.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-medium px-4 py-2 rounded-lg transition-colors">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/50">
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider w-8">No.</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">No. Kuesioner</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Umur</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Kelamin</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Wilayah</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Surveyor</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Tgl Wawancara</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($responses as $i => $resp)
                    @php
                        $statusColors = [
                            'draft'         => 'bg-gray-100 text-gray-600',
                            'submitted'     => 'bg-yellow-50 text-yellow-700',
                            'need_revision' => 'bg-orange-50 text-orange-700',
                            'verified'      => 'bg-green-50 text-green-700',
                            'rejected'      => 'bg-red-50 text-red-700',
                        ];
                        $statusColor = $statusColors[$resp->status] ?? 'bg-gray-100 text-gray-600';
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-4 py-3 text-gray-400">{{ $responses->firstItem() + $i }}</td>
                        <td class="px-4 py-3 font-mono text-gray-700">{{ $resp->questionnaire_number }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $resp->respondent?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $resp->respondent?->age ?? '—' }} th</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $resp->respondent?->gender === 'male' ? 'L' : ($resp->respondent?->gender === 'female' ? 'P' : '—') }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $resp->region?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $resp->surveyor?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $resp->interview_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[0.65rem] font-semibold {{ $statusColor }}">
                                {{ $resp->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('app.lansia.show', $resp->id) }}" class="text-sky-500 hover:text-sky-600 font-medium">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center text-gray-400">
                            <svg class="w-10 h-10 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p class="text-sm font-medium text-gray-400">Belum ada data lansia</p>
                            <p class="text-xs text-gray-300 mt-1">Mulai input survey untuk menambah data</p>
                            <a href="{{ route('app.survey.create') }}" class="inline-block mt-3 text-xs text-sky-500 font-medium hover:underline">+ Input Survey Baru</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
            <span class="text-xs text-gray-400">
                Menampilkan {{ $responses->firstItem() ?? 0 }}–{{ $responses->lastItem() ?? 0 }} dari {{ $responses->total() }} data
            </span>
            <div class="flex items-center gap-1 text-xs">
                {{ $responses->links() }}
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-region-search]').forEach(function (root) {
    const input = root.querySelector('[data-region-input]');
    const hidden = root.querySelector('[data-region-value]');
    const results = root.querySelector('[data-region-results]');
    const url = root.dataset.searchUrl;
    let controller = null;
    let timer = null;

    function close() {
        results.classList.remove('open');
        results.innerHTML = '';
    }

    function render(items) {
        results.innerHTML = '';

        if (items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'searchable-select-empty';
            empty.textContent = 'Data wilayah tidak ditemukan';
            results.appendChild(empty);
        } else {
            items.forEach(function (item) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'searchable-select-option';
                button.textContent = item.label;
                button.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    hidden.value = item.id;
                    input.value = item.label;
                    close();
                });
                results.appendChild(button);
            });
        }

        results.classList.add('open');
    }

    input.addEventListener('input', function () {
        hidden.value = '';
        window.clearTimeout(timer);

        const query = input.value.trim();
        if (query.length < 2) {
            close();
            return;
        }

        timer = window.setTimeout(function () {
            if (controller) {
                controller.abort();
            }

            controller = new AbortController();
            fetch(url + '?q=' + encodeURIComponent(query), {
                headers: { 'Accept': 'application/json' },
                signal: controller.signal,
            })
                .then(function (response) { return response.json(); })
                .then(render)
                .catch(function (error) {
                    if (error.name !== 'AbortError') {
                        close();
                    }
                });
        }, 220);
    });

    input.addEventListener('blur', function () {
        window.setTimeout(close, 120);
    });
});
</script>
@endpush
