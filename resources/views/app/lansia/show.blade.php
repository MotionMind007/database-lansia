@extends('layouts.app')

@section('title', 'Detail Data Lansia — Lansia Papua')
@section('page-title', 'Detail Data Lansia')

@push('styles')
<style>
    .tab-btn { padding: 0.65rem 1.25rem; font-size: 0.8rem; font-weight: 500; color: #6B7280; border-bottom: 2px solid transparent; white-space: nowrap; transition: all 0.15s; cursor: pointer; background: none; border-top: none; border-left: none; border-right: none; }
    .tab-btn:hover { color: #0EA5E9; }
    .tab-btn.active { color: #0EA5E9; border-bottom-color: #0EA5E9; font-weight: 600; }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }
    .field-row { display: flex; gap: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid #f9fafb; }
    .field-label { font-size: 0.72rem; color: #6B7280; width: 180px; flex-shrink: 0; }
    .field-value { font-size: 0.72rem; color: #1F2937; font-weight: 500; }
    .param-card { background: #f9fafb; border-radius: 12px; padding: 1rem 1.25rem; }
    .param-card-title { font-size: 0.72rem; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem; }
</style>
@endpush

@php
    $resp = $response;
    $respondent = $resp->respondent;
    $initials = strtoupper(substr($respondent->full_name ?? 'X', 0, 2));
    $statusColors = [
        'draft' => ['bg-gray-50 text-gray-600', 'bg-gray-500'],
        'submitted' => ['bg-yellow-50 text-yellow-700', 'bg-yellow-500'],
        'need_revision' => ['bg-orange-50 text-orange-700', 'bg-orange-500'],
        'verified' => ['bg-green-50 text-green-600', 'bg-green-500'],
        'rejected' => ['bg-red-50 text-red-700', 'bg-red-500'],
    ];
    $statusBadge = $statusColors[$resp->status] ?? $statusColors['draft'];
    $answers = $resp->answers->first()?->answer_json ?? [];

    // Label mappings for coded values
    $labelMaps = [
        'pola_konsumsi' => [
            'a' => 'Nasi/Ubi',
            'b' => 'Nasi/Ubi dan Sayur',
            'c' => 'Nasi/Ubi, Sayur, Daging/Ikan/Telur',
            'd' => 'Nasi/Ubi, Sayur, Daging/Ikan/Telur dan Susu',
            'e' => 'Nasi/Ubi, Sayur, Daging/Ikan/Telur, Susu dan Buah',
        ],
        'konsumsi_hari' => ['1' => '1 Kali Sehari', '2' => '2 Kali Sehari', '3' => '3 Kali Sehari'],
        'keluhan_kes' => ['ya' => 'Ya', 'tidak' => 'Tidak'],
        'periksa_rutin' => ['ya' => 'Ya', 'tidak' => 'Tidak'],
        'jangkau_kes' => ['kurang_30' => '< 30 Menit', '30_60' => '30-60 Menit', '1_5_jam' => '1-5 Jam', 'lebih_5jam' => '> 5 Jam'],
        'status_rumah' => ['milik_sendiri' => 'Milik Sendiri', 'sewa_kontrak' => 'Sewa/Kontrak', 'rumah_dinas' => 'Rumah Dinas', 'bantuan_pemerintah' => 'Bantuan Pemerintah', 'lainnya' => 'Lainnya'],
        'jenis_rumah' => ['permanen' => 'Permanen', 'semi_permanen' => 'Semi Permanen', 'kayu_papan' => 'Kayu/Papan', 'kayu_tanah' => 'Kayu/Tanah', 'rumah_adat' => 'Rumah Adat', 'lainnya' => 'Lainnya'],
        'mck' => ['pribadi' => 'Pribadi', 'umum' => 'Umum', 'tidak_ada' => 'Tidak Ada'],
        'lama_penerangan' => ['24jam' => '24 Jam', '12jam' => '12 Jam', '6jam' => '6 Jam', 'kurang_6jam' => '< 6 Jam', 'tidak_ada' => 'Tidak Ada'],
        'punya_hp' => ['ya' => 'Ya', 'tidak' => 'Tidak'],
        'bansos' => ['pernah' => 'Pernah', 'tidak' => 'Tidak'],
        'jamsosial' => ['pernah' => 'Pernah', 'tidak' => 'Tidak'],
        'pelatihan_lansia' => ['pernah' => 'Pernah', 'tidak' => 'Tidak'],
        'bansos_sembako' => ['pernah' => 'Pernah', 'tidak' => 'Tidak'],
        'kunjungi' => ['on' => 'Ya', 'off' => 'Tidak'],
    ];

    // Helper to resolve label
    $getLabel = function($key, $value) use ($labelMaps) {
        if (is_array($value)) {
            $mapped = array_map(function($v) use ($key, $labelMaps) {
                if (isset($labelMaps[$key][$v])) return $labelMaps[$key][$v];
                return ucwords(str_replace('_', ' ', $v));
            }, $value);
            return implode(', ', $mapped);
        }
        if (isset($labelMaps[$key][$value])) return $labelMaps[$key][$value];
        return ucwords(str_replace('_', ' ', $value));
    };
@endphp

@section('content')
<div class="max-w-6xl">

    <!-- Back -->
    <a href="{{ route('app.lansia.index') }}" class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-sky-500 mb-5 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali ke Data Lansia
    </a>

    <!-- Profile header -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-5">
                @if($respondent->photo_path)
                <img src="{{ route('app.respondents.photo', $respondent) }}" alt="{{ $respondent->full_name }}" class="w-20 h-20 rounded-xl object-cover shrink-0" />
                @else
                <div class="w-20 h-20 rounded-xl bg-sky-100 flex items-center justify-center text-sky-500 text-xl font-bold shrink-0">
                    {{ $initials }}
                </div>
                @endif
                <div>
                    <h2 class="text-lg font-bold text-gray-800">{{ $respondent->full_name }}</h2>
                    <div class="flex flex-wrap items-center gap-3 mt-1">
                        <span class="text-xs text-gray-500">No. Kuesioner: <span class="font-medium text-gray-700">{{ $resp->questionnaire_number }}</span></span>
                        <span class="text-gray-300">|</span>
                        <span class="text-xs text-gray-500">Usia: <span class="font-medium text-gray-700">{{ $respondent->age }} Tahun</span></span>
                        <span class="text-gray-300">|</span>
                        <span class="text-xs text-gray-500">Kelamin: <span class="font-medium text-gray-700">{{ $respondent->gender_label }}</span></span>
                    </div>
                    <div class="mt-1.5">
                        <span class="inline-flex items-center gap-1 {{ $statusBadge[0] }} text-[0.68rem] font-semibold px-2.5 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge[1] }}"></span>
                            {{ $resp->status_label }}
                        </span>
                    </div>
                </div>
            </div>
            @if(in_array($resp->status, ['need_revision', 'draft']) && (auth()->user()->hasRole('administrator') || $resp->surveyor_id === auth()->id()))
            <a href="{{ route('app.survey.edit', $resp->id) }}"
               class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold px-4 py-2.5 rounded-lg shadow-sm transition-colors cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Revisi Data
            </a>
            @endif
        </div>
        <div class="text-[0.65rem] text-gray-400 mt-3">
            Surveyor: {{ $resp->surveyor?->name }} &nbsp;|&nbsp;
            Tgl Wawancara: {{ $resp->interview_date?->format('d F Y') }} &nbsp;|&nbsp;
            Wilayah: {{ $resp->region?->name }}
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="border-b border-gray-100">
            <div class="flex px-5">
                <button class="tab-btn active" onclick="switchTab('data-pribadi', this)">Data Pribadi</button>
                <button class="tab-btn" onclick="switchTab('data-parameter', this)">Data Parameter</button>
                <button class="tab-btn" onclick="switchTab('dokumen', this)">Dokumen</button>
                <button class="tab-btn" onclick="switchTab('verifikasi', this)">Verifikasi</button>
            </div>
        </div>

        <div class="p-6">

            {{-- TAB 1: DATA PRIBADI --}}
            <div id="tab-data-pribadi" class="tab-panel active">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10">
                    <div>
                        @foreach([
                            'Nama Lengkap' => $respondent->full_name,
                            'No. Kuesioner' => $resp->questionnaire_number,
                            'Jenis Kelamin' => $respondent->gender_label,
                            'Umur' => ($respondent->age ?? '-') . ' Tahun',
                            'Pendidikan' => $respondent->education ?? '-',
                            'Pekerjaan' => $respondent->occupation ?? '-',
                            'Status OAP' => $respondent->citizenship_status ?? '-',
                        ] as $label => $value)
                        <div class="field-row">
                            <span class="field-label">{{ $label }}</span>
                            <span class="field-value">: {{ $value }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div>
                        @foreach([
                            'Alamat' => $respondent->address ?? '-',
                            'No. Kontak' => $respondent->phone ?? '-',
                            'Agama' => $respondent->religion ?? '-',
                            'Suku' => $respondent->ethnicity ?? '-',
                            'Status dalam RT' => $respondent->household_status ?? '-',
                            'Wilayah' => $resp->region?->name ?? '-',
                            'Tgl Wawancara' => $resp->interview_date?->format('d/m/Y') ?? '-',
                        ] as $label => $value)
                        <div class="field-row">
                            <span class="field-label">{{ $label }}</span>
                            <span class="field-value">: {{ $value }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Profil Rumah Tangga --}}
                @if(isset($answers['jml_anggota']) || $respondent->household_status)
                <div class="mt-6">
                    <h4 class="text-xs font-bold text-gray-600 mb-3 uppercase tracking-wider">Profil Rumah Tangga</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10">
                        <div>
                            @if($respondent->household_status)
                            <div class="field-row">
                                <span class="field-label">Status dalam Rumah Tangga</span>
                                <span class="field-value">: {{ $respondent->household_status }}</span>
                            </div>
                            @endif
                            @if(isset($answers['jml_anggota']))
                            <div class="field-row">
                                <span class="field-label">Jumlah Anggota Keluarga</span>
                                <span class="field-value">: {{ $answers['jml_anggota'] }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                @if($respondent->familyMembers->count() > 0)
                <div class="mt-6">
                    <h4 class="text-xs font-bold text-gray-600 mb-3 uppercase tracking-wider">Identitas Anggota Keluarga Lansia</h4>
                    <div class="overflow-x-auto border border-gray-100 rounded-xl">
                        <table class="w-full text-xs">
                            <thead><tr class="bg-gray-50/80">
                                <th class="text-left px-3 py-2.5 font-semibold text-gray-500">No.</th>
                                <th class="text-left px-3 py-2.5 font-semibold text-gray-500">Nama</th>
                                <th class="text-left px-3 py-2.5 font-semibold text-gray-500">Jenis Kelamin</th>
                                <th class="text-left px-3 py-2.5 font-semibold text-gray-500">Umur</th>
                                <th class="text-left px-3 py-2.5 font-semibold text-gray-500">Status</th>
                                <th class="text-left px-3 py-2.5 font-semibold text-gray-500">Pendidikan</th>
                                <th class="text-left px-3 py-2.5 font-semibold text-gray-500">Status KTP</th>
                            </tr></thead>
                            <tbody>
                                @foreach($respondent->familyMembers as $i => $fm)
                                <tr class="border-t border-gray-50">
                                    <td class="px-3 py-2.5">{{ $i + 1 }}</td>
                                    <td class="px-3 py-2.5 font-medium">{{ $fm->name }}</td>
                                    <td class="px-3 py-2.5">{{ $fm->gender === 'Laki-laki' ? 'Laki-laki' : ($fm->gender === 'Perempuan' ? 'Perempuan' : ($fm->gender === 'male' ? 'Laki-laki' : 'Perempuan')) }}</td>
                                    <td class="px-3 py-2.5">{{ $fm->age ?? '-' }} Tahun</td>
                                    <td class="px-3 py-2.5">{{ $fm->status ?? '-' }}</td>
                                    <td class="px-3 py-2.5">{{ $fm->education ?? '-' }}</td>
                                    <td class="px-3 py-2.5">{{ $fm->ktp_status ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>

            {{-- TAB 2: DATA PARAMETER --}}
            <div id="tab-data-parameter" class="tab-panel">
                @if(!empty($answers))
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-4">
                        @if(isset($answers['status_rumah']) || isset($answers['jenis_rumah']))
                        <div class="param-card">
                            <div class="param-card-title">Karakteristik Rumah</div>
                            @foreach(['status_rumah' => 'Status Kepemilikan', 'jenis_rumah' => 'Jenis Konstruksi', 'mck' => 'Fasilitas MCK', 'lama_penerangan' => 'Lama Penerangan'] as $key => $label)
                                @if(isset($answers[$key]))
                                <div class="flex justify-between py-1.5 border-b border-white text-xs">
                                    <span class="text-gray-500">{{ $label }}</span>
                                    <span class="text-gray-800 font-medium">{{ $getLabel($key, $answers[$key]) }}</span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @endif

                        @if(isset($answers['pola_konsumsi']) || isset($answers['konsumsi_hari']))
                        <div class="param-card">
                            <div class="param-card-title">Konsumsi Pangan</div>
                            @foreach(['pola_konsumsi' => 'Pola Konsumsi', 'konsumsi_hari' => 'Konsumsi Per Hari', 'bansos_sembako' => 'Bantuan Sembako'] as $key => $label)
                                @if(isset($answers[$key]))
                                <div class="flex justify-between py-1.5 border-b border-white text-xs">
                                    <span class="text-gray-500">{{ $label }}</span>
                                    <span class="text-gray-800 font-medium">{{ $getLabel($key, $answers[$key]) }}</span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @endif

                        @if(isset($answers['penghasilan']) || isset($answers['pengeluaran_total']))
                        <div class="param-card">
                            <div class="param-card-title">Ekonomi</div>
                            @if(isset($answers['penghasilan']))
                            <div class="flex justify-between py-1.5 border-b border-white text-xs">
                                <span class="text-gray-500">Rata-rata Penghasilan/Bulan</span>
                                <span class="text-gray-800 font-medium">{{ $answers['penghasilan'] }}</span>
                            </div>
                            @endif
                            @if(isset($answers['pengeluaran_total']))
                            <div class="flex justify-between py-1.5 border-b border-white text-xs">
                                <span class="text-gray-500">Rata-rata Pengeluaran/Bulan</span>
                                <span class="text-gray-800 font-medium">{{ $answers['pengeluaran_total'] }}</span>
                            </div>
                            @endif
                            @if(isset($answers['pengeluaran_items']) && is_array($answers['pengeluaran_items']))
                            <div class="mt-2 pt-2 border-t border-gray-200/50">
                                <div class="text-[0.65rem] text-gray-500 font-semibold mb-1.5">Rincian Pengeluaran:</div>
                                @foreach($answers['pengeluaran_items'] as $item => $jumlah)
                                @if($jumlah)
                                <div class="flex justify-between py-1 text-xs">
                                    <span class="text-gray-500">{{ $item }}</span>
                                    <span class="text-gray-800 font-medium">Rp {{ number_format((int)$jumlah, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>

                    <div class="space-y-4">
                        @if(isset($answers['keluhan_kes']) || isset($answers['periksa_rutin']))
                        <div class="param-card">
                            <div class="param-card-title">Kesehatan</div>
                            @foreach(['keluhan_kes' => 'Keluhan Kesehatan', 'periksa_rutin' => 'Pemeriksaan Rutin', 'jangkau_kes' => 'Waktu Jangkau Layanan'] as $key => $label)
                                @if(isset($answers[$key]))
                                <div class="flex justify-between py-1.5 border-b border-white text-xs">
                                    <span class="text-gray-500">{{ $label }}</span>
                                    <span class="text-gray-800 font-medium">{{ $getLabel($key, $answers[$key]) }}</span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @endif

                        @if(isset($answers['bansos']) || isset($answers['jamsosial']))
                        <div class="param-card">
                            <div class="param-card-title">Perlindungan Sosial</div>
                            @foreach(['bansos' => 'Bantuan Sosial', 'jenis_bansos' => 'Jenis Bantuan', 'jamsosial' => 'Jaminan Sosial', 'pelatihan_lansia' => 'Pelatihan Lansia'] as $key => $label)
                                @if(isset($answers[$key]))
                                <div class="flex justify-between py-1.5 border-b border-white text-xs">
                                    <span class="text-gray-500">{{ $label }}</span>
                                    <span class="text-gray-800 font-medium">{{ $getLabel($key, $answers[$key]) }}</span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @endif

                        @if(isset($answers['punya_hp']) || isset($answers['media_info']))
                        <div class="param-card">
                            <div class="param-card-title">Informasi & Komunikasi</div>
                            @foreach(['media_info' => 'Media Informasi', 'punya_hp' => 'Punya Handphone', 'media_alternatif' => 'Media Alternatif'] as $key => $label)
                                @if(isset($answers[$key]))
                                <div class="flex justify-between py-1.5 border-b border-white text-xs">
                                    <span class="text-gray-500">{{ $label }}</span>
                                    <span class="text-gray-800 font-medium">{{ $getLabel($key, $answers[$key]) }}</span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                @else
                <div class="text-center py-10 text-gray-400">
                    <p class="text-sm">Data parameter belum tersedia</p>
                </div>
                @endif
            </div>

            {{-- TAB 3: DOKUMEN --}}
            <div id="tab-dokumen" class="tab-panel">
                @php $docs = $resp->documents; @endphp
                @if($docs->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($docs as $doc)
                    <div class="border border-gray-200 rounded-xl p-4 text-center">
                        <div class="w-12 h-12 bg-sky-50 rounded-xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-sky-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="text-xs font-semibold text-gray-700 mb-1">{{ $doc->type_label }}</div>
                        <div class="text-[0.65rem] text-gray-400 truncate">{{ $doc->file_name }}</div>
                        <a href="{{ route('app.documents.show', $doc) }}" target="_blank" class="inline-block mt-2 text-[0.65rem] text-sky-500 hover:text-sky-600 font-medium">Lihat</a>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-10 text-gray-400">
                    <p class="text-sm">Belum ada dokumen yang diunggah</p>
                </div>
                @endif
            </div>

            {{-- TAB 4: VERIFIKASI --}}
            <div id="tab-verifikasi" class="tab-panel">
                <!-- Status -->
                <div class="flex items-center gap-3 p-4 {{ $statusBadge[0] }} border rounded-xl mb-6">
                    <div class="w-9 h-9 bg-white/80 rounded-full flex items-center justify-center">
                        @if($resp->status === 'verified')
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @else
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>
                    <div>
                        <div class="text-sm font-semibold">{{ $resp->status_label }}</div>
                        <div class="text-xs opacity-70">
                            @if($resp->verified_at) Diverifikasi {{ $resp->verified_at->diffForHumans() }}
                            @elseif($resp->submitted_at) Disubmit {{ $resp->submitted_at->diffForHumans() }}
                            @else Draft
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Riwayat -->
                @if($resp->verificationLogs->count() > 0)
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Riwayat Verifikasi</h3>
                <div class="space-y-3">
                    @foreach($resp->verificationLogs as $log)
                    <div class="flex gap-3 p-4 bg-gray-50 rounded-xl">
                        <div class="w-2 h-2 mt-1.5 rounded-full {{ $log->status === 'verified' ? 'bg-green-500' : ($log->status === 'need_revision' ? 'bg-orange-500' : 'bg-sky-500') }} shrink-0"></div>
                        <div>
                            <div class="text-xs font-semibold text-gray-700">{{ ucwords(str_replace('_', ' ', $log->status)) }} — {{ $log->verified_at->format('d M Y, H:i') }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">oleh {{ $log->verifier?->name ?? '-' }}</div>
                            @if($log->note)
                            <div class="text-xs text-gray-500 mt-1">{{ $log->note }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-xs text-gray-400">Belum ada riwayat verifikasi.</p>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function switchTab(name, btn) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + name).classList.add('active');
        btn.classList.add('active');
    }
</script>
@endpush
