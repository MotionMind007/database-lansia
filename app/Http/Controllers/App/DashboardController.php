<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use App\Support\SurveyResponseAccess;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $cityId = request('city_id');
        $districtId = request('district_id');
        $villageId = request('village_id');
        $gender = request('gender');
        $category = request('category');

        if (! in_array($gender, ['male', 'female'], true)) {
            $gender = null;
        }

        $baseQuery = SurveyResponse::query();
        SurveyResponseAccess::applyVisibleScope($baseQuery, $user);

        if ($villageId) {
            $baseQuery->where('region_id', $villageId);
        } elseif ($districtId) {
            $regionIds = Region::where('parent_id', $districtId)->pluck('id');
            $baseQuery->whereIn('region_id', $regionIds);
        } elseif ($cityId) {
            $districtIds = Region::where('parent_id', $cityId)->pluck('id');
            $regionIds = Region::whereIn('parent_id', $districtIds)->pluck('id');
            $baseQuery->whereIn('region_id', $regionIds);
        }

        if ($gender) {
            $baseQuery->whereHas('respondent', function ($query) use ($gender) {
                $query->where('gender', $gender);
            });
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'this_month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'verified' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_VERIFIED)->count(),
            'need_revision' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_NEED_REVISION)->count(),
            'submitted' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_SUBMITTED)->count(),
            'draft' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_DRAFT)->count(),
            'rejected' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_REJECTED)->count(),
        ];

        $responseIds = (clone $baseQuery)->pluck('id');
        $responses = SurveyResponse::with(['respondent'])
            ->whereIn('id', $responseIds)
            ->get();

        $answerPayloads = SurveyAnswer::whereIn('survey_response_id', $responseIds)
            ->whereNotNull('answer_json')
            ->get()
            ->groupBy('survey_response_id')
            ->map(function ($answers) {
                return $answers->reduce(function (array $carry, SurveyAnswer $answer) {
                    return array_replace_recursive($carry, $answer->answer_json ?? []);
                }, []);
            });

        $questionAnalytics = $this->buildQuestionAnalytics($responses, $answerPayloads);
        $categoryOptions = collect($questionAnalytics)->pluck('group')->unique()->values();

        if ($category && $categoryOptions->contains($category)) {
            $questionAnalytics = collect($questionAnalytics)
                ->where('group', $category)
                ->values()
                ->all();
        } else {
            $category = null;
        }

        $dashboardSummary = $this->buildDashboardSummary($questionAnalytics, $responses->count());

        $province = Region::active()->province()->first();
        $cities = $province ? Region::active()->city()->where('parent_id', $province->id)->orderBy('name')->get() : collect();
        $districts = $cityId ? Region::active()->district()->where('parent_id', $cityId)->orderBy('name')->get() : collect();
        $villages = $districtId ? Region::active()->village()->where('parent_id', $districtId)->orderBy('name')->get() : collect();

        return view('app.dashboard', compact(
            'stats',
            'questionAnalytics',
            'dashboardSummary',
            'cities',
            'districts',
            'villages',
            'cityId',
            'districtId',
            'villageId',
            'gender',
            'category',
            'categoryOptions'
        ));
    }

    private function buildQuestionAnalytics($responses, $answerPayloads): array
    {
        $items = [];

        foreach ($this->questionCatalog() as $question) {
            $values = [];

            foreach ($responses as $response) {
                if (($question['source'] ?? 'answer') === 'respondent') {
                    $value = data_get($response->respondent, $question['field']);
                } else {
                    $payload = $answerPayloads->get($response->id, []);
                    $value = data_get($payload, $question['field']);
                }

                if (isset($question['transform']) && method_exists($this, $question['transform'])) {
                    $value = $this->{$question['transform']}($value);
                }

                $values[] = $value;
            }

            $items[] = $this->buildDistributionItem($question, $values, $responses->count(), 'responden');
        }

        $items[] = $this->buildFoodFrequencyChart($responses, $answerPayloads);
        $items[] = $this->buildHealthServiceMatrix($responses, $answerPayloads);

        return collect($items)
            ->filter()
            ->sortBy(fn ($item) => $item['sort'])
            ->values()
            ->all();
    }

    private function buildDistributionItem(array $question, array $values, int $baseTotal, string $denominatorLabel): array
    {
        $kind = $question['kind'] ?? 'single';
        $options = $question['options'] ?? [];
        $counts = $options ? array_fill_keys(array_values($options), 0) : [];
        $answered = 0;
        $mentions = 0;

        foreach ($values as $value) {
            if ($this->blank($value)) {
                continue;
            }

            if ($kind === 'text') {
                $answered++;
                $label = $this->cleanTextValue($value);
                $counts[$label] = ($counts[$label] ?? 0) + 1;
                continue;
            }

            if ($kind === 'multi') {
                $selected = is_array($value) ? $value : [$value];
                $selected = array_values(array_filter($selected, fn ($item) => ! $this->blank($item)));

                if (empty($selected)) {
                    continue;
                }

                $answered++;

                foreach ($selected as $selectedValue) {
                    $label = $this->optionLabel($selectedValue, $options);
                    $counts[$label] = ($counts[$label] ?? 0) + 1;
                    $mentions++;
                }

                continue;
            }

            $answered++;
            $label = $this->optionLabel($value, $options);
            $counts[$label] = ($counts[$label] ?? 0) + 1;
            $mentions++;
        }

        arsort($counts);

        $rows = collect($counts)->map(function ($count, $label) use ($answered, $baseTotal) {
            return [
                'label' => $label,
                'count' => $count,
                'pct' => $answered > 0 ? round(($count / $answered) * 100, 1) : 0,
                'coverage_pct' => $baseTotal > 0 ? round(($count / $baseTotal) * 100, 1) : 0,
            ];
        })->values()->all();

        $top = collect($rows)->first(fn ($row) => $row['count'] > 0);

        return [
            'key' => $question['key'],
            'number' => $question['number'],
            'label' => $question['label'],
            'group' => $question['group'],
            'kind' => $kind,
            'display' => $question['display'] ?? 'table',
            'wide' => $question['wide'] ?? false,
            'sort' => $question['sort'],
            'rows' => $rows,
            'answered' => $answered,
            'base_total' => $baseTotal,
            'mentions' => $kind === 'multi' ? $mentions : $answered,
            'denominator_label' => $denominatorLabel,
            'insight' => $this->buildInsight($top, $answered, $kind),
        ];
    }

    private function buildFoodFrequencyChart($responses, $answerPayloads): array
    {
        $foods = [
            'pangan_beras' => 'Beras',
            'pangan_ubi' => 'Ubi-ubian',
            'pangan_sagu' => 'Sagu',
        ];
        $frequencyOptions = $this->frequencyOptions();
        $rows = [];
        $answered = 0;

        foreach ($foods as $field => $foodLabel) {
            $rows[$field] = [
                'label' => $foodLabel,
                'counts' => array_fill_keys(array_values($frequencyOptions), 0),
            ];
        }

        foreach ($responses as $response) {
            $payload = $answerPayloads->get($response->id, []);
            $hasAnswer = false;

            foreach ($foods as $field => $foodLabel) {
                $value = data_get($payload, $field);

                if ($this->blank($value)) {
                    continue;
                }

                $label = $this->optionLabel($value, $frequencyOptions);
                $rows[$field]['counts'][$label] = ($rows[$field]['counts'][$label] ?? 0) + 1;
                $hasAnswer = true;
            }

            if ($hasAnswer) {
                $answered++;
            }
        }

        $datasets = collect($rows)->map(function ($row) {
            return [
                'label' => $row['label'],
                'data' => array_values($row['counts']),
            ];
        })->values()->all();

        return [
            'key' => 'frekuensi_pangan_pokok',
            'number' => 'F7',
            'label' => 'Frekuensi konsumsi beras, ubi-ubian, dan sagu',
            'group' => 'F. Pola Konsumsi Pangan',
            'kind' => 'grouped_bar',
            'display' => 'grouped_bar',
            'wide' => true,
            'sort' => 71,
            'rows' => array_values($rows),
            'chart_labels' => array_values($frequencyOptions),
            'datasets' => $datasets,
            'answered' => $answered,
            'base_total' => $responses->count(),
            'mentions' => collect($datasets)->sum(fn ($dataset) => array_sum($dataset['data'])),
            'denominator_label' => 'responden',
            'insight' => $answered > 0 ? 'Beras, ubi-ubian, dan sagu dibandingkan dalam satu diagram batang berkelompok.' : 'Belum ada jawaban terisi.',
        ];
    }

    private function buildHealthServiceMatrix($responses, $answerPayloads): array
    {
        $services = [
            'rumah_sakit' => 'Rumah Sakit',
            'puskesmas' => 'Puskesmas',
            'pustu' => 'Puskesmas Pembantu',
            'puskesmas_keliling' => 'Puskesmas Keliling',
            'klinik' => 'Klinik',
            'apotek' => 'Apotek',
            'lainnya' => 'Lainnya',
        ];

        $rows = [];
        $answered = 0;

        foreach ($services as $key => $label) {
            $rows[$key] = [
                'label' => $label,
                'medis' => 0,
                'rutin' => 0,
            ];
        }

        foreach ($responses as $response) {
            $payload = $answerPayloads->get($response->id, []);
            $matrix = data_get($payload, 'layanan_kes', []);
            $hasAnswer = false;

            foreach ($services as $key => $label) {
                if (data_get($matrix, "$key.medis")) {
                    $rows[$key]['medis']++;
                    $hasAnswer = true;
                }

                if (data_get($matrix, "$key.rutin")) {
                    $rows[$key]['rutin']++;
                    $hasAnswer = true;
                }
            }

            if ($hasAnswer) {
                $answered++;
            }
        }

        return [
            'key' => 'layanan_kes',
            'number' => 'G19',
            'label' => 'Tempat mendapatkan layanan kesehatan',
            'group' => 'G. Akses Layanan Kesehatan',
            'kind' => 'matrix',
            'display' => 'grouped_bar',
            'wide' => true,
            'sort' => 190,
            'rows' => array_values($rows),
            'chart_labels' => array_values($services),
            'datasets' => [
                ['label' => 'Medis', 'data' => collect($rows)->pluck('medis')->values()->all()],
                ['label' => 'Pemeriksaan rutin', 'data' => collect($rows)->pluck('rutin')->values()->all()],
            ],
            'answered' => $answered,
            'base_total' => $responses->count(),
            'mentions' => collect($rows)->sum('medis') + collect($rows)->sum('rutin'),
            'denominator_label' => 'responden',
            'insight' => $answered > 0 ? 'Terbaca pada '.$answered.' responden.' : 'Belum ada jawaban terisi.',
        ];
    }

    private function buildDashboardSummary(array $questionAnalytics, int $responseCount): array
    {
        $questionsWithData = collect($questionAnalytics)->where('answered', '>', 0)->count();

        return [
            'response_count' => $responseCount,
            'questions_total' => count($questionAnalytics),
            'questions_with_data' => $questionsWithData,
            'completion_pct' => count($questionAnalytics) > 0 ? round(($questionsWithData / count($questionAnalytics)) * 100, 1) : 0,
        ];
    }

    private function questionCatalog(): array
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

    private function frequencyOptions(): array
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

    private function optionLabel($value, array $options): string
    {
        $normalizedValue = $this->normalizeValue($value);

        foreach ($options as $optionValue => $label) {
            if ($this->normalizeValue($optionValue) === $normalizedValue) {
                return $label;
            }
        }

        return $this->cleanTextValue($value);
    }

    private function ageBucket($age): ?string
    {
        if ($this->blank($age)) {
            return null;
        }

        $age = (int) $age;

        return match (true) {
            $age < 60 => '<60',
            $age <= 64 => '60-64',
            $age <= 69 => '65-69',
            $age <= 74 => '70-74',
            $age <= 79 => '75-79',
            default => '80+',
        };
    }

    private function buildInsight(?array $top, int $answered, string $kind): string
    {
        if (! $top || $answered === 0) {
            return 'Belum ada jawaban terisi.';
        }

        if ($kind === 'multi') {
            return 'Pilihan paling sering muncul: '.$top['label'].' ('.$top['pct'].'% dari responden yang menjawab).';
        }

        if ($kind === 'text') {
            return 'Isian yang paling sering muncul: '.$top['label'].' ('.$top['count'].' kali).';
        }

        return 'Mayoritas: '.$top['label'].' ('.$top['pct'].'% dari responden yang menjawab).';
    }

    private function cleanTextValue($value): string
    {
        if (is_array($value)) {
            $value = implode(', ', array_filter($value));
        }

        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value) ?: '-';

        return mb_strlen($value) > 90 ? mb_substr($value, 0, 87).'...' : $value;
    }

    private function normalizeValue($value): string
    {
        $value = $this->cleanTextValue($value);
        $value = str_replace(['â€“', '–', '—'], '-', $value);

        return mb_strtolower($value);
    }

    private function blank($value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
