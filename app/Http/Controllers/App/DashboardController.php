<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();

        // Get filter params
        $cityId = request('city_id');
        $districtId = request('district_id');
        $villageId = request('village_id');

        // Base query (scoped by role + filter)
        $baseQuery = SurveyResponse::query();
        if ($role === 'surveyor') {
            $baseQuery->where('surveyor_id', $user->id);
        }

        // Apply wilayah filter
        if ($villageId) {
            $baseQuery->where('region_id', $villageId);
        } elseif ($districtId) {
            $regionIds = \App\Models\Region::where('parent_id', $districtId)->pluck('id');
            $baseQuery->whereIn('region_id', $regionIds);
        } elseif ($cityId) {
            $districtIds = \App\Models\Region::where('parent_id', $cityId)->pluck('id');
            $regionIds = \App\Models\Region::whereIn('parent_id', $districtIds)->pluck('id');
            $baseQuery->whereIn('region_id', $regionIds);
        }

        // Stat cards
        $stats = [
            'total'         => (clone $baseQuery)->count(),
            'this_month'    => (clone $baseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'verified'      => (clone $baseQuery)->where('status', 'verified')->count(),
            'need_revision' => (clone $baseQuery)->where('status', 'need_revision')->count(),
            'submitted'     => (clone $baseQuery)->where('status', 'submitted')->count(),
            'draft'         => (clone $baseQuery)->where('status', 'draft')->count(),
            'rejected'      => (clone $baseQuery)->where('status', 'rejected')->count(),
        ];

        // Get response IDs for analytics
        $responseIds = (clone $baseQuery)->pluck('id');

        // Per-question analytics from answer_json (filtered)
        $questionAnalytics = $this->buildQuestionAnalytics($responseIds);

        // Filter options for view
        $province = \App\Models\Region::active()->province()->first();
        $cities = $province ? \App\Models\Region::active()->city()->where('parent_id', $province->id)->orderBy('name')->get() : collect();

        $districts = $cityId ? \App\Models\Region::active()->district()->where('parent_id', $cityId)->orderBy('name')->get() : collect();
        $villages = $districtId ? \App\Models\Region::active()->village()->where('parent_id', $districtId)->orderBy('name')->get() : collect();

        return view('app.dashboard', compact('stats', 'questionAnalytics', 'cities', 'districts', 'villages', 'cityId', 'districtId', 'villageId'));
    }

    /**
     * Aggregate answers from all survey_answers.answer_json to produce
     * per-question frequency counts.
     */
    private function buildQuestionAnalytics($responseIds): array
    {
        $query = SurveyAnswer::whereNotNull('answer_json');
        if ($responseIds->isNotEmpty()) {
            $query->whereIn('survey_response_id', $responseIds);
        }
        $allAnswers = $query->pluck('answer_json');

        if ($allAnswers->isEmpty()) {
            return [];
        }

        // Define which fields to analyze and their labels + chart type
        // type: 'doughnut' = chart donut, 'bar' = chart batang, 'text' = hanya teks persentase
        $fields = [
            // Pangan
            'pola_konsumsi'     => ['label' => 'Pola Konsumsi Harian', 'group' => 'Pangan', 'type' => 'doughnut', 'options' => ['a' => 'Nasi/Ubi saja', 'b' => 'Nasi/Ubi + Sayur', 'c' => '+ Daging/Ikan', 'd' => '+ Susu', 'e' => '+ Buah']],
            'konsumsi_hari'     => ['label' => 'Frekuensi Makan Per Hari', 'group' => 'Pangan', 'type' => 'text', 'options' => ['1' => '1x Sehari', '2' => '2x Sehari', '3' => '3x Sehari']],
            'bansos_sembako'    => ['label' => 'Pernah Dapat Bantuan Sembako', 'group' => 'Pangan', 'type' => 'text', 'options' => ['pernah' => 'Pernah', 'tidak' => 'Tidak']],

            // Kesehatan
            'keluhan_kes'       => ['label' => 'Keluhan Kesehatan 1 Bulan Terakhir', 'group' => 'Kesehatan', 'type' => 'doughnut', 'options' => ['ya' => 'Ya', 'tidak' => 'Tidak']],
            'periksa_rutin'     => ['label' => 'Pemeriksaan Rutin 6 Bulan Terakhir', 'group' => 'Kesehatan', 'type' => 'text', 'options' => ['ya' => 'Ya', 'tidak' => 'Tidak']],
            'jangkau_kes'       => ['label' => 'Waktu Tempuh ke Layanan Kesehatan', 'group' => 'Kesehatan', 'type' => 'bar', 'options' => ['kurang_30' => '< 30 Menit', '30_60' => '30-60 Menit', '1_5_jam' => '1-5 Jam', 'lebih_5jam' => '> 5 Jam']],

            // Perumahan
            'status_rumah'      => ['label' => 'Status Kepemilikan Rumah', 'group' => 'Perumahan', 'type' => 'bar', 'options' => ['milik_sendiri' => 'Milik Sendiri', 'sewa_kontrak' => 'Sewa/Kontrak', 'rumah_dinas' => 'Rumah Dinas', 'bantuan_pemerintah' => 'Bantuan Pemerintah', 'lainnya' => 'Lainnya']],
            'jenis_rumah'       => ['label' => 'Jenis Konstruksi Rumah', 'group' => 'Perumahan', 'type' => 'text', 'options' => ['permanen' => 'Permanen', 'semi_permanen' => 'Semi Permanen', 'kayu_papan' => 'Kayu/Papan', 'kayu_tanah' => 'Kayu/Tanah', 'rumah_adat' => 'Rumah Adat', 'lainnya' => 'Lainnya']],
            'mck'              => ['label' => 'Ketersediaan MCK', 'group' => 'Perumahan', 'type' => 'doughnut', 'options' => ['pribadi' => 'Pribadi', 'umum' => 'Umum', 'tidak_ada' => 'Tidak Ada']],
            'lama_penerangan'   => ['label' => 'Lama Pelayanan Penerangan', 'group' => 'Perumahan', 'type' => 'text', 'options' => ['24jam' => '24 Jam', '12jam' => '12 Jam', '6jam' => '6 Jam', 'kurang_6jam' => '< 6 Jam', 'tidak_ada' => 'Tidak Ada']],

            // Informasi & Komunikasi
            'punya_hp'          => ['label' => 'Kepemilikan Handphone', 'group' => 'Informasi', 'type' => 'doughnut', 'options' => ['ya' => 'Ya', 'tidak' => 'Tidak']],

            // Perlindungan Sosial
            'bansos'            => ['label' => 'Pernah Dapat Bantuan Sosial', 'group' => 'Perlindungan Sosial', 'type' => 'doughnut', 'options' => ['pernah' => 'Pernah', 'tidak' => 'Tidak']],
            'jamsosial'         => ['label' => 'Pernah Dapat Jaminan Sosial', 'group' => 'Perlindungan Sosial', 'type' => 'text', 'options' => ['pernah' => 'Pernah', 'tidak' => 'Tidak']],
            'pelatihan_lansia'  => ['label' => 'Pernah Ikut Pelatihan Lansia', 'group' => 'Perlindungan Sosial', 'type' => 'text', 'options' => ['pernah' => 'Pernah', 'tidak' => 'Tidak']],

            // Penghasilan & Pengeluaran
            'penghasilan'       => ['label' => 'Rata-rata Penghasilan Per Bulan', 'group' => 'Ekonomi', 'type' => 'bar', 'options' => ['Di bawah Rp 500.000' => '< Rp 500rb', 'Rp 500.000 \xe2\x80\x93 Rp 1.000.000' => 'Rp 500rb-1jt', 'Rp 1.000.000 \xe2\x80\x93 Rp 2.500.000' => 'Rp 1-2,5jt', 'Rp 2.500.000 \xe2\x80\x93 Rp 5.000.000' => 'Rp 2,5-5jt', 'Di atas Rp 5.000.000' => '> Rp 5jt']],
            'pengeluaran_total' => ['label' => 'Rata-rata Pengeluaran Per Bulan', 'group' => 'Ekonomi', 'type' => 'bar', 'options' => ['Di bawah Rp 1.000.000' => '< Rp 1jt', 'Rp 1.000.000 \xe2\x80\x93 Rp 2.000.000' => 'Rp 1-2jt', 'Rp 2.000.000 \xe2\x80\x93 Rp 3.000.000' => 'Rp 2-3jt', 'Rp 3.000.000 \xe2\x80\x93 Rp 4.000.000' => 'Rp 3-4jt', 'Di atas Rp 4.000.000' => '> Rp 4jt']],
        ];

        // Also handle multiple-choice fields
        $multiFields = [
            'cara_masak'    => ['label' => 'Cara Penyajian Makanan', 'group' => 'Pangan', 'type' => 'bar', 'options' => ['dibakar' => 'Dibakar', 'diolah' => 'Diolah/Dimasak', 'tidak_dimasak' => 'Tidak Dimasak']],
            'bahan_bakar'   => ['label' => 'Bahan Bakar Memasak', 'group' => 'Pangan', 'type' => 'bar', 'options' => ['kompor' => 'Kompor', 'gas_lpg' => 'Gas LPG', 'kayu_bakar' => 'Kayu Bakar', 'lainnya' => 'Lainnya']],
            'media_info'    => ['label' => 'Media Informasi', 'group' => 'Informasi', 'type' => 'bar', 'options' => ['tv' => 'TV', 'radio' => 'Radio', 'berita_online' => 'Berita Online', 'medsos' => 'Media Sosial']],
            'biaya_kes'     => ['label' => 'Sumber Biaya Kesehatan', 'group' => 'Kesehatan', 'type' => 'bar', 'options' => ['pribadi' => 'Biaya Pribadi', 'kis_kps' => 'KIS/KPS', 'bpjs' => 'BPJS', 'lainnya' => 'Lainnya']],
            'sumber_air'    => ['label' => 'Sumber Air Bersih', 'group' => 'Perumahan', 'type' => 'bar', 'options' => ['sumur_pribadi' => 'Sumur Pribadi', 'sumur_umum' => 'Sumur Umum', 'mata_air' => 'Mata Air', 'sungai_kali' => 'Sungai/Kali', 'lainnya' => 'Lainnya']],
            'penerangan'    => ['label' => 'Sumber Penerangan', 'group' => 'Perumahan', 'type' => 'bar', 'options' => ['pln' => 'PLN/PLTMH', 'genset' => 'Generator', 'solar_cell' => 'Solar Cell', 'lainnya' => 'Lainnya']],
            'transport_kes' => ['label' => 'Transportasi ke Layanan Kesehatan', 'group' => 'Kesehatan', 'type' => 'bar', 'options' => ['pribadi' => 'Kendaraan Pribadi', 'umum_ojek' => 'Angkutan Umum/Ojek', 'pemda' => 'Kendaraan Pemda', 'lainnya' => 'Lainnya']],
        ];

        $results = [];
        $totalResponses = $allAnswers->count();

        // Process single-choice fields
        foreach ($fields as $key => $config) {
            $counts = [];
            foreach ($allAnswers as $json) {
                $data = is_array($json) ? $json : (is_string($json) ? json_decode($json, true) : []);
                if (isset($data[$key]) && $data[$key] !== null && $data[$key] !== '') {
                    $val = $data[$key];
                    $label = $config['options'][$val] ?? $val;
                    $counts[$label] = ($counts[$label] ?? 0) + 1;
                }
            }
            if (!empty($counts)) {
                $results[] = [
                    'key'    => $key,
                    'label'  => $config['label'],
                    'group'  => $config['group'],
                    'type'   => $config['type'],
                    'data'   => $counts,
                    'total'  => array_sum($counts),
                ];
            }
        }

        // Process multi-choice fields
        foreach ($multiFields as $key => $config) {
            $counts = [];
            foreach ($allAnswers as $json) {
                $data = is_array($json) ? $json : (is_string($json) ? json_decode($json, true) : []);
                if (isset($data[$key]) && is_array($data[$key])) {
                    foreach ($data[$key] as $val) {
                        $label = $config['options'][$val] ?? $val;
                        $counts[$label] = ($counts[$label] ?? 0) + 1;
                    }
                }
            }
            if (!empty($counts)) {
                $results[] = [
                    'key'    => $key,
                    'label'  => $config['label'],
                    'group'  => $config['group'],
                    'type'   => $config['type'],
                    'data'   => $counts,
                    'total'  => $totalResponses,
                ];
            }
        }

        return $results;
    }
}
