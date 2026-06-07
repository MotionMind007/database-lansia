<?php

namespace Database\Seeders;

use App\Models\FamilyMember;
use App\Models\Region;
use App\Models\Respondent;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\VerificationLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DemoSurveyResponseSeeder extends Seeder
{
    private const QUESTIONNAIRE_PREFIX = 'DUMMY';

    public function run(): void
    {
        $count = max(1, (int) env('DEMO_SURVEY_COUNT', 500));
        $reset = filter_var(env('DEMO_SURVEY_RESET', true), FILTER_VALIDATE_BOOL);

        DB::transaction(function () use ($count, $reset) {
            if ($reset) {
                $this->deleteExistingDummyData();
            }

            $survey = $this->ensureSurvey();
            $villages = $this->ensureVillages();
            $surveyor = $this->ensureUser('surveyor');
            $verifier = $this->ensureUser('verifikator');
            $runCode = now()->format('YmdHis');

            for ($i = 1; $i <= $count; $i++) {
                $village = $villages->random();
                $gender = $this->weighted([
                    'male' => 48,
                    'female' => 52,
                ]);
                $age = $this->age();
                $education = $this->weighted([
                    'Tidak Sekolah' => 18,
                    'SD' => 35,
                    'SMP' => 20,
                    'SMA' => 18,
                    'Perguruan Tinggi' => 9,
                ]);
                $occupation = $this->weighted([
                    'Petani' => 28,
                    'Nelayan' => 12,
                    'Pedagang' => 14,
                    'Pensiunan' => 10,
                    'Ibu rumah tangga' => 18,
                    'Tidak bekerja' => 12,
                    'Tokoh adat' => 6,
                ]);
                $status = $this->status();
                $interviewDate = Carbon::now()
                    ->subDays(random_int(0, 180))
                    ->subHours(random_int(0, 23));

                $respondent = Respondent::create([
                    'full_name' => $this->name($gender).' (Demo '.$i.')',
                    'gender' => $gender,
                    'age' => $age,
                    'education' => $education,
                    'occupation' => $occupation,
                    'address' => 'Alamat demo RT '.random_int(1, 6).'/RW '.random_int(1, 4).', '.$village->name,
                    'phone' => '08'.random_int(1111111111, 9999999999),
                    'religion' => $this->random(['Kristen Protestan', 'Katolik', 'Islam', 'Hindu']),
                    'ethnicity' => $this->random(['Papua', 'Biak', 'Sentani', 'Dani', 'Yapen', 'Nusantara']),
                    'citizenship_status' => $this->weighted([
                        'OAP' => 62,
                        'Non_OAP' => 28,
                        'WNI' => 10,
                    ]),
                    'household_status' => $this->random(['Kepala keluarga', 'Istri/suami', 'Orang tua', 'Keluarga lain']),
                    'region_id' => $village->id,
                ]);

                $this->createFamilyMembers($respondent);

                $questionnaireNumber = $reset
                    ? sprintf('%s-%05d', self::QUESTIONNAIRE_PREFIX, $i)
                    : sprintf('%s-%s-%05d', self::QUESTIONNAIRE_PREFIX, $runCode, $i);

                $response = SurveyResponse::create([
                    'survey_id' => $survey->id,
                    'respondent_id' => $respondent->id,
                    'questionnaire_number' => $questionnaireNumber,
                    'surveyor_id' => $surveyor->id,
                    'region_id' => $village->id,
                    'interview_date' => $interviewDate->toDateString(),
                    'status' => $status,
                    'surveyor_notes' => $this->surveyorNote($status),
                    'submitted_at' => $status === SurveyResponse::STATUS_DRAFT ? null : $interviewDate->copy()->addHours(3),
                    'verified_at' => in_array($status, [SurveyResponse::STATUS_VERIFIED, SurveyResponse::STATUS_REJECTED], true)
                        ? $interviewDate->copy()->addDays(random_int(1, 6))
                        : null,
                ]);

                $response->forceFill([
                    'verified_by' => in_array($status, [SurveyResponse::STATUS_VERIFIED, SurveyResponse::STATUS_REJECTED], true)
                        ? $verifier->id
                        : null,
                    'created_by' => $surveyor->id,
                    'updated_by' => $surveyor->id,
                    'created_at' => $interviewDate,
                    'updated_at' => in_array($status, [SurveyResponse::STATUS_VERIFIED, SurveyResponse::STATUS_REJECTED], true)
                        ? $interviewDate->copy()->addDays(random_int(1, 6))
                        : $interviewDate->copy()->addHours(random_int(2, 8)),
                ])->save();

                SurveyAnswer::create([
                    'survey_response_id' => $response->id,
                    'question_id' => null,
                    'answer_json' => $this->answerPayload($respondent),
                ]);

                $this->createVerificationLog($response, $verifier);
            }
        });

        $this->command?->info("Demo survey responses ready: {$count} records.");
        $this->command?->line('Run with DEMO_SURVEY_COUNT=1000 to change volume, or DEMO_SURVEY_RESET=false to append.');
    }

    private function deleteExistingDummyData(): void
    {
        $responses = SurveyResponse::query()
            ->where('questionnaire_number', 'like', self::QUESTIONNAIRE_PREFIX.'-%')
            ->get(['id', 'respondent_id']);

        if ($responses->isEmpty()) {
            return;
        }

        $respondentIds = $responses->pluck('respondent_id')->filter()->unique();

        SurveyResponse::whereIn('id', $responses->pluck('id'))->delete();
        Respondent::whereIn('id', $respondentIds)->delete();
    }

    private function ensureSurvey(): Survey
    {
        return Survey::firstOrCreate(
            ['title' => 'Kuesioner Pendataan Lansia Provinsi Papua'],
            [
                'description' => 'Kuesioner pendukung pendataan lansia di Provinsi Papua.',
                'version' => '1.0',
                'is_active' => true,
            ]
        );
    }

    private function ensureVillages()
    {
        $villages = Region::active()->village()->get();

        if ($villages->isNotEmpty()) {
            return $villages;
        }

        $province = Region::firstOrCreate(
            ['code' => 'PPA'],
            ['name' => 'Provinsi Papua', 'type' => 'province', 'is_active' => true]
        );

        $city = Region::firstOrCreate(
            ['code' => 'PPA-DEMO-JAY'],
            ['parent_id' => $province->id, 'name' => 'Kota Jayapura Demo', 'type' => 'city', 'is_active' => true]
        );

        $district = Region::firstOrCreate(
            ['code' => 'PPA-DEMO-JAY-ABP'],
            ['parent_id' => $city->id, 'name' => 'Distrik Demo', 'type' => 'district', 'is_active' => true]
        );

        foreach (['Kampung Demo Satu', 'Kampung Demo Dua', 'Kampung Demo Tiga'] as $index => $name) {
            Region::firstOrCreate(
                ['code' => 'PPA-DEMO-VIL-'.($index + 1)],
                ['parent_id' => $district->id, 'name' => $name, 'type' => 'village', 'is_active' => true]
            );
        }

        return Region::active()->village()->get();
    }

    private function ensureUser(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user = User::role($roleName)->first();

        if ($user) {
            return $user;
        }

        $user = User::firstOrCreate(
            ['email' => $roleName.'-demo@lansiapapua.id'],
            [
                'name' => Str::title($roleName).' Demo',
                'username' => $roleName.'_demo',
                'password' => Hash::make(Str::password(32)),
                'is_active' => true,
            ]
        );

        $user->assignRole($role);

        return $user;
    }

    private function createFamilyMembers(Respondent $respondent): void
    {
        $count = random_int(0, 5);
        $relations = ['Suami/Istri', 'Anak', 'Cucu', 'Saudara', 'Menantu'];

        for ($i = 1; $i <= $count; $i++) {
            $gender = $this->random(['male', 'female']);

            FamilyMember::create([
                'respondent_id' => $respondent->id,
                'name' => $this->name($gender).' (Keluarga Demo)',
                'gender' => $gender,
                'age' => random_int(3, 82),
                'status' => $this->random($relations),
                'education' => $this->random(['Tidak Sekolah', 'SD', 'SMP', 'SMA', 'Perguruan Tinggi']),
                'occupation' => $this->random(['Pelajar', 'Petani', 'Pedagang', 'Karyawan', 'Tidak bekerja']),
                'ktp_status' => $this->weighted([
                    'e_ktp' => 70,
                    'ktp_nasional' => 20,
                    'no_ktp' => 10,
                ]),
            ]);
        }
    }

    private function createVerificationLog(SurveyResponse $response, User $verifier): void
    {
        if ($response->status === SurveyResponse::STATUS_DRAFT) {
            return;
        }

        if ($response->status === SurveyResponse::STATUS_SUBMITTED) {
            VerificationLog::create([
                'survey_response_id' => $response->id,
                'status' => SurveyResponse::STATUS_SUBMITTED,
                'note' => 'Data demo disubmit dan menunggu verifikasi.',
                'verified_by' => $verifier->id,
                'verified_at' => $response->submitted_at ?? now(),
            ]);

            return;
        }

        VerificationLog::create([
            'survey_response_id' => $response->id,
            'status' => $response->status,
            'note' => match ($response->status) {
                SurveyResponse::STATUS_NEED_REVISION => 'Data demo perlu revisi: lengkapi catatan kesehatan dan pengeluaran.',
                SurveyResponse::STATUS_VERIFIED => 'Data demo sudah diverifikasi.',
                SurveyResponse::STATUS_REJECTED => 'Data demo ditolak untuk kebutuhan uji alur.',
                default => 'Log verifikasi data demo.',
            },
            'verified_by' => $verifier->id,
            'verified_at' => $response->verified_at ?? ($response->submitted_at ?? now()),
        ]);
    }

    private function answerPayload(Respondent $respondent): array
    {
        $keluhanKes = $this->weighted(['ya' => 58, 'tidak' => 42]);
        $periksaRutin = $this->weighted(['ya' => 63, 'tidak' => 37]);
        $bansos = $this->weighted(['pernah' => 68, 'tidak' => 32]);
        $jamsosial = $this->weighted(['pernah' => 46, 'tidak' => 54]);
        $pelatihan = $this->weighted(['pernah' => 22, 'tidak' => 78]);

        return [
            'jml_anggota' => random_int(1, 7),
            'penghasilan' => $this->weighted([
                'Di bawah Rp 500.000' => 18,
                'Rp 500.000 - Rp 1.000.000' => 26,
                'Rp 1.000.000 - Rp 2.500.000' => 30,
                'Rp 2.500.000 - Rp 5.000.000' => 18,
                'Di atas Rp 5.000.000' => 8,
            ]),

            'pangan_ubi' => $this->frequency(),
            'pangan_beras' => $this->frequency(),
            'pangan_sagu' => $this->frequency(),
            'sumber_pangan' => $this->many(['panen_sendiri', 'beli', 'lainnya'], 1, 2),
            'sumber_pangan_beli' => $this->random(['Pasar kampung', 'Warung sekitar', 'Kios distrik']),
            'sumber_pangan_lainnya' => $this->maybe('Kiriman keluarga'),
            'pola_konsumsi' => $this->weighted(['a' => 10, 'b' => 24, 'c' => 36, 'd' => 20, 'e' => 10]),
            'konsumsi_hari' => $this->weighted(['1' => 14, '2' => 45, '3' => 41]),
            'cara_masak' => $this->many(['dibakar', 'diolah', 'tidak_dimasak'], 1, 2),
            'bahan_bakar' => $this->many(['kompor', 'gas_lpg', 'kayu_bakar', 'lainnya'], 1, 2),
            'bahan_bakar_lainnya' => $this->maybe('Minyak tanah'),
            'bansos_sembako' => $this->weighted(['pernah' => 62, 'tidak' => 38]),
            'pemberi_sembako' => $this->weighted(['pemerintah' => 72, 'swasta' => 15, 'lainnya' => 13]),
            'pemberi_sembako_lainnya' => $this->maybe('Gereja/komunitas'),

            'keluhan_kes' => $keluhanKes,
            'keluhan_detail' => $keluhanKes === 'ya' ? $this->random(['Hipertensi', 'Nyeri sendi', 'Diabetes', 'Batuk lama', 'Gangguan penglihatan', 'Asam urat']) : null,
            'periksa_rutin' => $periksaRutin,
            'frek_periksa' => $periksaRutin === 'ya' ? $this->weighted([
                'seminggu_sekali' => 10,
                'dua_minggu_sekali' => 14,
                'sebulan_sekali' => 48,
                'lebih_sebulan' => 28,
            ]) : null,
            'layanan_kes' => $this->healthServices(),
            'layanan_kes_lainnya_nama' => $this->maybe('Pos pelayanan kampung'),
            'jangkau_kes' => $this->weighted(['kurang_30' => 34, '30_60' => 32, '1_5_jam' => 24, 'lebih_5jam' => 10]),
            'jangkau_kes_detail' => $this->maybe('Akses jalan dipengaruhi cuaca'),
            'transport_kes' => $this->many(['pribadi', 'umum_ojek', 'pemda', 'lainnya'], 1, 2),
            'transport_kes_lainnya' => $this->maybe('Jalan kaki'),
            'biaya_kes' => $this->many(['pribadi', 'kis_kps', 'bpjs', 'lainnya'], 1, 2),
            'biaya_kes_lainnya' => $this->maybe('Bantuan keluarga'),
            'masalah_kes' => $this->random(['Jarak layanan jauh', 'Biaya transport tinggi', 'Obat tidak selalu tersedia', 'Tidak ada pendamping', 'Antrian panjang']),

            'status_rumah' => $this->weighted(['milik_sendiri' => 68, 'sewa_kontrak' => 8, 'rumah_dinas' => 4, 'bantuan_pemerintah' => 15, 'lainnya' => 5]),
            'status_rumah_lainnya' => $this->maybe('Menumpang keluarga'),
            'jenis_rumah' => $this->weighted(['permanen' => 25, 'semi_permanen' => 28, 'kayu_papan' => 24, 'kayu_tanah' => 10, 'rumah_adat' => 10, 'lainnya' => 3]),
            'jenis_rumah_lainnya' => $this->maybe('Campuran papan dan seng'),
            'sumber_air' => $this->many(['sumur_pribadi', 'sumur_umum', 'mata_air', 'sungai_kali', 'lainnya'], 1, 2),
            'mata_air_nama' => $this->maybe('Mata air kampung'),
            'nama_sungai' => $this->maybe('Sungai kecil setempat'),
            'nama_kali' => $this->maybe('Kali dekat permukiman'),
            'sumber_air_lainnya' => $this->maybe('Air hujan'),
            'sistem_air' => $this->many(['ambil_sendiri', 'bak_penampungan', 'sambungan_rumah', 'lainnya'], 1, 2),
            'sistem_air_lainnya' => $this->maybe('Tandon keluarga'),
            'mck' => $this->weighted(['pribadi' => 56, 'umum' => 30, 'tidak_ada' => 14]),
            'bab_fasilitas' => $this->weighted(['milik_sendiri' => 62, 'lainnya' => 38]),
            'bab_pembuangan' => $this->weighted(['septik' => 65, 'lainnya' => 35]),
            'jenis_kloset' => $this->weighted(['leher_angsa' => 58, 'lainnya' => 42]),
            'penerangan' => $this->many(['pln', 'genset', 'solar_cell', 'lainnya'], 1, 2),
            'penerangan_lainnya' => $this->maybe('Lampu minyak'),
            'lama_penerangan' => $this->weighted(['24jam' => 45, '12jam' => 20, '6jam' => 16, 'kurang_6jam' => 10, 'tidak_ada' => 9]),

            'media_info' => $this->many(['tv', 'radio', 'berita_online', 'medsos'], 1, 3),
            'punya_hp' => $this->weighted(['ya' => 64, 'tidak' => 36]),
            'media_alternatif' => $this->many(['perangkat_distrik', 'kelompok_masyarakat', 'komunitas_ibadah', 'keluarga'], 1, 2),

            'bansos' => $bansos,
            'jenis_bansos' => $bansos === 'pernah' ? $this->many(['pkh', 'blt', 'kartu_sembako', 'prakerja'], 1, 2) : [],
            'jamsosial' => $jamsosial,
            'jenis_jamsosial' => $jamsosial === 'pernah' ? $this->many(['jamsostek', 'jaminan_pensiun', 'jht', 'jkn'], 1, 2) : [],
            'pelatihan_lansia' => $pelatihan,
            'jenis_pelatihan' => $pelatihan === 'pernah' ? $this->random(['Kesehatan lansia', 'Kerajinan tangan', 'Literasi digital', 'Pengolahan pangan lokal']) : null,
            'masalah_linsos' => $this->random(['Data penerima belum sinkron', 'Informasi program kurang jelas', 'Jarak pengurusan jauh', 'Dokumen administrasi kurang lengkap', 'Bantuan tidak rutin']),

            'kunjungi' => $this->weighted(['Setiap Hari' => 18, 'Dua kali seminggu' => 24, 'Satu kali seminggu' => 34, 'Tidak Pernah' => 24]),
            'perkumpulan' => $this->weighted(['Ya' => 52, 'Tidak' => 48]),
            'rapat_warga' => $this->weighted(['Pernah' => 57, 'Tidak' => 43]),
            'pemilu' => $this->weighted(['Pernah' => 76, 'Tidak' => 24]),

            'pengeluaran_total' => $this->weighted([
                'Di bawah Rp 1.000.000' => 20,
                'Rp 1.000.000 - Rp 2.000.000' => 32,
                'Rp 2.000.000 - Rp 3.000.000' => 27,
                'Rp 3.000.000 - Rp 4.000.000' => 14,
                'Di atas Rp 4.000.000' => 7,
            ]),
            'pengeluaran_items' => $this->expenseItems($respondent->age),
        ];
    }

    private function healthServices(): array
    {
        $services = ['rumah_sakit', 'puskesmas', 'pustu', 'puskesmas_keliling', 'klinik', 'apotek', 'lainnya'];
        $answers = [];

        foreach ($services as $service) {
            $answers[$service] = [
                'medis' => $this->chance($service === 'puskesmas' ? 72 : 35),
                'rutin' => $this->chance($service === 'puskesmas' ? 58 : 28),
            ];
        }

        return $answers;
    }

    private function expenseItems(int $age): array
    {
        return [
            'Konsumsi (Kebutuhan Pokok)' => random_int(400000, 1800000),
            'Energi (penerangan & bahan bakar)' => random_int(80000, 450000),
            'Pendidikan' => $age > 75 ? random_int(0, 150000) : random_int(0, 450000),
            'Kesehatan' => random_int(50000, 800000),
            'Acara Sosial/Keagamaan/Adat' => random_int(0, 650000),
            'Transportasi' => random_int(50000, 600000),
        ];
    }

    private function frequency(): string
    {
        return $this->weighted([
            '50' => 8,
            '25' => 34,
            '15' => 24,
            '10' => 18,
            '5' => 9,
            '0' => 7,
        ]);
    }

    private function status(): string
    {
        return $this->weighted([
            SurveyResponse::STATUS_VERIFIED => 52,
            SurveyResponse::STATUS_SUBMITTED => 24,
            SurveyResponse::STATUS_NEED_REVISION => 15,
            SurveyResponse::STATUS_DRAFT => 6,
            SurveyResponse::STATUS_REJECTED => 3,
        ]);
    }

    private function surveyorNote(string $status): ?string
    {
        return match ($status) {
            SurveyResponse::STATUS_DRAFT => 'Draft data demo, belum siap diverifikasi.',
            SurveyResponse::STATUS_NEED_REVISION => 'Data demo sengaja dibuat perlu revisi untuk uji alur perbaikan.',
            SurveyResponse::STATUS_REJECTED => 'Data demo untuk uji status ditolak.',
            default => $this->maybe('Catatan lapangan demo: responden kooperatif.'),
        };
    }

    private function age(): int
    {
        return match ($this->weighted(['60' => 24, '65' => 25, '70' => 22, '75' => 17, '80' => 12])) {
            '60' => random_int(60, 64),
            '65' => random_int(65, 69),
            '70' => random_int(70, 74),
            '75' => random_int(75, 79),
            default => random_int(80, 92),
        };
    }

    private function name(string $gender): string
    {
        $firstNames = $gender === 'male'
            ? ['Yohanis', 'Samuel', 'Musa', 'Petrus', 'Markus', 'Daud', 'Yakob', 'Hendrik']
            : ['Maria', 'Ester', 'Martha', 'Yuliana', 'Magdalena', 'Ruth', 'Agnes', 'Mina'];

        $lastNames = ['Numberi', 'Wambrauw', 'Suebu', 'Tebay', 'Kogoya', 'Wenda', 'Rumbiak', 'Mofu', 'Korwa', 'Yoku'];

        return $this->random($firstNames).' '.$this->random($lastNames);
    }

    private function many(array $values, int $min = 1, int $max = 3): array
    {
        shuffle($values);

        return array_slice($values, 0, random_int($min, min($max, count($values))));
    }

    private function maybe(string $value, int $probability = 20): ?string
    {
        return $this->chance($probability) ? $value : null;
    }

    private function chance(int $probability): bool
    {
        return random_int(1, 100) <= $probability;
    }

    private function random(array $values)
    {
        return $values[array_rand($values)];
    }

    private function weighted(array $weights)
    {
        $total = array_sum($weights);
        $roll = random_int(1, $total);

        foreach ($weights as $value => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $value;
            }
        }

        return array_key_first($weights);
    }
}
