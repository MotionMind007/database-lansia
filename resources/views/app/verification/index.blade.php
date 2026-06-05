@extends('layouts.app')

@section('title', 'Verifikasi Survey — Lansia Papua')
@section('page-title', 'Data Masuk Verifikasi')

@section('content')
<div class="max-w-full">

    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Data Masuk Verifikasi</h2>
            <p class="text-xs text-gray-400 mt-0.5">Data survey yang menunggu verifikasi ({{ $responses->total() }} data)</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 text-xs px-4 py-3 rounded-xl">
        <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-xs px-4 py-3 rounded-xl">
        {{ session('error') }}
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/50">
                        <th class="text-left px-4 py-3 font-semibold text-gray-500">No.</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500">No. Kuesioner</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500">Nama Responden</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500">Wilayah</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500">Surveyor</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500">Tgl Submit</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500">Status</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($responses as $i => $resp)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-3 text-gray-400">{{ $responses->firstItem() + $i }}</td>
                        <td class="px-4 py-3 font-mono text-gray-700">{{ $resp->questionnaire_number }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $resp->respondent?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $resp->region?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $resp->surveyor?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $resp->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.65rem] font-semibold {{ $resp->status === 'submitted' ? 'bg-yellow-50 text-yellow-700' : 'bg-orange-50 text-orange-700' }}">
                                {{ $resp->status === 'submitted' ? 'Submitted' : 'Perlu Revisi' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('app.verification.show', $resp->id) }}" class="inline-flex items-center gap-1 text-sky-500 hover:text-sky-600 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Verifikasi
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                            <p class="text-sm font-medium">Tidak ada data yang menunggu verifikasi</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $responses->links() }}
        </div>
    </div>
</div>
@endsection
