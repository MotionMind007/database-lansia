@extends('layouts.app')

@section('title', 'Log Aktivitas - Lansia Papua')
@section('page-title', 'Log Aktivitas')

@section('content')
@php
    $decode = function ($value) {
        if (blank($value)) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    };

    $subjectLabel = function ($type, $id) {
        if (! $type && ! $id) {
            return '-';
        }

        $shortType = $type ? class_basename($type) : 'Data';

        return $shortType.($id ? ' #'.$id : '');
    };
@endphp

<div class="max-w-full space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Log Aktivitas</h2>
            <p class="text-xs text-gray-400 mt-0.5">Pantau aktivitas penting di sistem, termasuk perubahan data, user, dan operasi admin.</p>
        </div>
        <a href="{{ url('/admin') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-sky-500 hover:bg-sky-600 text-white text-xs font-semibold transition-colors">
            Buka Setting
        </a>
    </div>

    <form method="GET" action="{{ route('app.activity-logs.index') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-[0.68rem] text-gray-500 font-medium mb-1">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Deskripsi, nama, email"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none" />
            </div>
            <div>
                <label class="block text-[0.68rem] text-gray-500 font-medium mb-1">Event</label>
                <select name="event" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none">
                    <option value="">Semua Event</option>
                    @foreach($events as $event)
                        <option value="{{ $event }}" @selected(request('event') === $event)>{{ $event }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[0.68rem] text-gray-500 font-medium mb-1">Log Name</label>
                <select name="log_name" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-700 focus:border-sky-400 outline-none">
                    <option value="">Semua Log</option>
                    @foreach($logNames as $logName)
                        <option value="{{ $logName }}" @selected(request('log_name') === $logName)>{{ $logName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white text-xs font-medium px-4 py-2 rounded-lg transition-colors">Filter</button>
                <a href="{{ route('app.activity-logs.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-medium px-4 py-2 rounded-lg transition-colors">Reset</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Waktu</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">User</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Aktivitas</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Target</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        @php
                            $properties = $decode($log->properties ?? null);
                            $changes = $decode($log->attribute_changes ?? null);
                        @endphp
                        <tr class="hover:bg-gray-50/80 align-top">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                {{ \Illuminate\Support\Carbon::parse($log->created_at)->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-700">{{ $log->causer_name ?? 'System' }}</div>
                                <div class="text-[0.65rem] text-gray-400">{{ $log->causer_email ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ $log->description }}</div>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @if($log->event)
                                        <span class="px-2 py-0.5 rounded-full bg-sky-50 text-sky-600 text-[0.65rem] font-semibold">{{ $log->event }}</span>
                                    @endif
                                    @if($log->log_name)
                                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-[0.65rem] font-semibold">{{ $log->log_name }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                {{ $subjectLabel($log->subject_type, $log->subject_id) }}
                            </td>
                            <td class="px-4 py-3 min-w-[240px]">
                                @if(! empty($changes))
                                    <details>
                                        <summary class="cursor-pointer text-sky-600 font-semibold">Lihat perubahan</summary>
                                        <pre class="mt-2 max-w-lg whitespace-pre-wrap rounded-lg bg-gray-50 border border-gray-100 p-3 text-[0.65rem] text-gray-600">{{ json_encode($changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @elseif(! empty($properties))
                                    <details>
                                        <summary class="cursor-pointer text-sky-600 font-semibold">Lihat properti</summary>
                                        <pre class="mt-2 max-w-lg whitespace-pre-wrap rounded-lg bg-gray-50 border border-gray-100 p-3 text-[0.65rem] text-gray-600">{{ json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-400">
                                Belum ada log aktivitas untuk filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-100">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
