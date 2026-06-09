@extends('layouts.app')

@section('title', 'Input Survey — Lansia Papua')
@section('page-title', 'Input Survey')

@include('app.survey.partials.styles')

@section('content')
<div class="max-w-4xl">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Input Survey Lansia</h2>
            <p class="text-xs text-gray-400 mt-0.5">Kuesioner Pendataan Lansia Provinsi Papua</p>
        </div>
        <a href="{{ route('app.lansia.index') }}" class="text-xs text-gray-400 hover:text-sky-500 transition-colors">Batal</a>
    </div>

    <!-- Step progress -->
    @include('app.survey.partials.steps')

    <!-- Form -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form method="POST" action="{{ route('app.survey.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="action" id="action-input" value="submit" />
        <div class="p-6">

            {{-- ─────────────────────────────────
                 STEP 1: DATA AWAL
            ───────────────────────────────── --}}
            <div class="form-section active" id="section-1">
                <div class="section-heading">Data Awal Survey</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Kabupaten/Kota <span class="text-red-400">*</span></label>
                        <select id="sel-city" class="form-input">
                            <option value="">-- Pilih Kabupaten/Kota --</option>
                            @foreach($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Distrik/Kecamatan <span class="text-red-400">*</span></label>
                        <select id="sel-district" class="form-input" disabled>
                            <option value="">-- Pilih Distrik --</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Kelurahan/Kampung <span class="text-red-400">*</span></label>
                        <select id="sel-village" name="region_id" class="form-input" disabled>
                            <option value="">-- Pilih Kelurahan --</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Nomor Kuesioner <span class="text-red-400">*</span></label>
                        <input type="text" name="questionnaire_number" class="form-input" placeholder="Contoh: KS-2024-001" />
                        @error('questionnaire_number')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label">Nama Enumerator/Surveyor <span class="text-red-400">*</span></label>
                        <input type="text" name="enumerator_name" class="form-input" value="{{ auth()->user()->name }}" />
                    </div>
                    <div>
                        <label class="form-label">Tanggal Wawancara <span class="text-red-400">*</span></label>
                        <input type="date" name="interview_date" class="form-input" value="{{ date('Y-m-d') }}" />
                    </div>
                </div>
            </div>

            {{-- ─────────────────────────────────
                 STEP 2: SECTION A — IDENTITAS
            ───────────────────────────────── --}}
            <div class="form-section" id="section-2">
                <div class="section-heading">A. Identitas Responden</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="form-label">Nama Lengkap <span class="text-red-400">*</span></label>
                        <input type="text" name="full_name" class="form-input" placeholder="Nama lengkap responden" />
                        @error('full_name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Jenis Kelamin <span class="text-red-400">*</span></label>
                        <div class="radio-group mt-1">
                            <label class="radio-opt"><input type="radio" name="gender" value="male"> Laki-laki</label>
                            <label class="radio-opt"><input type="radio" name="gender" value="female"> Perempuan</label>
                        </div>
                        @error('gender')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Umur <span class="text-red-400">*</span></label>
                        <input type="number" name="age" class="form-input" placeholder="Tahun" min="1" max="150" />
                        @error('age')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Pendidikan Terakhir</label>
                        <select name="education" class="form-input">
                            <option value="">-- Pilih --</option>
                            <option>Tidak Sekolah</option>
                            <option>SD</option><option>SMP</option>
                            <option>SMA</option><option>Perguruan Tinggi</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Pekerjaan</label>
                        <input type="text" name="occupation" class="form-input" placeholder="Pekerjaan responden" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Alamat Rumah</label>
                        <textarea name="address" class="form-input" rows="2" placeholder="Alamat lengkap"></textarea>
                    </div>
                    <div>
                        <label class="form-label">Nomor Kontak</label>
                        <input type="text" name="phone" class="form-input" placeholder="08xx-xxxx-xxxx" />
                    </div>
                    <div>
                        <label class="form-label">Agama</label>
                        <select name="religion" class="form-input">
                            <option value="">-- Pilih --</option>
                            <option>Islam</option><option>Kristen</option><option>Katolik</option>
                            <option>Hindu</option><option>Buddha</option><option>Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Suku</label>
                        <input type="text" name="ethnicity" class="form-input" placeholder="Suku responden" />
                    </div>
                    <div>
                        <label class="form-label">Status <span class="text-red-400">*</span></label>
                        <div class="radio-group mt-1">
                            <label class="radio-opt"><input type="radio" name="status_oap" value="OAP"> OAP</label>
                            <label class="radio-opt"><input type="radio" name="status_oap" value="Non_OAP"> Non OAP</label>
                            <label class="radio-opt"><input type="radio" name="status_oap" value="WNI"> WNI</label>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Status dalam Rumah Tangga</label>
                        <input type="text" name="household_status" class="form-input" placeholder="Contoh: Kepala Keluarga" />
                    </div>
                </div>
            </div>

            {{-- ─────────────────────────────────
                 STEP 3: SECTION B-C — RT & PENDIDIKAN
            ───────────────────────────────── --}}
            <div class="form-section" id="section-3">
                <div class="section-heading">B. Profil Rumah Tangga</div>
                <div class="mb-4">
                    <label class="form-label">B1. Berapa jumlah anggota keluarga yang tinggal bersama? <span class="text-red-400">*</span></label>
                    <div class="radio-group mt-1">
                        @foreach(['Tinggal Sendiri', '2 Orang', '3 Orang', '4 Orang', '5 Orang', 'Lebih dari 5 orang'] as $opt)
                        <label class="radio-opt"><input type="radio" name="jml_anggota" value="{{ $opt }}"> {{ $opt }}</label>
                        @endforeach
                    </div>
                </div>

                <div class="section-sub">B2. Identitas Anggota Keluarga Lansia (usia 60+ tahun)</div>
                <div class="overflow-x-auto">
                    <table class="matrix-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Anggota Keluarga</th>
                                <th>Jenis Kelamin</th>
                                <th>Umur</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="rt-table-body">
                            @for($i = 1; $i <= 3; $i++)
                            <tr>
                                <td class="text-center">{{ $i }}</td>
                                <td><input type="text" name="rt_nama[]" class="form-input" style="min-width:160px" /></td>
                                <td>
                                    <select name="rt_gender[]" class="form-input" style="min-width:120px">
                                        <option value="">-</option>
                                        <option value="male">Laki-laki</option>
                                        <option value="female">Perempuan</option>
                                    </select>
                                </td>
                                <td><input type="number" name="rt_umur[]" class="form-input" style="width:70px" min="60" /></td>
                                <td><input type="text" name="rt_status[]" class="form-input" style="min-width:120px" placeholder="Hub. keluarga" /></td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <button type="button" id="btn-add-b2" class="mt-2 inline-flex items-center gap-1.5 text-xs text-sky-500 hover:text-sky-600 font-medium cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah Baris
                </button>
                <div class="section-heading mt-6">C. Profil Pendidikan Lansia</div>
                <div class="section-sub">C3. Pendidikan terakhir anggota keluarga lansia</div>
                <div class="overflow-x-auto">
                    <table class="matrix-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama</th>
                                <th>Tidak Sekolah</th>
                                <th>SD</th>
                                <th>SMP</th>
                                <th>SMA</th>
                                <th>Perguruan Tinggi</th>
                            </tr>
                        </thead>
                        <tbody id="c3-table-body">
                            @for($i = 1; $i <= 3; $i++)
                            <tr>
                                <td class="text-center">{{ $i }}</td>
                                <td><input type="text" class="form-input" style="min-width:140px" /></td>
                                @foreach(['ts', 'sd', 'smp', 'sma', 'pt'] as $edu)
                                <td><input type="radio" name="edu_{{ $i }}" value="{{ $edu }}" /></td>
                                @endforeach
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <button type="button" id="btn-add-c3" class="mt-2 inline-flex items-center gap-1.5 text-xs text-sky-500 hover:text-sky-600 font-medium cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah Baris
                </button>
            </div>

            {{-- ─────────────────────────────────
                 STEP 4: SECTION D-E — PEKERJAAN & KTP
            ───────────────────────────────── --}}
            <div class="form-section" id="section-4">
                <div class="section-heading">D. Profil Pekerjaan dan Penghasilan</div>
                <div class="section-sub">D4. Pekerjaan anggota keluarga lansia</div>
                <div class="overflow-x-auto mb-4">
                    <table class="matrix-table">
                        <thead>
                            <tr>
                                <th>No.</th><th>Nama</th>
                                <th>Pensiunan PNS</th><th>Pensiunan TNI/Polri</th>
                                <th>Wiraswasta</th><th>Petani</th><th>Nelayan</th>
                                <th>Tidak Bekerja</th><th>Lain-lain</th>
                            </tr>
                        </thead>
                        <tbody id="d4-table-body">
                            @for($i = 1; $i <= 3; $i++)
                            <tr>
                                <td class="text-center">{{ $i }}</td>
                                <td><input type="text" class="form-input" style="min-width:130px" /></td>
                                @foreach(['A','B','C','D','E','F'] as $pek)
                                <td><input type="radio" name="pek_{{ $i }}" value="{{ $pek }}" /></td>
                                @endforeach
                                <td><input type="text" class="form-input" style="min-width:100px" placeholder="Jenis pekerjaan" /></td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <button type="button" id="btn-add-d4" class="mt-2 inline-flex items-center gap-1.5 text-xs text-sky-500 hover:text-sky-600 font-medium cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah Baris
                </button>
                <div class="mb-4">
                    <label class="form-label">D5. Rata-rata penghasilan kepala keluarga per bulan <span class="text-red-400">*</span></label>
                    <div class="radio-group mt-1">
                        @foreach(['Di bawah Rp 500.000', 'Rp 500.000 – Rp 1.000.000', 'Rp 1.000.000 – Rp 2.500.000', 'Rp 2.500.000 – Rp 5.000.000', 'Di atas Rp 5.000.000'] as $opt)
                        <label class="radio-opt"><input type="radio" name="penghasilan" value="{{ $opt }}"> {{ $opt }}</label>
                        @endforeach
                    </div>
                </div>

                <div class="section-heading">E. Status Kependudukan</div>
                <div class="section-sub">E6. Kepemilikan KTP anggota keluarga lansia</div>
                <div class="overflow-x-auto">
                    <table class="matrix-table">
                        <thead>
                            <tr><th>No.</th><th>Nama</th><th>E-KTP</th><th>KTP Nasional</th><th>Tidak Ber-KTP</th></tr>
                        </thead>
                        <tbody id="e6-table-body">
                            @for($i = 1; $i <= 3; $i++)
                            <tr>
                                <td class="text-center">{{ $i }}</td>
                                <td><input type="text" class="form-input" style="min-width:140px" /></td>
                                @foreach(['e_ktp', 'ktp_nasional', 'no_ktp'] as $ktp)
                                <td><input type="radio" name="ktp_{{ $i }}" value="{{ $ktp }}" /></td>
                                @endforeach
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <button type="button" id="btn-add-e6" class="mt-2 inline-flex items-center gap-1.5 text-xs text-sky-500 hover:text-sky-600 font-medium cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah Baris
                </button>
            </div>

            {{-- ─────────────────────────────────
                 STEP 5: SECTION F-G — PANGAN & KESEHATAN
            ───────────────────────────────── --}}
            <div class="form-section" id="section-5">
                <div class="section-heading">F. Pola Konsumsi Pangan Lansia</div>

                {{-- F7 --}}
                <div class="section-sub">F7. Seberapa sering saudara/keluarga mengkonsumsi makanan pokok berikut? <em class="text-gray-400 font-normal">(centang satu frekuensi per jenis pangan)</em></div>
                <div class="overflow-x-auto mb-5">
                    <table class="matrix-table">
                        <thead>
                            <tr>
                                <th class="text-left">Jenis Pangan Pokok</th>
                                <th>&gt;3x/Hari<br><span class="text-gray-400 font-normal">(50)</span></th>
                                <th>1x/Hari<br><span class="text-gray-400 font-normal">(25)</span></th>
                                <th>3-6x/Minggu<br><span class="text-gray-400 font-normal">(15)</span></th>
                                <th>1-2x/Minggu<br><span class="text-gray-400 font-normal">(10)</span></th>
                                <th>2x/Bulan<br><span class="text-gray-400 font-normal">(5)</span></th>
                                <th>Tidak Pernah<br><span class="text-gray-400 font-normal">(0)</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['Ubi-Ubian' => 'ubi', 'Beras' => 'beras', 'Sagu' => 'sagu'] as $label => $key)
                            <tr>
                                <td>{{ $label }}</td>
                                @foreach(['50','25','15','10','5','0'] as $skor)
                                <td><input type="radio" name="pangan_{{ $key }}" value="{{ $skor }}" /></td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- F8 --}}
                <div class="mb-4">
                    <label class="form-label">F8. Dari mana sumber makan pokok yang menjadi konsumsi sehari-hari? <span class="text-xs text-sky-500 font-normal">(boleh memilih lebih dari 1 pilihan)</span></label>
                    <div class="check-group mt-1">
                        <label class="check-opt"><input type="checkbox" name="sumber_pangan[]" value="panen_sendiri"> a. Hasil Pangan Sendiri</label>
                        <label class="check-opt">
                            <input type="checkbox" name="sumber_pangan[]" value="beli" id="chk-beli">
                            b. Beli
                        </label>
                        <label class="check-opt"><input type="checkbox" name="sumber_pangan[]" value="lainnya" id="chk-f8-lain"> c. Lainnya</label>
                    </div>
                    <div class="mt-2 hidden" id="beli-detail">
                        <input type="text" class="form-input" name="sumber_pangan_beli" placeholder="Sebutkan tempat/cara beli..." style="max-width:350px"/>
                    </div>
                    <div class="mt-2 hidden" id="f8-lain-detail">
                        <input type="text" class="form-input" name="sumber_pangan_lainnya" placeholder="Sebutkan sumber lainnya..." style="max-width:350px"/>
                    </div>
                </div>

                {{-- F9 --}}
                <div class="mb-4">
                    <label class="form-label">F9. Bagaimana pola (kombinasi konsumsi) keluarga/saudara sehari-hari?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="pola_konsumsi" value="a"> a. Nasi/Ubi</label>
                        <label class="radio-opt"><input type="radio" name="pola_konsumsi" value="b"> b. Nasi/Ubi dan Sayur</label>
                        <label class="radio-opt"><input type="radio" name="pola_konsumsi" value="c"> c. Nasi/Ubi, Sayur, Daging/Ikan/Telur</label>
                        <label class="radio-opt"><input type="radio" name="pola_konsumsi" value="d"> d. Nasi/Ubi, Sayur, Daging/Ikan/Telur dan Susu</label>
                        <label class="radio-opt"><input type="radio" name="pola_konsumsi" value="e"> e. Nasi/Ubi, Sayur, Daging/Ikan/Telur, Susu dan Buah</label>
                    </div>
                </div>

                {{-- F10 --}}
                <div class="mb-4">
                    <label class="form-label">F10. Berapa rata-rata konsumsi saudara/keluarga sehari-hari?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="konsumsi_hari" value="1"> a. 1 Kali Sehari</label>
                        <label class="radio-opt"><input type="radio" name="konsumsi_hari" value="2"> b. 2 Kali Sehari</label>
                        <label class="radio-opt"><input type="radio" name="konsumsi_hari" value="3"> c. 3 Kali Sehari</label>
                    </div>
                </div>

                {{-- F11 --}}
                <div class="mb-4">
                    <label class="form-label">F11. Bagaimana cara penyajian (pengolahan) bahan konsumsi saudara/keluarga? <span class="text-xs text-sky-500 font-normal">(boleh memilih lebih dari 1 pilihan)</span></label>
                    <div class="check-group mt-1">
                        <label class="check-opt"><input type="checkbox" name="cara_masak[]" value="dibakar"> a. Dibakar</label>
                        <label class="check-opt"><input type="checkbox" name="cara_masak[]" value="diolah"> b. Diolah/Dimasak</label>
                        <label class="check-opt"><input type="checkbox" name="cara_masak[]" value="tidak_dimasak"> c. Tidak Dimasak</label>
                    </div>
                </div>

                {{-- F12 --}}
                <div class="mb-4">
                    <label class="form-label">F12. Bahan bakar apa yang biasanya digunakan untuk memasak? <span class="text-xs text-sky-500 font-normal">(boleh memilih lebih dari 1 pilihan)</span></label>
                    <div class="check-group mt-1">
                        <label class="check-opt"><input type="checkbox" name="bahan_bakar[]" value="kompor"> a. Kompor</label>
                        <label class="check-opt"><input type="checkbox" name="bahan_bakar[]" value="gas_lpg"> b. Gas LPG</label>
                        <label class="check-opt"><input type="checkbox" name="bahan_bakar[]" value="kayu_bakar"> c. Kayu Bakar</label>
                        <label class="check-opt">
                            <input type="checkbox" name="bahan_bakar[]" value="lainnya" id="chk-bbkar-lain"> d. Lainnya
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="bbkar-lain-detail">
                        <input type="text" class="form-input" name="bahan_bakar_lainnya" placeholder="Sebutkan bahan bakar lainnya..." style="max-width:350px"/>
                    </div>
                </div>

                {{-- F13 --}}
                <div class="mb-4">
                    <label class="form-label">F13. Apakah saudara pernah mendapatkan bantuan sembako (bahan pokok)?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="bansos_sembako" value="pernah" id="radio-sembako-pernah"> a. Pernah</label>
                        <label class="radio-opt"><input type="radio" name="bansos_sembako" value="tidak" id="radio-sembako-tidak"> b. Tidak <span class="text-gray-400 text-xs ml-1">(lanjut ke pertanyaan no. 24 — Kondisi Perumahan)</span></label>
                    </div>
                </div>

                {{-- F14 — tampil hanya jika F13 = Pernah --}}
                <div class="mb-4 hidden" id="f14-block">
                    <label class="form-label">F14. Jika "pernah", siapa yang memberi bantuan tersebut?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="pemberi_sembako" value="pemerintah"> a. Pemerintah Provinsi/Kabupaten/Kota</label>
                        <label class="radio-opt"><input type="radio" name="pemberi_sembako" value="swasta"> b. Swasta</label>
                        <label class="radio-opt">
                            <input type="radio" name="pemberi_sembako" value="lainnya" id="radio-sembako-lain"> c. Lainnya
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="sembako-lain-detail">
                        <input type="text" class="form-input" name="pemberi_sembako_lainnya" placeholder="Sebutkan..." style="max-width:350px"/>
                    </div>
                </div>

                <div class="section-heading mt-6">G. Akses Layanan Kesehatan Lansia</div>

                {{-- G15 --}}
                <div class="mb-4">
                    <label class="form-label">G15. Apakah dalam kurun waktu satu bulan terakhir mengalami keluhan kesehatan?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="keluhan_kes" value="ya" id="radio-keluhan-ya"> a. Ya</label>
                        <label class="radio-opt"><input type="radio" name="keluhan_kes" value="tidak"> b. Tidak</label>
                    </div>
                </div>

                {{-- G16 — tampil hanya jika G15 = Ya --}}
                <div class="mb-4 hidden" id="g16-block">
                    <label class="form-label">G16. Jika "Ya", apa keluhan kesehatan saudara?</label>
                    <textarea class="form-input" name="keluhan_kes_detail" rows="2" placeholder="Jelaskan keluhan kesehatan..."></textarea>
                </div>

                {{-- G17 --}}
                <div class="mb-4">
                    <label class="form-label">G17. Apakah dalam kurun waktu 6 bulan terakhir pernah melakukan pemeriksaan rutin?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="periksa_rutin" value="ya" id="radio-periksa-ya"> a. Ya</label>
                        <label class="radio-opt"><input type="radio" name="periksa_rutin" value="tidak"> b. Tidak</label>
                    </div>
                </div>

                {{-- G18 — tampil hanya jika G17 = Ya --}}
                <div class="mb-4 hidden" id="g18-block">
                    <label class="form-label">G18. Jika "Ya", seberapa sering melakukan pemeriksaan rutin?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="frek_periksa" value="seminggu_sekali"> a. Satu minggu sekali</label>
                        <label class="radio-opt"><input type="radio" name="frek_periksa" value="dua_minggu_sekali"> b. Dua minggu satu kali</label>
                        <label class="radio-opt"><input type="radio" name="frek_periksa" value="sebulan_sekali"> c. Satu bulan sekali</label>
                        <label class="radio-opt"><input type="radio" name="frek_periksa" value="lebih_sebulan"> d. Lebih dari satu bulan sekali</label>
                    </div>
                </div>

                {{-- G19 --}}
                <div class="mb-4">
                    <label class="form-label">G19. Di mana biasanya saudara/keluarga mendapatkan layanan kesehatan? <span class="text-xs text-sky-500 font-normal">(boleh memilih lebih dari 1 pilihan)</span></label>
                    <div class="overflow-x-auto">
                        <table class="matrix-table">
                            <thead>
                                <tr>
                                    <th class="text-left">Nama Layanan</th>
                                    <th>A — Medis</th>
                                    <th>B — Pemeriksaan Rutin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(['Layanan Rumah Sakit', 'Layanan Puskesmas', 'Layanan Puskesmas Pembantu', 'Layanan Puskesmas Keliling', 'Klinik', 'Apotek'] as $i => $layanan)
                                <tr>
                                    <td>{{ $layanan }}</td>
                                    <td><input type="checkbox" name="layanan_kes[{{ $i }}][medis]" value="1" /></td>
                                    <td><input type="checkbox" name="layanan_kes[{{ $i }}][rutin]" value="1" /></td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td><input type="text" class="form-input" name="layanan_kes_lainnya_nama" placeholder="Lainnya..." style="min-width:160px"/></td>
                                    <td><input type="checkbox" name="layanan_kes_lain_medis" value="1" /></td>
                                    <td><input type="checkbox" name="layanan_kes_lain_rutin" value="1" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- G20 --}}
                <div class="mb-4">
                    <label class="form-label">G20. Berapa lama waktu yang dibutuhkan untuk menjangkau layanan kesehatan?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="jangkau_kes" value="kurang_30"> a. Kurang dari 30 Menit</label>
                        <label class="radio-opt"><input type="radio" name="jangkau_kes" value="30_60"> b. 30 menit sampai 1 Jam</label>
                        <label class="radio-opt"><input type="radio" name="jangkau_kes" value="1_5_jam"> c. 1 Jam sampai 5 Jam</label>
                        <label class="radio-opt">
                            <input type="radio" name="jangkau_kes" value="lebih_5jam" id="radio-jangkau-lain"> d. Lebih dari 5 Jam
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="jangkau-lain-detail">
                        <input type="text" class="form-input" name="jangkau_kes_detail" placeholder="Berapa jam/hari..." style="max-width:250px"/>
                    </div>
                </div>

                {{-- G21 --}}
                <div class="mb-4">
                    <label class="form-label">G21. Sarana transportasi yang digunakan untuk mendapatkan pelayanan kesehatan? <span class="text-xs text-sky-500 font-normal">(boleh memilih lebih dari 1 pilihan)</span></label>
                    <div class="check-group mt-1">
                        <label class="check-opt"><input type="checkbox" name="transport_kes[]" value="pribadi"> a. Kendaraan pribadi</label>
                        <label class="check-opt"><input type="checkbox" name="transport_kes[]" value="umum_ojek"> b. Angkutan umum/ojek</label>
                        <label class="check-opt"><input type="checkbox" name="transport_kes[]" value="pemda"> c. Kendaraan milik pemerintah daerah</label>
                        <label class="check-opt">
                            <input type="checkbox" name="transport_kes[]" value="lainnya" id="chk-transport-lain"> d. Lainnya
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="transport-lain-detail">
                        <input type="text" class="form-input" name="transport_kes_lainnya" placeholder="Sebutkan..." style="max-width:300px"/>
                    </div>
                </div>

                {{-- G22 --}}
                <div class="mb-4">
                    <label class="form-label">G22. Dari mana sumber biaya untuk mendapatkan akses layanan kesehatan? <span class="text-xs text-sky-500 font-normal">(boleh memilih lebih dari 1 pilihan)</span></label>
                    <div class="check-group mt-1">
                        <label class="check-opt"><input type="checkbox" name="biaya_kes[]" value="pribadi"> a. Biaya Pribadi</label>
                        <label class="check-opt"><input type="checkbox" name="biaya_kes[]" value="kis_kps"> b. Bantuan Pemerintah (KIS/KPS)</label>
                        <label class="check-opt"><input type="checkbox" name="biaya_kes[]" value="bpjs"> c. BPJS Kesehatan</label>
                        <label class="check-opt">
                            <input type="checkbox" name="biaya_kes[]" value="lainnya" id="chk-biaya-lain"> d. Lainnya
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="biaya-lain-detail">
                        <input type="text" class="form-input" name="biaya_kes_lainnya" placeholder="Sebutkan..." style="max-width:300px"/>
                    </div>
                </div>

                {{-- G23 --}}
                <div class="mb-2">
                    <label class="form-label">G23. Apa yang menjadi masalah utama dalam peningkatan layanan kesehatan lansia di tempat tinggal saudara?</label>
                    <textarea class="form-input" name="masalah_kes" rows="3" placeholder="Jelaskan masalah utama layanan kesehatan..."></textarea>
                </div>
            </div>

            {{-- ─────────────────────────────────
                 STEP 6: SECTION H-I — PERUMAHAN & INFO
            ───────────────────────────────── --}}
            <div class="form-section" id="section-6">
                <div class="section-heading">H. Kondisi Perumahan Lansia</div>

                {{-- H24 --}}
                <div class="mb-4">
                    <label class="form-label">H24. Bagaimana status kepemilikan rumah yang ditempati saat ini?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="status_rumah" value="milik_sendiri"> a. Milik sendiri</label>
                        <label class="radio-opt"><input type="radio" name="status_rumah" value="sewa_kontrak"> b. Sewa/Kontrak</label>
                        <label class="radio-opt"><input type="radio" name="status_rumah" value="rumah_dinas"> c. Rumah Dinas</label>
                        <label class="radio-opt"><input type="radio" name="status_rumah" value="bantuan_pemerintah"> d. Bantuan Pemerintah</label>
                        <label class="radio-opt">
                            <input type="radio" name="status_rumah" value="lainnya" id="radio-rumah-lain"> e. Lainnya
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="rumah-lain-detail">
                        <input type="text" class="form-input" name="status_rumah_lainnya" placeholder="Sebutkan..." style="max-width:300px"/>
                    </div>
                </div>

                {{-- H25 --}}
                <div class="mb-4">
                    <label class="form-label">H25. Bagaimana jenis konstruksi perumahan yang ditempati saat ini?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="jenis_rumah" value="permanen"> a. Permanen/Lantai semen/keramik</label>
                        <label class="radio-opt"><input type="radio" name="jenis_rumah" value="semi_permanen"> b. Semi Permanen/Lantai semen/Keramik</label>
                        <label class="radio-opt"><input type="radio" name="jenis_rumah" value="kayu_papan"> c. Kayu/Lantai Papan</label>
                        <label class="radio-opt"><input type="radio" name="jenis_rumah" value="kayu_tanah"> d. Kayu/Lantai Tanah</label>
                        <label class="radio-opt"><input type="radio" name="jenis_rumah" value="rumah_adat"> e. Rumah Adat</label>
                        <label class="radio-opt">
                            <input type="radio" name="jenis_rumah" value="lainnya" id="radio-jenis-rumah-lain"> f. Lainnya
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="jenis-rumah-lain-detail">
                        <input type="text" class="form-input" name="jenis_rumah_lainnya" placeholder="Sebutkan..." style="max-width:300px"/>
                    </div>
                </div>

                {{-- H26 --}}
                <div class="mb-4">
                    <label class="form-label">H26. Darimana sumber ketersediaan air bersih yang digunakan? <span class="text-xs text-sky-500 font-normal">(boleh memilih lebih dari 1 pilihan)</span></label>
                    <div class="check-group mt-1">
                        <label class="check-opt"><input type="checkbox" name="sumber_air[]" value="sumur_pribadi"> a. Sumur/Sumber Air Pribadi</label>
                        <label class="check-opt"><input type="checkbox" name="sumber_air[]" value="sumur_umum"> b. Sumur/Sumber/Jaringan Air Umum</label>
                        <label class="check-opt">
                            <input type="checkbox" name="sumber_air[]" value="mata_air" id="chk-mata-air"> c. Sumber Mata Air
                        </label>
                        <label class="check-opt">
                            <input type="checkbox" name="sumber_air[]" value="sungai_kali" id="chk-sungai"> d. Sumber/Aliran perairan umum (sungai dan kali)
                        </label>
                        <label class="check-opt">
                            <input type="checkbox" name="sumber_air[]" value="lainnya" id="chk-air-lain"> e. Lainnya
                        </label>
                    </div>
                    <div class="mt-2 flex gap-3 flex-wrap hidden" id="air-detail-block">
                        <div id="mata-air-detail" class="hidden">
                            <input type="text" class="form-input" name="mata_air_nama" placeholder="Nama mata air..." style="max-width:200px"/>
                        </div>
                        <div id="sungai-detail" class="hidden flex gap-2">
                            <input type="text" class="form-input" name="nama_sungai" placeholder="Nama Sungai..." style="max-width:180px"/>
                            <input type="text" class="form-input" name="nama_kali" placeholder="Nama Kali..." style="max-width:180px"/>
                        </div>
                        <div id="air-lain-detail" class="hidden">
                            <input type="text" class="form-input" name="sumber_air_lainnya" placeholder="Sebutkan..." style="max-width:200px"/>
                        </div>
                    </div>
                </div>

                {{-- H27 --}}
                <div class="mb-4">
                    <label class="form-label">H27. Bagaimana sistem penyediaan air bersih di tempat saudara? <span class="text-xs text-sky-500 font-normal">(boleh memilih lebih dari 1 pilihan)</span></label>
                    <div class="check-group mt-1">
                        <label class="check-opt"><input type="checkbox" name="sistem_air[]" value="ambil_sendiri"> a. Ambil sendiri dari sumber</label>
                        <label class="check-opt"><input type="checkbox" name="sistem_air[]" value="bak_penampungan"> b. Bak Penampungan/hidran umum</label>
                        <label class="check-opt"><input type="checkbox" name="sistem_air[]" value="sambungan_rumah"> c. Sambungan rumah</label>
                        <label class="check-opt">
                            <input type="checkbox" name="sistem_air[]" value="lainnya" id="chk-sistem-air-lain"> d. Lainnya
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="sistem-air-lain-detail">
                        <input type="text" class="form-input" name="sistem_air_lainnya" placeholder="Sebutkan..." style="max-width:300px"/>
                    </div>
                </div>

                {{-- H28 --}}
                <div class="mb-4">
                    <label class="form-label">H28. Apakah terdapat ketersediaan sarana MCK di tempat tinggal saudara?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="mck" value="pribadi"> a. Tunggal/Pribadi</label>
                        <label class="radio-opt"><input type="radio" name="mck" value="umum"> b. Umum</label>
                        <label class="radio-opt"><input type="radio" name="mck" value="tidak_ada"> c. Tidak Ada</label>
                    </div>
                </div>

                {{-- H29 --}}
                <div class="mb-4">
                    <label class="form-label">H29. Apakah tersedia fasilitas toilet (kakus atau kloset) di tempat tinggal saudara?</label>
                    <div class="overflow-x-auto">
                        <table class="matrix-table">
                            <thead>
                                <tr>
                                    <th rowspan="2">Karakteristik Sanitasi</th>
                                    <th colspan="2">Fasilitas Buang Air Besar</th>
                                    <th colspan="2">Tempat Pembuangan Akhir Tinja</th>
                                    <th colspan="2">Jenis Kloset yang Digunakan</th>
                                </tr>
                                <tr>
                                    <th>a. Milik Sendiri</th>
                                    <th>b. Lainnya</th>
                                    <th>a. Tangki Septik/IPAL/SPAL</th>
                                    <th>b. Lainnya</th>
                                    <th>a. Leher Angsa</th>
                                    <th>b. Lainnya</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Pilihan</td>
                                    <td><input type="radio" name="bab_fasilitas" value="milik_sendiri"/></td>
                                    <td><input type="radio" name="bab_fasilitas" value="lainnya"/></td>
                                    <td><input type="radio" name="bab_pembuangan" value="septik"/></td>
                                    <td><input type="radio" name="bab_pembuangan" value="lainnya"/></td>
                                    <td><input type="radio" name="jenis_kloset" value="leher_angsa"/></td>
                                    <td><input type="radio" name="jenis_kloset" value="lainnya"/></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- H30 --}}
                <div class="mb-4">
                    <label class="form-label">H30. Sumber penerangan apa yang digunakan di rumah saudara? <span class="text-xs text-sky-500 font-normal">(boleh memilih lebih dari 1 pilihan)</span></label>
                    <div class="check-group mt-1">
                        <label class="check-opt"><input type="checkbox" name="penerangan[]" value="pln"> a. PLN/PLTMH/PLTS</label>
                        <label class="check-opt"><input type="checkbox" name="penerangan[]" value="genset"> b. Generator/Genset</label>
                        <label class="check-opt"><input type="checkbox" name="penerangan[]" value="solar_cell"> c. Solar cell</label>
                        <label class="check-opt">
                            <input type="checkbox" name="penerangan[]" value="lainnya" id="chk-penerangan-lain"> d. Lainnya
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="penerangan-lain-detail">
                        <input type="text" class="form-input" name="penerangan_lainnya" placeholder="Sebutkan..." style="max-width:300px"/>
                    </div>
                </div>

                {{-- H31 --}}
                <div class="mb-4">
                    <label class="form-label">H31. Berapa lama pelayanan sumber penerangan yang disediakan oleh pemerintah?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="lama_penerangan" value="24jam"> a. 24 Jam</label>
                        <label class="radio-opt"><input type="radio" name="lama_penerangan" value="12jam"> b. 12 Jam</label>
                        <label class="radio-opt"><input type="radio" name="lama_penerangan" value="6jam"> c. 6 Jam</label>
                        <label class="radio-opt"><input type="radio" name="lama_penerangan" value="kurang_6jam"> d. Kurang dari 6 Jam</label>
                        <label class="radio-opt"><input type="radio" name="lama_penerangan" value="tidak_ada"> e. Belum/Tidak ada</label>
                    </div>
                </div>

                <div class="section-heading mt-6">I. Pemanfaatan Akses Informasi dan Komunikasi</div>

                {{-- I32 --}}
                <div class="mb-4">
                    <label class="form-label">I32. Apakah saudara menggunakan media berikut dalam mendukung akses informasi? <span class="text-xs text-sky-500 font-normal">(boleh memilih lebih dari 1 pilihan)</span></label>
                    <div class="check-group mt-1">
                        <label class="check-opt"><input type="checkbox" name="media_info[]" value="tv"> a. TV</label>
                        <label class="check-opt"><input type="checkbox" name="media_info[]" value="radio"> b. Radio</label>
                        <label class="check-opt"><input type="checkbox" name="media_info[]" value="berita_online"> c. Berita online/Web</label>
                        <label class="check-opt"><input type="checkbox" name="media_info[]" value="medsos"> d. Media Sosial (FB, IG, WA)</label>
                    </div>
                </div>

                {{-- I33 --}}
                <div class="mb-4">
                    <label class="form-label">I33. Apakah saudara menggunakan handphone dalam membantu berkomunikasi?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="punya_hp" value="ya"> a. Ya</label>
                        <label class="radio-opt"><input type="radio" name="punya_hp" value="tidak"> b. Tidak</label>
                    </div>
                </div>

                {{-- I34 --}}
                <div class="mb-2">
                    <label class="form-label">I34. Melalui media (alternatif) apa saudara dapat mengakses informasi? <span class="text-xs text-sky-500 font-normal">(boleh memilih lebih dari 1 pilihan)</span></label>
                    <div class="check-group mt-1">
                        <label class="check-opt"><input type="checkbox" name="media_alternatif[]" value="perangkat_distrik"> a. Melalui Perangkat Distrik/Kampung</label>
                        <label class="check-opt"><input type="checkbox" name="media_alternatif[]" value="kelompok_masyarakat"> b. Melalui Kelompok Masyarakat</label>
                        <label class="check-opt"><input type="checkbox" name="media_alternatif[]" value="komunitas_ibadah"> c. Melalui Komunitas Tempat Ibadah</label>
                        <label class="check-opt"><input type="checkbox" name="media_alternatif[]" value="keluarga"> d. Keluarga terdekat</label>
                    </div>
                </div>
            </div>

            {{-- ─────────────────────────────────
                 STEP 7: SECTION J-K — SOSIAL
            ───────────────────────────────── --}}
            <div class="form-section" id="section-7">
                <div class="section-heading">J. Akses terhadap Program Perlindungan Sosial</div>

                {{-- J35 --}}
                <div class="mb-4">
                    <label class="form-label">J35. Apakah saudara pernah mendapat bantuan sosial dari pemerintah?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="bansos" value="pernah" id="radio-bansos-pernah"> a. Pernah</label>
                        <label class="radio-opt"><input type="radio" name="bansos" value="tidak"> b. Tidak</label>
                    </div>
                </div>

                {{-- J36 — tampil hanya jika J35 = Pernah --}}
                <div class="mb-4 hidden" id="j36-block">
                    <label class="form-label">J36. Jika "pernah", jenis bantuan apa yang pernah diterima? <span class="text-xs text-sky-500 font-normal">(boleh memilih lebih dari 1 pilihan)</span></label>
                    <div class="check-group mt-1">
                        <label class="check-opt"><input type="checkbox" name="jenis_bansos[]" value="pkh"> a. Program Keluarga Harapan</label>
                        <label class="check-opt"><input type="checkbox" name="jenis_bansos[]" value="blt"> b. Bantuan Langsung Tunai</label>
                        <label class="check-opt"><input type="checkbox" name="jenis_bansos[]" value="kartu_sembako"> c. Kartu Sembako</label>
                        <label class="check-opt"><input type="checkbox" name="jenis_bansos[]" value="prakerja"> d. Kartu Prakerja</label>
                    </div>
                </div>

                {{-- J37 --}}
                <div class="mb-4">
                    <label class="form-label">J37. Apakah saudara pernah mendapatkan program jaminan sosial dari pemerintah?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="jamsosial" value="pernah" id="radio-jamsos-pernah"> a. Pernah</label>
                        <label class="radio-opt"><input type="radio" name="jamsosial" value="tidak"> b. Tidak</label>
                    </div>
                </div>

                {{-- J38 — tampil hanya jika J37 = Pernah --}}
                <div class="mb-4 hidden" id="j38-block">
                    <label class="form-label">J38. Jika "pernah", jenis jaminan sosial apa yang pernah diterima? <span class="text-xs text-sky-500 font-normal">(boleh memilih lebih dari 1 pilihan)</span></label>
                    <div class="check-group mt-1">
                        <label class="check-opt"><input type="checkbox" name="jenis_jamsosial[]" value="jamsostek"> a. Jaminan sosial ketenagakerjaan</label>
                        <label class="check-opt"><input type="checkbox" name="jenis_jamsosial[]" value="jaminan_pensiun"> b. Jaminan pensiun</label>
                        <label class="check-opt"><input type="checkbox" name="jenis_jamsosial[]" value="jht"> c. Jaminan hari tua</label>
                        <label class="check-opt"><input type="checkbox" name="jenis_jamsosial[]" value="jkn"> d. Jaminan kesehatan nasional</label>
                    </div>
                </div>

                {{-- J39 --}}
                <div class="mb-4">
                    <label class="form-label">J39. Apakah saudara pernah dilibatkan dalam pelatihan khusus lansia (misalnya pelatihan keterampilan, dll)?</label>
                    <div class="radio-group mt-1">
                        <label class="radio-opt"><input type="radio" name="pelatihan_lansia" value="pernah" id="radio-pelatihan-pernah"> a. Pernah</label>
                        <label class="radio-opt"><input type="radio" name="pelatihan_lansia" value="tidak"> b. Tidak</label>
                    </div>
                </div>

                {{-- J40 — tampil hanya jika J39 = Pernah --}}
                <div class="mb-4 hidden" id="j40-block">
                    <label class="form-label">J40. Jika "pernah", pelatihan seperti apa yang pernah diikuti?</label>
                    <textarea class="form-input" name="jenis_pelatihan" rows="2" placeholder="Jelaskan jenis pelatihan yang pernah diikuti..."></textarea>
                </div>

                {{-- J41 --}}
                <div class="mb-4">
                    <label class="form-label">J41. Menurut saudara, apa yang menjadi masalah utama dalam implementasi program perlindungan sosial bagi lansia?</label>
                    <textarea class="form-input" name="masalah_linsos" rows="3" placeholder="Jelaskan masalah utama..."></textarea>
                </div>

                <div class="section-heading mt-6">K. Relasi Sosial dan Keterlibatan Publik</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">K42. Frekuensi mengunjungi keluarga/teman</label>
                        <div class="radio-group mt-1">
                            @foreach(['Setiap Hari', 'Dua kali seminggu', 'Satu kali seminggu', 'Tidak Pernah'] as $opt)
                            <label class="radio-opt"><input type="radio" name="kunjungi" value="{{ $opt }}"> {{ $opt }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="form-label">K43. Memiliki kelompok perkumpulan?</label>
                        <div class="radio-group mt-1">
                            <label class="radio-opt"><input type="radio" name="perkumpulan" value="Ya"> Ya</label>
                            <label class="radio-opt"><input type="radio" name="perkumpulan" value="Tidak"> Tidak</label>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">K44. Terlibat pertemuan warga?</label>
                        <div class="radio-group mt-1">
                            <label class="radio-opt"><input type="radio" name="rapat_warga" value="Pernah"> Pernah</label>
                            <label class="radio-opt"><input type="radio" name="rapat_warga" value="Tidak"> Tidak</label>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">K45. Partisipasi pemilu?</label>
                        <div class="radio-group mt-1">
                            <label class="radio-opt"><input type="radio" name="pemilu" value="Pernah"> Pernah</label>
                            <label class="radio-opt"><input type="radio" name="pemilu" value="Tidak"> Tidak</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─────────────────────────────────
                 STEP 8: SECTION L-M — PENGELUARAN
            ───────────────────────────────── --}}
            <div class="form-section" id="section-8">
                <div class="section-heading">L. Rata-rata Biaya Pengeluaran Keluarga</div>
                <div class="mb-4">
                    <label class="form-label">L46. Rata-rata pengeluaran per bulan</label>
                    <div class="radio-group mt-1">
                        @foreach(['Di bawah Rp 1.000.000', 'Rp 1.000.000 – Rp 2.000.000', 'Rp 2.000.000 – Rp 3.000.000', 'Rp 3.000.000 – Rp 4.000.000', 'Di atas Rp 4.000.000'] as $opt)
                        <label class="radio-opt"><input type="radio" name="pengeluaran_total" value="{{ $opt }}"> {{ $opt }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="section-sub">Rincian pengeluaran per item</div>
                <div class="overflow-x-auto border border-gray-100 rounded-xl">
                    <table class="matrix-table">
                        <thead>
                            <tr><th class="text-left">No.</th><th class="text-left">Item Pengeluaran</th><th>Jumlah (Rp)</th></tr>
                        </thead>
                        <tbody>
                            @php
                                $pengeluaranItems = [
                                    'pengeluaran_konsumsi' => 'Konsumsi (Kebutuhan Pokok)',
                                    'pengeluaran_energi' => 'Energi (penerangan & bahan bakar)',
                                    'pengeluaran_pendidikan' => 'Pendidikan',
                                    'pengeluaran_kesehatan' => 'Kesehatan',
                                    'pengeluaran_sosial' => 'Acara Sosial/Keagamaan/Adat',
                                    'pengeluaran_transport' => 'Transportasi',
                                ];
                            @endphp
                            @foreach($pengeluaranItems as $name => $label)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $label }}</td>
                                <td><input type="number" name="{{ $name }}" class="form-input" style="width:150px" placeholder="0" min="0" /></td>
                            </tr>
                            @endforeach
                            <tr class="bg-sky-50/50">
                                <td colspan="2" class="font-bold text-right text-sky-700 px-3 py-2">TOTAL</td>
                                <td><input type="number" class="form-input" style="width:150px" placeholder="0" readonly id="total-pengeluaran" /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="section-heading mt-6">M. Catatan Surveyor</div>
                <div>
                    <label class="form-label">Catatan tambahan (opsional)</label>
                    <textarea name="surveyor_notes" class="form-input" rows="3" placeholder="Catatan tambahan dari surveyor...">{{ old('surveyor_notes') }}</textarea>
                </div>
            </div>

            {{-- ─────────────────────────────────
                 STEP 9: DOKUMEN & SUBMIT
            ───────────────────────────────── --}}
            <div class="form-section" id="section-9">
                <div class="section-heading">Upload Dokumen Pendukung</div>

                <!-- Foto Profil Lansia -->
                <div class="mb-6">
                    <label class="form-label">Foto Profil Lansia</label>
                    <label class="border-2 border-dashed border-gray-200 hover:border-sky-300 rounded-xl p-5 text-center transition-colors group cursor-pointer block max-w-xs">
                        <div class="w-16 h-16 bg-gray-100 group-hover:bg-sky-50 rounded-full flex items-center justify-center mx-auto mb-3 transition-colors">
                            <svg class="w-7 h-7 text-gray-300 group-hover:text-sky-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                        </div>
                        <div class="text-xs font-semibold text-gray-600 mb-1">Upload Foto Profil</div>
                        <input type="file" name="photo" class="hidden" accept=".jpg,.jpeg,.png" onchange="this.parentElement.classList.add('border-sky-400','bg-sky-50/50'); this.nextElementSibling.textContent = this.files[0]?.name || 'JPG, PNG (max 2MB)'" />
                        <span class="text-[0.65rem] text-gray-400 block truncate max-w-full">JPG, PNG (max 2MB)</span>
                    </label>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    @foreach(['KTP' => 'ktp', 'KK' => 'kk', 'Surat Domisili' => 'surat_domisili', 'Foto Kondisi Rumah' => 'foto_rumah'] as $label => $key)
                    <label class="border-2 border-dashed border-gray-200 hover:border-sky-300 rounded-xl p-4 text-center transition-colors group cursor-pointer">
                        <div class="w-10 h-10 bg-gray-100 group-hover:bg-sky-50 rounded-lg flex items-center justify-center mx-auto mb-2 transition-colors">
                            <svg class="w-5 h-5 text-gray-300 group-hover:text-sky-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                        </div>
                        <div class="text-xs font-semibold text-gray-600 mb-1">{{ $label }}</div>
                        <input type="file" name="documents[{{ $key }}]" class="hidden" accept=".jpg,.jpeg,.png,.pdf" onchange="this.parentElement.classList.add('border-sky-400','bg-sky-50/50'); this.nextElementSibling.textContent = this.files[0]?.name || 'JPG, PNG, PDF (max 5MB)'" />
                        <span class="text-[0.65rem] text-gray-400 block truncate max-w-full">JPG, PNG, PDF (max 5MB)</span>
                    </label>
                    @endforeach
                </div>

                <!-- Review summary -->
                <div class="bg-sky-50 border border-sky-200 rounded-xl p-4 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-xs font-semibold text-sky-700">Review sebelum submit</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-xs">
                        <div><span class="text-gray-500">Nomor Kuesioner:</span><span class="font-medium text-gray-800 ml-1" id="review-nomor">—</span></div>
                        <div><span class="text-gray-500">Tanggal:</span><span class="font-medium text-gray-800 ml-1" id="review-tanggal">—</span></div>
                        <div><span class="text-gray-500">Surveyor:</span><span class="font-medium text-gray-800 ml-1">{{ auth()->user()->name }}</span></div>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <div class="text-xs font-semibold text-amber-700">Perhatian</div>
                        <div class="text-xs text-amber-600 mt-0.5">Setelah submit, data tidak bisa diedit kecuali dikembalikan oleh Verifikator. Pastikan semua data sudah benar.</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Navigation buttons -->
        <div class="px-4 sm:px-6 pb-6 flex flex-wrap items-center justify-between gap-3 border-t border-gray-50 pt-5">
            <button type="button" id="btn-prev" onclick="prevStep()"
                    class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-medium px-5 py-2.5 rounded-lg transition-colors cursor-pointer hidden">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Sebelumnya
            </button>
            <div></div>

            <div class="flex items-center gap-3">
                <button type="button" id="btn-draft"
                        onclick="document.getElementById('action-input').value='draft'; document.querySelector('form').submit();"
                        class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-600 border border-gray-200 text-xs font-medium px-5 py-2.5 rounded-lg transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Simpan Draft
                </button>
                <button type="button" id="btn-next" onclick="nextStep()"
                        class="inline-flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white text-xs font-semibold px-6 py-2.5 rounded-lg shadow-sm transition-colors cursor-pointer">
                    Selanjutnya
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button type="submit" id="btn-submit"
                        class="hidden inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-6 py-2.5 rounded-lg shadow-sm transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Submit Survey
                </button>
            </div>
        </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ══════════════════════════════════════
    // CASCADE WILAYAH
    // ══════════════════════════════════════
    async function loadOptions(url, params, selectEl, placeholder) {
        selectEl.disabled = true;
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;
        const qs = new URLSearchParams(params).toString();
        const res = await fetch(`${url}?${qs}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            selectEl.appendChild(opt);
        });
        selectEl.disabled = data.length === 0;
        selectEl.dispatchEvent(new Event('searchable:refresh'));
    }

    if (typeof window.initSearchableSelect === 'function') {
        window.initSearchableSelect('#sel-city', { placeholder: 'Ketik kabupaten/kota...' });
        window.initSearchableSelect('#sel-district', { placeholder: 'Ketik distrik/kecamatan...' });
        window.initSearchableSelect('#sel-village', { placeholder: 'Ketik kelurahan/kampung...' });
    }

    document.getElementById('sel-city')?.addEventListener('change', function() {
        const districtSel = document.getElementById('sel-district');
        const villageSel  = document.getElementById('sel-village');
        villageSel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
        villageSel.disabled = true;
        villageSel.dispatchEvent(new Event('searchable:refresh'));
        if (!this.value) {
            districtSel.innerHTML = '<option value="">-- Pilih Distrik --</option>';
            districtSel.disabled = true;
            districtSel.dispatchEvent(new Event('searchable:refresh'));
            return;
        }
        loadOptions('{{ route("app.wilayah.districts") }}', { city_id: this.value }, districtSel, '-- Pilih Distrik --');
    });

    document.getElementById('sel-district')?.addEventListener('change', function() {
        const villageSel = document.getElementById('sel-village');
        if (!this.value) {
            villageSel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
            villageSel.disabled = true;
            villageSel.dispatchEvent(new Event('searchable:refresh'));
            return;
        }
        villageSel.dispatchEvent(new Event('searchable:refresh'));
        loadOptions('{{ route("app.wilayah.villages") }}', { district_id: this.value }, villageSel, '-- Pilih Kelurahan --');
    });

    // ══════════════════════════════════════
    // STEP WIZARD
    // ══════════════════════════════════════
    const totalSteps = 9;
    let currentStep = 1;

    window.goToStep = function(step) {
        document.getElementById('section-' + currentStep).classList.remove('active');
        document.getElementById('step-indicator-' + currentStep).classList.remove('active');
        if (step > currentStep) {
            document.getElementById('step-indicator-' + currentStep).classList.add('done');
        } else {
            document.getElementById('step-indicator-' + currentStep).classList.remove('done');
        }
        currentStep = step;
        document.getElementById('section-' + currentStep).classList.add('active');
        document.getElementById('step-indicator-' + currentStep).classList.add('active');
        document.getElementById('step-indicator-' + currentStep).classList.remove('done');
        document.getElementById('btn-prev').classList.toggle('hidden', currentStep === 1);
        document.getElementById('btn-next').classList.toggle('hidden', currentStep === totalSteps);
        document.getElementById('btn-submit').classList.toggle('hidden', currentStep !== totalSteps);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    window.nextStep = function() { if (currentStep < totalSteps) goToStep(currentStep + 1); };
    window.prevStep = function() { if (currentStep > 1) goToStep(currentStep - 1); };

    // ══════════════════════════════════════
    // HELPER: toggle visibility
    // ══════════════════════════════════════
    function show(id) { document.getElementById(id)?.classList.remove('hidden'); }
    function hide(id) { document.getElementById(id)?.classList.add('hidden'); }
    function onCheck(id, showId) {
        document.getElementById(id)?.addEventListener('change', function() {
            this.checked ? show(showId) : hide(showId);
        });
    }
    function onRadioChange(name, value, showId, hideId) {
        document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
            r.addEventListener('change', function() {
                if (this.value === value) { show(showId); if(hideId) hide(hideId); }
                else { hide(showId); }
            });
        });
    }

    // ══════════════════════════════════════
    // CONDITIONAL: Section F
    // ══════════════════════════════════════
    onCheck('chk-beli', 'beli-detail');
    onCheck('chk-f8-lain', 'f8-lain-detail');
    onCheck('chk-bbkar-lain', 'bbkar-lain-detail');

    // F13: Pernah → tampilkan F14; Tidak → sembunyikan
    onRadioChange('bansos_sembako', 'pernah', 'f14-block');

    // F14 Lainnya → input
    document.querySelectorAll('input[name="pemberi_sembako"]').forEach(r => {
        r.addEventListener('change', function() {
            this.value === 'lainnya' ? show('sembako-lain-detail') : hide('sembako-lain-detail');
        });
    });

    // ══════════════════════════════════════
    // CONDITIONAL: Section G
    // ══════════════════════════════════════
    // G15 Ya → G16
    onRadioChange('keluhan_kes', 'ya', 'g16-block');

    // G17 Ya → G18
    onRadioChange('periksa_rutin', 'ya', 'g18-block');

    // G20 lebih 5 jam → input
    document.querySelectorAll('input[name="jangkau_kes"]').forEach(r => {
        r.addEventListener('change', function() {
            this.value === 'lebih_5jam' ? show('jangkau-lain-detail') : hide('jangkau-lain-detail');
        });
    });

    onCheck('chk-transport-lain', 'transport-lain-detail');
    onCheck('chk-biaya-lain', 'biaya-lain-detail');

    // ══════════════════════════════════════
    // CONDITIONAL: Section H
    // ══════════════════════════════════════
    document.querySelectorAll('input[name="status_rumah"]').forEach(r => {
        r.addEventListener('change', function() {
            this.value === 'lainnya' ? show('rumah-lain-detail') : hide('rumah-lain-detail');
        });
    });
    document.querySelectorAll('input[name="jenis_rumah"]').forEach(r => {
        r.addEventListener('change', function() {
            this.value === 'lainnya' ? show('jenis-rumah-lain-detail') : hide('jenis-rumah-lain-detail');
        });
    });

    // H26 conditional sub-inputs
    onCheck('chk-mata-air', 'mata-air-detail');
    onCheck('chk-sungai', 'sungai-detail');
    onCheck('chk-air-lain', 'air-lain-detail');
    // show container if any sub checked
    ['chk-mata-air','chk-sungai','chk-air-lain'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', function() {
            const anyChecked = ['chk-mata-air','chk-sungai','chk-air-lain'].some(i => document.getElementById(i)?.checked);
            anyChecked ? show('air-detail-block') : hide('air-detail-block');
        });
    });

    onCheck('chk-sistem-air-lain', 'sistem-air-lain-detail');
    onCheck('chk-penerangan-lain', 'penerangan-lain-detail');

    // ══════════════════════════════════════
    // CONDITIONAL: Section J
    // ══════════════════════════════════════
    // J35 Pernah → J36
    onRadioChange('bansos', 'pernah', 'j36-block');
    // J37 Pernah → J38
    onRadioChange('jamsosial', 'pernah', 'j38-block');
    // J39 Pernah → J40
    onRadioChange('pelatihan_lansia', 'pernah', 'j40-block');

    // ══════════════════════════════════════
    // DYNAMIC ROWS: B2, C3, D4, E6
    // ══════════════════════════════════════

    // B2 — Identitas Anggota Keluarga
    let rowCountB2 = 3;
    document.getElementById('btn-add-b2')?.addEventListener('click', function() {
        rowCountB2++;
        const tbody = document.getElementById('rt-table-body');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center">${rowCountB2}</td>
            <td><input type="text" name="rt_nama[]" class="form-input" style="min-width:160px" /></td>
            <td><select name="rt_gender[]" class="form-input" style="min-width:120px"><option value="">-</option><option value="male">Laki-laki</option><option value="female">Perempuan</option></select></td>
            <td><input type="number" name="rt_umur[]" class="form-input" style="width:70px" min="60" /></td>
            <td><input type="text" name="rt_status[]" class="form-input" style="min-width:120px" placeholder="Hub. keluarga" /></td>
        `;
        tbody.appendChild(tr);
    });

    // C3 — Pendidikan
    let rowCountC3 = 3;
    document.getElementById('btn-add-c3')?.addEventListener('click', function() {
        rowCountC3++;
        const tbody = document.getElementById('c3-table-body');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center">${rowCountC3}</td>
            <td><input type="text" class="form-input" style="min-width:140px" /></td>
            ${['ts','sd','smp','sma','pt'].map(v => `<td><input type="radio" name="edu_${rowCountC3}" value="${v}" /></td>`).join('')}
        `;
        tbody.appendChild(tr);
    });

    // D4 — Pekerjaan
    let rowCountD4 = 3;
    document.getElementById('btn-add-d4')?.addEventListener('click', function() {
        rowCountD4++;
        const tbody = document.getElementById('d4-table-body');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center">${rowCountD4}</td>
            <td><input type="text" class="form-input" style="min-width:130px" /></td>
            ${['A','B','C','D','E','F'].map(v => `<td><input type="radio" name="pek_${rowCountD4}" value="${v}" /></td>`).join('')}
            <td><input type="text" class="form-input" style="min-width:100px" placeholder="Jenis pekerjaan" /></td>
        `;
        tbody.appendChild(tr);
    });

    // E6 — KTP
    let rowCountE6 = 3;
    document.getElementById('btn-add-e6')?.addEventListener('click', function() {
        rowCountE6++;
        const tbody = document.getElementById('e6-table-body');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center">${rowCountE6}</td>
            <td><input type="text" class="form-input" style="min-width:140px" /></td>
            ${['e_ktp','ktp_nasional','no_ktp'].map(v => `<td><input type="radio" name="ktp_${rowCountE6}" value="${v}" /></td>`).join('')}
        `;
        tbody.appendChild(tr);
    });

    // Auto-calculate total pengeluaran
    const pengeluaranInputs = document.querySelectorAll('input[name^="pengeluaran_"]:not([name="pengeluaran_total"])');
    const totalEl = document.getElementById('total-pengeluaran');
    pengeluaranInputs.forEach(input => {
        input.addEventListener('input', function() {
            let total = 0;
            pengeluaranInputs.forEach(inp => { total += parseInt(inp.value) || 0; });
            if (totalEl) totalEl.value = total > 0 ? total.toLocaleString('id-ID') : '';
        });
    });

}); // end DOMContentLoaded
</script>
@endpush
