<?php

namespace App\Support;

class DashboardQuestionCatalog
{
    public static function items(): array
    {
        $yesNo = ['ya' => 'Ya', 'tidak' => 'Tidak'];
        $everNever = ['pernah' => 'Pernah', 'tidak' => 'Tidak'];
        $income = [
            'Di bawah Rp 500.000' => 'Di bawah Rp 500.000',
            'Rp 500.000 - Rp 1.000.000' => 'Rp 500.000 - Rp 1.000.000',
            'Rp 1.000.000 - Rp 2.500.000' => 'Rp 1.000.000 - Rp 2.500.000',
            'Rp 2.500.000 - Rp 5.000.000' => 'Rp 2.500.000 - Rp 5.000.000',
            'Di atas Rp 5.000.000' => 'Di atas Rp 5.000.000',
        ];

        return [
            ['key' => 'gender', 'field' => 'gender', 'source' => 'respondent', 'number' => 'A1', 'label' => 'Jenis kelamin responden', 'group' => 'A. Identitas Responden', 'kind' => 'single', 'display' => 'pie', 'sort' => 11, 'options' => ['male' => 'Laki-laki', 'female' => 'Perempuan']],
            ['key' => 'age', 'field' => 'age', 'source' => 'respondent', 'number' => 'A2', 'label' => 'Kelompok umur responden', 'group' => 'A. Identitas Responden', 'kind' => 'single', 'display' => 'bar', 'sort' => 12, 'transform' => 'ageBucket', 'options' => ['60-64' => '60-64 tahun', '65-69' => '65-69 tahun', '70-74' => '70-74 tahun', '75-79' => '75-79 tahun', '80+' => '80 tahun ke atas', '<60' => 'Di bawah 60 tahun']],
            ['key' => 'education', 'field' => 'education', 'source' => 'respondent', 'number' => 'A3', 'label' => 'Pendidikan terakhir responden', 'group' => 'A. Identitas Responden', 'kind' => 'single', 'display' => 'bar', 'sort' => 13, 'options' => ['Tidak Sekolah' => 'Tidak Sekolah', 'SD' => 'SD', 'SMP' => 'SMP', 'SMA' => 'SMA', 'Perguruan Tinggi' => 'Perguruan Tinggi']],
            ['key' => 'occupation', 'field' => 'occupation', 'source' => 'respondent', 'number' => 'A4', 'label' => 'Pekerjaan responden', 'group' => 'A. Identitas Responden', 'kind' => 'text', 'display' => 'pie', 'sort' => 14],
            ['key' => 'citizenship_status', 'field' => 'citizenship_status', 'source' => 'respondent', 'number' => 'A6', 'label' => 'Status OAP/Non OAP/WNI', 'group' => 'A. Identitas Responden', 'kind' => 'single', 'display' => 'pie', 'sort' => 16, 'options' => ['OAP' => 'OAP', 'Non_OAP' => 'Non OAP', 'WNI' => 'WNI']],
            ['key' => 'penghasilan', 'field' => 'penghasilan', 'number' => 'D5', 'label' => 'Rata-rata penghasilan kepala keluarga per bulan', 'group' => 'D. Pekerjaan dan Penghasilan', 'kind' => 'single', 'display' => 'bar', 'sort' => 55, 'options' => $income],
            ['key' => 'sumber_pangan', 'field' => 'sumber_pangan', 'number' => 'F8', 'label' => 'Sumber makanan pokok sehari-hari', 'group' => 'F. Pola Konsumsi Pangan', 'kind' => 'multi', 'display' => 'table', 'sort' => 80, 'options' => ['panen_sendiri' => 'Hasil pangan sendiri', 'beli' => 'Beli', 'lainnya' => 'Lainnya']],
            ['key' => 'pola_konsumsi', 'field' => 'pola_konsumsi', 'number' => 'F9', 'label' => 'Pola kombinasi konsumsi harian', 'group' => 'F. Pola Konsumsi Pangan', 'kind' => 'single', 'display' => 'table', 'sort' => 90, 'options' => ['a' => 'Nasi/ubi', 'b' => 'Nasi/ubi dan sayur', 'c' => 'Nasi/ubi, sayur, daging/ikan/telur', 'd' => 'Nasi/ubi, sayur, protein, susu', 'e' => 'Nasi/ubi, sayur, protein, susu, buah']],
            ['key' => 'konsumsi_hari', 'field' => 'konsumsi_hari', 'number' => 'F10', 'label' => 'Rata-rata frekuensi makan per hari', 'group' => 'F. Pola Konsumsi Pangan', 'kind' => 'single', 'display' => 'bar', 'sort' => 100, 'options' => ['1' => '1 kali sehari', '2' => '2 kali sehari', '3' => '3 kali sehari']],
            ['key' => 'cara_masak', 'field' => 'cara_masak', 'number' => 'F11', 'label' => 'Cara penyajian bahan konsumsi', 'group' => 'F. Pola Konsumsi Pangan', 'kind' => 'multi', 'display' => 'bar', 'sort' => 110, 'options' => ['dibakar' => 'Dibakar', 'diolah' => 'Diolah/dimasak', 'tidak_dimasak' => 'Tidak dimasak']],
            ['key' => 'bahan_bakar', 'field' => 'bahan_bakar', 'number' => 'F12', 'label' => 'Bahan bakar memasak', 'group' => 'F. Pola Konsumsi Pangan', 'kind' => 'multi', 'display' => 'table', 'sort' => 120, 'options' => ['kompor' => 'Kompor', 'gas_lpg' => 'Gas LPG', 'kayu_bakar' => 'Kayu bakar', 'lainnya' => 'Lainnya']],
            ['key' => 'bansos_sembako', 'field' => 'bansos_sembako', 'number' => 'F13', 'label' => 'Pernah mendapat bantuan sembako', 'group' => 'F. Pola Konsumsi Pangan', 'kind' => 'single', 'display' => 'table', 'sort' => 130, 'options' => $everNever],
            ['key' => 'pemberi_sembako', 'field' => 'pemberi_sembako', 'number' => 'F14', 'label' => 'Pemberi bantuan sembako', 'group' => 'F. Pola Konsumsi Pangan', 'kind' => 'single', 'display' => 'pie', 'sort' => 140, 'options' => ['pemerintah' => 'Pemerintah provinsi/kab/kota', 'swasta' => 'Swasta', 'lainnya' => 'Lainnya']],
            ['key' => 'keluhan_kes', 'field' => 'keluhan_kes', 'number' => 'G15', 'label' => 'Keluhan kesehatan satu bulan terakhir', 'group' => 'G. Akses Layanan Kesehatan', 'kind' => 'single', 'display' => 'table', 'sort' => 150, 'options' => $yesNo],
            ['key' => 'keluhan_detail', 'field' => 'keluhan_detail', 'number' => 'G16', 'label' => 'Jenis keluhan kesehatan', 'group' => 'G. Akses Layanan Kesehatan', 'kind' => 'text', 'display' => 'table', 'sort' => 160],
            ['key' => 'periksa_rutin', 'field' => 'periksa_rutin', 'number' => 'G17', 'label' => 'Pemeriksaan rutin enam bulan terakhir', 'group' => 'G. Akses Layanan Kesehatan', 'kind' => 'single', 'display' => 'pie', 'sort' => 170, 'options' => $yesNo],
            ['key' => 'frek_periksa', 'field' => 'frek_periksa', 'number' => 'G18', 'label' => 'Frekuensi pemeriksaan rutin', 'group' => 'G. Akses Layanan Kesehatan', 'kind' => 'single', 'display' => 'table', 'sort' => 180, 'options' => ['seminggu_sekali' => 'Satu minggu sekali', 'dua_minggu_sekali' => 'Dua minggu sekali', 'sebulan_sekali' => 'Satu bulan sekali', 'lebih_sebulan' => 'Lebih dari satu bulan sekali']],
            ['key' => 'jangkau_kes', 'field' => 'jangkau_kes', 'number' => 'G20', 'label' => 'Waktu tempuh ke layanan kesehatan', 'group' => 'G. Akses Layanan Kesehatan', 'kind' => 'single', 'display' => 'bar', 'sort' => 200, 'options' => ['kurang_30' => 'Kurang dari 30 menit', '30_60' => '30 menit sampai 1 jam', '1_5_jam' => '1 sampai 5 jam', 'lebih_5jam' => 'Lebih dari 5 jam']],
            ['key' => 'transport_kes', 'field' => 'transport_kes', 'number' => 'G21', 'label' => 'Transportasi ke layanan kesehatan', 'group' => 'G. Akses Layanan Kesehatan', 'kind' => 'multi', 'display' => 'bar', 'sort' => 210, 'options' => ['pribadi' => 'Kendaraan pribadi', 'umum_ojek' => 'Angkutan umum/ojek', 'pemda' => 'Kendaraan pemda', 'lainnya' => 'Lainnya']],
            ['key' => 'biaya_kes', 'field' => 'biaya_kes', 'number' => 'G22', 'label' => 'Sumber biaya layanan kesehatan', 'group' => 'G. Akses Layanan Kesehatan', 'kind' => 'multi', 'display' => 'bar', 'sort' => 220, 'options' => ['pribadi' => 'Biaya pribadi', 'kis_kps' => 'KIS/KPS', 'bpjs' => 'BPJS Kesehatan', 'lainnya' => 'Lainnya']],
            ['key' => 'masalah_kes', 'field' => 'masalah_kes', 'number' => 'G23', 'label' => 'Masalah utama layanan kesehatan lansia', 'group' => 'G. Akses Layanan Kesehatan', 'kind' => 'text', 'display' => 'table', 'sort' => 230],
            ['key' => 'status_rumah', 'field' => 'status_rumah', 'number' => 'H24', 'label' => 'Status kepemilikan rumah', 'group' => 'H. Kondisi Perumahan', 'kind' => 'single', 'display' => 'pie', 'sort' => 240, 'options' => ['milik_sendiri' => 'Milik sendiri', 'sewa_kontrak' => 'Sewa/kontrak', 'rumah_dinas' => 'Rumah dinas', 'bantuan_pemerintah' => 'Bantuan pemerintah', 'lainnya' => 'Lainnya']],
            ['key' => 'jenis_rumah', 'field' => 'jenis_rumah', 'number' => 'H25', 'label' => 'Jenis konstruksi rumah', 'group' => 'H. Kondisi Perumahan', 'kind' => 'single', 'display' => 'bar', 'sort' => 250, 'options' => ['permanen' => 'Permanen', 'semi_permanen' => 'Semi permanen', 'kayu_papan' => 'Kayu/lantai papan', 'kayu_tanah' => 'Kayu/lantai tanah', 'rumah_adat' => 'Rumah adat', 'lainnya' => 'Lainnya']],
            ['key' => 'sumber_air', 'field' => 'sumber_air', 'number' => 'H26', 'label' => 'Sumber air bersih', 'group' => 'H. Kondisi Perumahan', 'kind' => 'multi', 'display' => 'table', 'sort' => 260, 'options' => ['sumur_pribadi' => 'Sumur/sumber air pribadi', 'sumur_umum' => 'Sumur/jaringan air umum', 'mata_air' => 'Mata air', 'sungai_kali' => 'Sungai/kali', 'lainnya' => 'Lainnya']],
            ['key' => 'sistem_air', 'field' => 'sistem_air', 'number' => 'H27', 'label' => 'Sistem penyediaan air bersih', 'group' => 'H. Kondisi Perumahan', 'kind' => 'multi', 'display' => 'table', 'sort' => 270, 'options' => ['ambil_sendiri' => 'Ambil sendiri dari sumber', 'bak_penampungan' => 'Bak penampungan/hidran umum', 'sambungan_rumah' => 'Sambungan rumah', 'lainnya' => 'Lainnya']],
            ['key' => 'mck', 'field' => 'mck', 'number' => 'H28', 'label' => 'Ketersediaan sarana MCK', 'group' => 'H. Kondisi Perumahan', 'kind' => 'single', 'display' => 'pie', 'sort' => 280, 'options' => ['pribadi' => 'Tunggal/pribadi', 'umum' => 'Umum', 'tidak_ada' => 'Tidak ada']],
            ['key' => 'bab_fasilitas', 'field' => 'bab_fasilitas', 'number' => 'H29a', 'label' => 'Fasilitas buang air besar', 'group' => 'H. Kondisi Perumahan', 'kind' => 'single', 'display' => 'pie', 'sort' => 291, 'options' => ['milik_sendiri' => 'Milik sendiri', 'lainnya' => 'Lainnya']],
            ['key' => 'bab_pembuangan', 'field' => 'bab_pembuangan', 'number' => 'H29b', 'label' => 'Tempat pembuangan akhir tinja', 'group' => 'H. Kondisi Perumahan', 'kind' => 'single', 'display' => 'pie', 'sort' => 292, 'options' => ['septik' => 'Tangki septik/IPAL/SPAL', 'lainnya' => 'Lainnya']],
            ['key' => 'jenis_kloset', 'field' => 'jenis_kloset', 'number' => 'H29c', 'label' => 'Jenis kloset yang digunakan', 'group' => 'H. Kondisi Perumahan', 'kind' => 'single', 'display' => 'pie', 'sort' => 293, 'options' => ['leher_angsa' => 'Leher angsa', 'lainnya' => 'Lainnya']],
            ['key' => 'penerangan', 'field' => 'penerangan', 'number' => 'H30', 'label' => 'Sumber penerangan rumah', 'group' => 'H. Kondisi Perumahan', 'kind' => 'multi', 'display' => 'bar', 'sort' => 300, 'options' => ['pln' => 'PLN/PLTMH/PLTS', 'genset' => 'Generator/genset', 'solar_cell' => 'Solar cell', 'lainnya' => 'Lainnya']],
            ['key' => 'lama_penerangan', 'field' => 'lama_penerangan', 'number' => 'H31', 'label' => 'Lama pelayanan penerangan pemerintah', 'group' => 'H. Kondisi Perumahan', 'kind' => 'single', 'display' => 'table', 'sort' => 310, 'options' => ['24jam' => '24 jam', '12jam' => '12 jam', '6jam' => '6 jam', 'kurang_6jam' => 'Kurang dari 6 jam', 'tidak_ada' => 'Belum/tidak ada']],
            ['key' => 'media_info', 'field' => 'media_info', 'number' => 'I32', 'label' => 'Media pendukung akses informasi', 'group' => 'I. Informasi dan Komunikasi', 'kind' => 'multi', 'display' => 'bar', 'sort' => 320, 'options' => ['tv' => 'TV', 'radio' => 'Radio', 'berita_online' => 'Berita online/web', 'medsos' => 'Media sosial']],
            ['key' => 'punya_hp', 'field' => 'punya_hp', 'number' => 'I33', 'label' => 'Penggunaan handphone untuk komunikasi', 'group' => 'I. Informasi dan Komunikasi', 'kind' => 'single', 'display' => 'pie', 'sort' => 330, 'options' => $yesNo],
            ['key' => 'media_alternatif', 'field' => 'media_alternatif', 'number' => 'I34', 'label' => 'Media alternatif akses informasi', 'group' => 'I. Informasi dan Komunikasi', 'kind' => 'multi', 'display' => 'table', 'sort' => 340, 'options' => ['perangkat_distrik' => 'Perangkat distrik/kampung', 'kelompok_masyarakat' => 'Kelompok masyarakat', 'komunitas_ibadah' => 'Komunitas tempat ibadah', 'keluarga' => 'Keluarga terdekat']],
            ['key' => 'bansos', 'field' => 'bansos', 'number' => 'J35', 'label' => 'Pernah mendapat bantuan sosial pemerintah', 'group' => 'J. Perlindungan Sosial', 'kind' => 'single', 'display' => 'pie', 'sort' => 350, 'options' => $everNever],
            ['key' => 'jenis_bansos', 'field' => 'jenis_bansos', 'number' => 'J36', 'label' => 'Jenis bantuan sosial yang diterima', 'group' => 'J. Perlindungan Sosial', 'kind' => 'multi', 'display' => 'bar', 'sort' => 360, 'options' => ['pkh' => 'Program Keluarga Harapan', 'blt' => 'Bantuan Langsung Tunai', 'kartu_sembako' => 'Kartu Sembako', 'prakerja' => 'Kartu Prakerja']],
            ['key' => 'jamsosial', 'field' => 'jamsosial', 'number' => 'J37', 'label' => 'Pernah mendapat program jaminan sosial', 'group' => 'J. Perlindungan Sosial', 'kind' => 'single', 'display' => 'pie', 'sort' => 370, 'options' => $everNever],
            ['key' => 'jenis_jamsosial', 'field' => 'jenis_jamsosial', 'number' => 'J38', 'label' => 'Jenis jaminan sosial yang diterima', 'group' => 'J. Perlindungan Sosial', 'kind' => 'multi', 'display' => 'table', 'sort' => 380, 'options' => ['jamsostek' => 'Jaminan sosial ketenagakerjaan', 'jaminan_pensiun' => 'Jaminan pensiun', 'jht' => 'Jaminan hari tua', 'jkn' => 'Jaminan kesehatan nasional']],
            ['key' => 'pelatihan_lansia', 'field' => 'pelatihan_lansia', 'number' => 'J39', 'label' => 'Pernah ikut pelatihan khusus lansia', 'group' => 'J. Perlindungan Sosial', 'kind' => 'single', 'display' => 'table', 'sort' => 390, 'options' => $everNever],
            ['key' => 'jenis_pelatihan', 'field' => 'jenis_pelatihan', 'number' => 'J40', 'label' => 'Jenis pelatihan yang diikuti', 'group' => 'J. Perlindungan Sosial', 'kind' => 'text', 'display' => 'bar', 'sort' => 400],
            ['key' => 'masalah_linsos', 'field' => 'masalah_linsos', 'number' => 'J41', 'label' => 'Masalah utama implementasi perlindungan sosial', 'group' => 'J. Perlindungan Sosial', 'kind' => 'text', 'display' => 'table', 'sort' => 410],
            ['key' => 'kunjungi', 'field' => 'kunjungi', 'number' => 'K42', 'label' => 'Frekuensi mengunjungi keluarga/teman', 'group' => 'K. Relasi Sosial dan Keterlibatan Publik', 'kind' => 'single', 'display' => 'bar', 'sort' => 420, 'options' => ['Setiap Hari' => 'Setiap hari', 'Dua kali seminggu' => 'Dua kali seminggu', 'Satu kali seminggu' => 'Satu kali seminggu', 'Tidak Pernah' => 'Tidak pernah', 'on' => 'Terisi tanpa pilihan (data lama)']],
            ['key' => 'perkumpulan', 'field' => 'perkumpulan', 'number' => 'K43', 'label' => 'Memiliki kelompok perkumpulan', 'group' => 'K. Relasi Sosial dan Keterlibatan Publik', 'kind' => 'single', 'display' => 'pie', 'sort' => 430, 'options' => ['Ya' => 'Ya', 'Tidak' => 'Tidak', 'on' => 'Terisi tanpa pilihan (data lama)']],
            ['key' => 'rapat_warga', 'field' => 'rapat_warga', 'number' => 'K44', 'label' => 'Terlibat pertemuan warga', 'group' => 'K. Relasi Sosial dan Keterlibatan Publik', 'kind' => 'single', 'display' => 'table', 'sort' => 440, 'options' => ['Pernah' => 'Pernah', 'Tidak' => 'Tidak', 'on' => 'Terisi tanpa pilihan (data lama)']],
            ['key' => 'pemilu', 'field' => 'pemilu', 'number' => 'K45', 'label' => 'Partisipasi pemilu', 'group' => 'K. Relasi Sosial dan Keterlibatan Publik', 'kind' => 'single', 'display' => 'table', 'sort' => 450, 'options' => ['Pernah' => 'Pernah', 'Tidak' => 'Tidak', 'on' => 'Terisi tanpa pilihan (data lama)']],
            ['key' => 'pengeluaran_total', 'field' => 'pengeluaran_total', 'number' => 'L46', 'label' => 'Rata-rata pengeluaran keluarga per bulan', 'group' => 'L. Biaya Pengeluaran Keluarga', 'kind' => 'single', 'display' => 'bar', 'sort' => 460, 'options' => ['Di bawah Rp 1.000.000' => 'Di bawah Rp 1.000.000', 'Rp 1.000.000 - Rp 2.000.000' => 'Rp 1.000.000 - Rp 2.000.000', 'Rp 2.000.000 - Rp 3.000.000' => 'Rp 2.000.000 - Rp 3.000.000', 'Rp 3.000.000 - Rp 4.000.000' => 'Rp 3.000.000 - Rp 4.000.000', 'Di atas Rp 4.000.000' => 'Di atas Rp 4.000.000']],
        ];
    }

    public static function specialItems(): array
    {
        return [
            ['key' => 'frekuensi_pangan_pokok', 'number' => 'F7', 'label' => 'Frekuensi konsumsi beras, ubi-ubian, dan sagu', 'group' => 'F. Pola Konsumsi Pangan', 'kind' => 'grouped_bar', 'display' => 'grouped_bar', 'wide' => true, 'sort' => 71],
            ['key' => 'layanan_kes', 'number' => 'G19', 'label' => 'Tempat mendapatkan layanan kesehatan', 'group' => 'G. Akses Layanan Kesehatan', 'kind' => 'matrix', 'display' => 'grouped_bar', 'wide' => true, 'sort' => 190],
        ];
    }

    public static function allItems(): array
    {
        return collect([...self::items(), ...self::specialItems()])
            ->sortBy(fn (array $item): int => $item['sort'])
            ->values()
            ->all();
    }

    public static function frequencyOptions(): array
    {
        return [
            '50' => '>3 kali/hari',
            '25' => '1 kali/hari',
            '15' => '3-6 kali/minggu',
            '10' => '1-2 kali/minggu',
            '5' => '2 kali/bulan',
            '0' => 'Tidak pernah',
        ];
    }

    public static function foodFields(): array
    {
        return [
            'pangan_beras' => 'Beras',
            'pangan_ubi' => 'Ubi-ubian',
            'pangan_sagu' => 'Sagu',
        ];
    }

    public static function healthServices(): array
    {
        return [
            'rumah_sakit' => 'Rumah Sakit',
            'puskesmas' => 'Puskesmas',
            'pustu' => 'Puskesmas Pembantu',
            'puskesmas_keliling' => 'Puskesmas Keliling',
            'klinik' => 'Klinik',
            'apotek' => 'Apotek',
            'lainnya' => 'Lainnya',
        ];
    }
}
