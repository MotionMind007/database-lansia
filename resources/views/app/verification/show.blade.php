@extends('layouts.app')

@section('title', 'Verifikasi Data — Lansia Papua')
@section('page-title', 'Verifikasi Data')

@section('content')
@php
    $resp = $response;
    $respondent = $resp->respondent;
    $answers = $resp->answers->first()?->answer_json ?? [];
@endphp
<div class="max-w-5xl">

    <a href="{{ route('app.verification.index') }}" class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-sky-500 mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali
    </a>

    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-sky-100 flex items-center justify-center text-sky-500 text-lg font-bold shrink-0">
                {{ strtoupper(substr($respondent->full_name ?? 'X', 0, 2)) }}
            </div>
            <div class="flex-1">
                <h2 class="text-base font-bold text-gray-800">{{ $respondent->full_name }}</h2>
                <div class="flex flex-wrap items-center gap-3 mt-0.5 text-xs text-gray-500">
                    <span>No: {{ $resp->questionnaire_number }}</span>
                    <span>|</span>
                    <span>Usia: {{ $respondent->age }} th</span>
                    <span>|</span>
                    <span>{{ $respondent->gender_label }}</span>
                    <span>|</span>
                    <span>{{ $resp->region?->name }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- Data ringkasan (2/3 width) -->
        <div class="md:col-span-2 space-y-4">
            <!-- Identitas -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <h3 class="text-xs font-bold text-gray-600 mb-3 uppercase tracking-wider">Identitas Responden</h3>
                <div class="grid grid-cols-2 gap-x-6 gap-y-1.5 text-xs">
                    @foreach([
                        'Nama' => $respondent->full_name,
                        'Pekerjaan' => $respondent->occupation ?? '-',
                        'Pendidikan' => $respondent->education ?? '-',
                        'Agama' => $respondent->religion ?? '-',
                        'Suku' => $respondent->ethnicity ?? '-',
                        'Status' => $respondent->citizenship_status ?? '-',
                        'Alamat' => $respondent->address ?? '-',
                        'Kontak' => $respondent->phone ?? '-',
                    ] as $label => $val)
                    <div class="flex gap-2 py-1 border-b border-gray-50">
                        <span class="text-gray-400 w-24 shrink-0">{{ $label }}</span>
                        <span class="text-gray-700 font-medium">{{ $val }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Jawaban kuesioner -->
            @if(!empty($answers))
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <h3 class="text-xs font-bold text-gray-600 mb-3 uppercase tracking-wider">Jawaban Kuesioner</h3>
                @php
                    $labelMap = [
                        'pola_konsumsi' => 'Bagaimana pola (kombinasi konsumsi) keluarga sehari-hari?',
                        'konsumsi_hari' => 'Berapa rata-rata konsumsi keluarga sehari-hari?',
                        'cara_masak' => 'Bagaimana cara penyajian (pengolahan) bahan konsumsi?',
                        'bahan_bakar' => 'Bahan bakar apa yang digunakan untuk memasak?',
                        'bansos_sembako' => 'Apakah pernah mendapatkan bantuan sembako (bahan pokok)?',
                        'pemberi_sembako' => 'Siapa yang memberi bantuan sembako tersebut?',
                        'keluhan_kes' => 'Apakah dalam kurun waktu 1 bulan terakhir mengalami keluhan kesehatan?',
                        'keluhan_detail' => 'Apa keluhan kesehatan yang dialami?',
                        'periksa_rutin' => 'Apakah dalam kurun waktu 6 bulan terakhir pernah melakukan pemeriksaan rutin?',
                        'frek_periksa' => 'Seberapa sering melakukan pemeriksaan rutin?',
                        'jangkau_kes' => 'Berapa lama waktu yang dibutuhkan untuk menjangkau layanan kesehatan?',
                        'transport_kes' => 'Sarana transportasi apa yang digunakan untuk mendapatkan pelayanan kesehatan?',
                        'biaya_kes' => 'Dari mana sumber biaya untuk mendapatkan akses layanan kesehatan?',
                        'masalah_kes' => 'Apa masalah utama dalam peningkatan layanan kesehatan lansia?',
                        'status_rumah' => 'Bagaimana status kepemilikan rumah yang ditempati saat ini?',
                        'jenis_rumah' => 'Bagaimana jenis konstruksi perumahan yang ditempati saat ini?',
                        'sumber_air' => 'Darimana sumber ketersediaan air bersih yang digunakan?',
                        'sistem_air' => 'Bagaimana sistem penyediaan air bersih?',
                        'mck' => 'Apakah terdapat ketersediaan sarana MCK?',
                        'penerangan' => 'Sumber penerangan apa yang digunakan?',
                        'lama_penerangan' => 'Berapa lama pelayanan sumber penerangan dari pemerintah?',
                        'media_info' => 'Media apa yang digunakan dalam mendukung akses informasi?',
                        'punya_hp' => 'Apakah menggunakan handphone dalam membantu berkomunikasi?',
                        'media_alternatif' => 'Melalui media alternatif apa saudara mengakses informasi?',
                        'bansos' => 'Apakah pernah mendapat bantuan sosial dari pemerintah?',
                        'jenis_bansos' => 'Jenis bantuan sosial apa yang pernah diterima?',
                        'jamsosial' => 'Apakah pernah mendapatkan program jaminan sosial dari pemerintah?',
                        'jenis_jamsosial' => 'Jenis jaminan sosial apa yang pernah diterima?',
                        'pelatihan_lansia' => 'Apakah pernah dilibatkan dalam pelatihan khusus lansia?',
                        'jenis_pelatihan' => 'Pelatihan seperti apa yang pernah diikuti?',
                        'masalah_linsos' => 'Apa masalah utama dalam implementasi program perlindungan sosial bagi lansia?',
                        'kunjungi' => 'Seberapa sering mengunjungi keluarga atau teman terdekat?',
                        'perkumpulan' => 'Apakah memiliki kelompok perkumpulan atau komunitas?',
                        'rapat_warga' => 'Apakah pernah terlibat dalam pertemuan warga tingkat kampung?',
                        'pemilu' => 'Apakah pernah berpartisipasi pada pemilihan umum (PILKADA/PILPRES)?',
                        'pengeluaran_total' => 'Berapa rata-rata pengeluaran keluarga dalam satu bulan?',
                        'penghasilan' => 'Berapa rata-rata penghasilan kepala keluarga dalam satu bulan?',
                    ];
                @endphp
                <div class="space-y-2 text-xs">
                    @foreach($answers as $key => $val)
                    @if($val !== null && $val !== '' && $val !== [])
                    <div class="py-2 border-b border-gray-50">
                        <div class="text-gray-500 mb-0.5">{{ $labelMap[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}</div>
                        <div class="text-gray-800 font-medium">{{ is_array($val) ? implode(', ', $val) : $val }}</div>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Dokumen Pendukung -->
            @php $docs = $resp->documents ?? collect(); @endphp
            @if($docs->count() > 0)
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <h3 class="text-xs font-bold text-gray-600 mb-3 uppercase tracking-wider">Dokumen Pendukung</h3>
                <div class="grid grid-cols-2 gap-3">
                    @foreach($docs as $doc)
                    <div class="border border-gray-100 rounded-lg p-3 flex items-center gap-3">
                        <div class="w-10 h-10 bg-sky-50 rounded-lg flex items-center justify-center shrink-0">
                            @if(str_contains($doc->mime_type, 'image'))
                            <img src="{{ route('app.documents.show', $doc) }}" class="w-10 h-10 rounded-lg object-cover" />
                            @else
                            <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-gray-700">{{ $doc->type_label }}</div>
                            <div class="text-[0.6rem] text-gray-400 truncate">{{ $doc->file_name }}</div>
                        </div>
                        <a href="{{ route('app.documents.show', $doc) }}" target="_blank" class="text-[0.65rem] text-sky-500 hover:text-sky-600 font-medium shrink-0">Lihat</a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Riwayat verifikasi sebelumnya -->
            @if($resp->verificationLogs->count() > 0)
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <h3 class="text-xs font-bold text-gray-600 mb-3 uppercase tracking-wider">Riwayat Verifikasi</h3>
                <div class="space-y-2">
                    @foreach($resp->verificationLogs as $log)
                    <div class="flex gap-2 p-2 bg-gray-50 rounded-lg text-xs">
                        <div class="w-2 h-2 mt-1 rounded-full {{ $log->status === 'verified' ? 'bg-green-500' : ($log->status === 'need_revision' ? 'bg-orange-400' : 'bg-red-400') }} shrink-0"></div>
                        <div>
                            <span class="font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $log->status)) }}</span>
                            <span class="text-gray-400 ml-2">{{ $log->verified_at->format('d/m/Y H:i') }}</span>
                            <span class="text-gray-400 ml-1">oleh {{ $log->verifier?->name }}</span>
                            @if($log->note)<p class="text-gray-500 mt-0.5">{{ $log->note }}</p>@endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Panel verifikasi (1/3 width, sticky) -->
        <div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sticky top-20">
                <h3 class="text-xs font-bold text-gray-600 mb-4 uppercase tracking-wider">Keputusan Verifikasi</h3>

                <form method="POST" action="{{ route('app.verification.verify', $resp->id) }}">
                    @csrf

                    <div class="space-y-2 mb-4">
                        <label class="flex items-center gap-2 p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:border-green-300 hover:bg-green-50/50 transition-colors has-[:checked]:border-green-400 has-[:checked]:bg-green-50">
                            <input type="radio" name="status" value="verified" class="accent-green-500" required />
                            <div>
                                <div class="text-xs font-semibold text-gray-700">Verified / Clean</div>
                                <div class="text-[0.6rem] text-gray-400">Data sesuai, lanjut ke pengolahan</div>
                            </div>
                        </label>

                        <label class="flex items-center gap-2 p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:border-orange-300 hover:bg-orange-50/50 transition-colors has-[:checked]:border-orange-400 has-[:checked]:bg-orange-50">
                            <input type="radio" name="status" value="need_revision" class="accent-orange-500" />
                            <div>
                                <div class="text-xs font-semibold text-gray-700">Need Revision</div>
                                <div class="text-[0.6rem] text-gray-400">Perlu perbaikan, kembalikan ke Surveyor</div>
                            </div>
                        </label>

                        <label class="flex items-center gap-2 p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:border-red-300 hover:bg-red-50/50 transition-colors has-[:checked]:border-red-400 has-[:checked]:bg-red-50">
                            <input type="radio" name="status" value="rejected" class="accent-red-500" />
                            <div>
                                <div class="text-xs font-semibold text-gray-700">Survey Ulang / Ditolak</div>
                                <div class="text-[0.6rem] text-gray-400">Data tidak bisa dilanjutkan</div>
                            </div>
                        </label>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs text-gray-600 font-medium mb-1">Catatan Verifikasi</label>
                        <textarea name="note" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs outline-none focus:border-sky-400 resize-none" placeholder="Wajib diisi jika Need Revision atau Ditolak (min 10 karakter)"></textarea>
                        @error('note')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 text-white text-xs font-semibold py-2.5 rounded-lg transition-colors cursor-pointer">
                        Simpan Keputusan
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
